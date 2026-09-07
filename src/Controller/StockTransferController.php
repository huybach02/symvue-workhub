<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\StockTransferDTO;
use App\Entity\User;
use App\Service\StockTransferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class StockTransferController extends AbstractController
{
    public function __construct(
        private readonly StockTransferService $stockTransferService,
    ) {}

    #[Route('/stock-transfer/context', methods: ['GET'])]
    public function context(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            return CustomResponse::success(
                $this->stockTransferService->getRequestContext($currentUser),
            );
        } catch (\Throwable $throwable) {
            return CustomResponse::error($throwable->getMessage());
        }
    }

    #[Route('/stock-transfer', methods: ['POST'])]
    public function execute(
        #[MapRequestPayload] StockTransferDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            return CustomResponse::success(
                $this->stockTransferService->execute($dto, $currentUser),
                t('success.transferred'),
            );
        } catch (\Throwable $throwable) {
            return CustomResponse::error($throwable->getMessage());
        }
    }
}
