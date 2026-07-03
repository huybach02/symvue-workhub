<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\UserDTO;
use App\DTO\ChangePasswordDTO;
use App\Entity\User;
use App\Service\UserService;
use App\Service\UserSignatureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    #[Route("/profile/signature", methods: ["GET"])]
    public function getSignature(
        #[CurrentUser] ?User $user,
        UserSignatureService $signatureService,
    ): JsonResponse {
        if (!$user) {
            return CustomResponse::error(t("auth.unauthorized"), [], 401);
        }

        try {
            $signature = $signatureService->getCurrentUserSignature($user);
            return CustomResponse::success($signature);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/profile/signature", methods: ["POST"])]
    public function saveSignature(
        #[CurrentUser] ?User $user,
        Request $request,
        UserSignatureService $signatureService,
    ): JsonResponse {
        if (!$user) {
            return CustomResponse::error(t("auth.unauthorized"), [], 401);
        }

        try {
            $payload = json_decode($request->getContent(), true);
            $signature = $signatureService->saveSignature($user, $payload);
            return CustomResponse::success($signature, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/profile/signature", methods: ["DELETE"])]
    public function deleteSignature(
        #[CurrentUser] ?User $user,
        UserSignatureService $signatureService,
    ): JsonResponse {
        if (!$user) {
            return CustomResponse::error(t("auth.unauthorized"), [], 401);
        }

        try {
            $signatureService->deleteSignature($user);
            return CustomResponse::success([], t("success.deleted"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/profile", methods: ["PUT"])]
    public function updateProfile(
        #[CurrentUser] ?User $user,
        #[MapRequestPayload(validationGroups: ["update"])] UserDTO $dto,
    ): JsonResponse {
        if (!$user) {
            return CustomResponse::error(t("auth.unauthorized"), [], 401);
        }

        try {
            $data = $this->userService->updateProfile($user, $dto);
            return CustomResponse::success($data, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/profile/change-password", methods: ["POST"])]
    public function changePassword(
        #[CurrentUser] ?User $user,
        #[MapRequestPayload] ChangePasswordDTO $dto,
    ): JsonResponse {
        if (!$user) {
            return CustomResponse::error(t("auth.unauthorized"), [], 401);
        }

        try {
            $this->userService->changePassword($user, $dto);
            return CustomResponse::success([], t("auth.change_password_success"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
