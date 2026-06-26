<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\UserDTO;
use App\DTO\ChangePasswordDTO;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

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
