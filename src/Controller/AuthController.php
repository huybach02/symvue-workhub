<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\Entity\User;
use App\Repository\BoPhanRepository;
use App\Service\AuthService;
use App\Service\DeviceInfoService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Psr\Cache\CacheItemPoolInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly DeviceInfoService $deviceInfoService,
        private readonly CacheItemPoolInterface $cache,
        private readonly BoPhanRepository $boPhanRepository,
    ) {}

    #[Route("/auth/me", methods: ["GET"])]
    public function me(#[CurrentUser] ?User $user)
    {
        if (null === $user) {
            return CustomResponse::error(t("auth.me.not_found"), 401);
        }

        $key = "user_permissions_" . $user->getId();

        $userPermission = $this->cache->getItem($key)->get();

        $userData = $user->jsonSerialize();
        $userData['permissions'] = $userPermission;

        if ($user->getBoPhanId()) {
            $boPhan = $this->boPhanRepository->find($user->getBoPhanId());
            $userData['boPhan'] = $boPhan->jsonSerialize();
        }

        return CustomResponse::success($userData);
    }

    #[Route("/auth/logout", methods: ["POST"])]
    public function logout(
        Request $request,
        RefreshTokenManagerInterface $refreshTokenManager,
        CacheItemPoolInterface $cache,
    ) {
        // 1. Xử lý Refresh Token
        $payload = $request->toArray();
        $refreshTokenString = $payload["refresh_token"] ?? null;
        if ($refreshTokenString) {
            $refreshToken = $refreshTokenManager->get($refreshTokenString);
            if ($refreshToken) {
                $refreshTokenManager->delete($refreshToken);
            }
        }

        // 2. Xử lý Blacklist Access Token
        // Lấy token từ header Authorization: Bearer <token>
        $authorizationHeader = $request->headers->get("Authorization");

        if ($authorizationHeader) {
            $accessToken = str_replace("Bearer ", "", $authorizationHeader);

            // Tạo một key unique cho token này
            $tokenKey = "blacklist_" . md5($accessToken);

            // Lưu vào cache với TTL = 1 ngày (Redis native TTL)
            $item = $cache->getItem($tokenKey);
            $item->set(true);
            $item->expiresAfter(3600 * 24); // Token sẽ bị chặn trong 1 ngày
            $cache->save($item);
        }

        return CustomResponse::success([], t("auth.logout.success"));
    }

    #[Route("/auth/verify-otp", methods: ["POST"])]
    public function verifyOtp(
        Request $request,
    ) {
        try {
            $payload = $request->toArray();
            $metadata = $this->deviceInfoService->getMetadata($request);
            $deviceId = $this->authService->verifyOtp($payload['email'], $payload['otp'], $metadata);
            return CustomResponse::success($deviceId, t("auth.otp_success"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/auth/forgot-password", methods: ["POST"])]
    public function forgotPassword(Request $request)
    {
        try {
            $payload = $request->toArray();
            $this->authService->forgotPassword($payload['email']);
            return CustomResponse::success([], t("auth.forgot_password"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/auth/change-password", methods: ["POST"])]
    public function changePassword(Request $request)
    {
        try {
            $payload = $request->toArray();
            $this->authService->changePassword($payload['email'], $payload['password'], $payload['confirm_password']);
            return CustomResponse::success([], t("auth.change_password"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
