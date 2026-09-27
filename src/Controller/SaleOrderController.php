<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\SaleOrderDTO;
use App\DTO\UpdateSaleOrderStatusDTO;
use App\Entity\User;
use App\Service\SaleOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SaleOrderController extends AbstractController
{
    public function __construct(
        private readonly SaleOrderService $saleOrderService,
    ) {}

    #[Route('/sale-order', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        try {
            $result = $this->saleOrderService->findAll($params, $currentUser);
            return CustomResponse::success([
                'collection' => $result['collection'],
                'total' => $result['total'],
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                    'total_current' => $result['total_current'],
                ]
            ]);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/sale-order/select', methods: ['GET'])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->saleOrderService->getDataSelect($params, $currentUser);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/sale-order/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->saleOrderService->findById($id, $currentUser);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/sale-order/{id}/status', methods: ['PUT', 'PATCH'])]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateSaleOrderStatusDTO $dto
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->saleOrderService->updateStatus(
                $id,
                $dto->status,
                $dto->paymentStatus,
                $currentUser
            );
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/sale-order/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] SaleOrderDTO $saleOrderDTO
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->saleOrderService->update($id, $saleOrderDTO, $currentUser);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
