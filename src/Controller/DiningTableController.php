<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\DiningTableDTO;
use App\DTO\DiningTableRangeDTO;
use App\Service\DiningTableService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class DiningTableController extends AbstractController
{
    public function __construct(
        private readonly DiningTableService $diningTableService,
    ) {}

    #[Route('/dining-table', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->diningTableService->findAll($params);
            return CustomResponse::success([
                'collection' => $result['collection'],
                'total' => $result['total'],
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                    'total_current' => $result['total_current'],
                ],
            ]);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/dining-table/select', methods: ['GET'])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->diningTableService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/dining-table/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->diningTableService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/dining-table/ensure-range', methods: ['POST'])]
    #[Route('/dining-table', methods: ['POST'])]
    public function ensureRange(
        #[MapRequestPayload] DiningTableRangeDTO $dto
    ): JsonResponse {
        try {
            $data = $this->diningTableService->ensureRange($dto);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/dining-table/{id}/toggle-status', methods: ['PATCH', 'POST'])]
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $data = $this->diningTableService->toggleStatus($id);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/dining-table/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] DiningTableDTO $diningTableDTO
    ): JsonResponse {
        try {
            $data = $this->diningTableService->update($id, $diningTableDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
