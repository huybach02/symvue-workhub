<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Service\AuthService;
use App\Service\CacheService;
use App\Service\DepartmentService;
use App\Service\DeviceInfoService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly DeviceInfoService $deviceInfoService,
        private readonly CacheService $cacheService,
        private readonly DepartmentService $departmentService,
        private readonly ImageRepository $imageRepository,
    ) {}

    #[Route("/auth/me", methods: ["GET"])]
    public function me(#[CurrentUser] ?User $user)
    {
        if (null === $user) {
            return CustomResponse::error(t("auth.me.not_found"), 401);
        }

        $userPermission = $this->departmentService->getCachedUserPermissions($user->getId());

        $userData = $user->jsonSerialize();
        $userData['image'] = $this->imageRepository->getImages($user, "avatar");
        $userData['permissions'] = $userPermission;

        return CustomResponse::success($userData);
    }

    #[Route("/auth/logout", methods: ["POST"])]
    public function logout(
        Request $request,
        RefreshTokenManagerInterface $refreshTokenManager,
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

            // Tạo một key unique
            $tokenKey = "blacklist_" . md5($accessToken);

            // Lưu vào cache với TTL = 1 ngày (Redis native TTL)
            $this->cacheService->set($tokenKey, true, 3600 * 24);
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
