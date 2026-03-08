<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\Service\PresenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PresenceController extends AbstractController
{
    public function __construct(
        private readonly PresenceService $presenceService,
    ) {}

    #[Route('/presence/online', methods: ['POST'])]
    public function online(): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->presenceService->setOnline($currentUser->getId());
            return CustomResponse::success([], 'Online');
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/presence/offline', methods: ['POST'])]
    public function offline(): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->presenceService->setOffline($currentUser->getId());
            return CustomResponse::success([], 'Offline');
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/presence/status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        $userIds = $request->query->all('userIds');

        if (empty($userIds)) {
            return CustomResponse::success([]);
        }

        try {
            $statuses = $this->presenceService->getStatuses($userIds);
            return CustomResponse::success($statuses);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
