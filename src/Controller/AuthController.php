<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\Entity\User;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class AuthController extends AbstractController
{
    #[Route("/auth/me", name: "api_auth_me", methods: ["GET"])]
    public function me(#[CurrentUser] ?User $user, CacheInterface $appCache)
    {
        if (null === $user) {
            return CustomResponse::error(t("auth.me.not_found"), 401);
        }

        return CustomResponse::success($user->jsonSerialize());
    }

    #[Route("/auth/logout", name: "api_auth_logout", methods: ["POST"])]
    public function logout(
        Request $request,
        RefreshTokenManagerInterface $refreshTokenManager,
        CacheInterface $cache,
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

            // Lưu vào cache.
            // Thời gian sống nên set bằng TTL của token (ví dụ 3600s).
            $cache->get($tokenKey, function (ItemInterface $item) {
                $item->expiresAfter(3600 * 24); // Token sẽ bị chặn trong 1 ngày
                return true;
            });
        }

        return CustomResponse::success([], t("auth.logout.success"));
    }
}
