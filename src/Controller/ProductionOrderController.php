<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\ProductionOrderDTO;
use App\DTO\ProductionOrderItemStatusDTO;
use App\DTO\ProductionItemInspectDTO;
use App\Entity\User;
use App\Exception\InsufficientMaterialException;
use App\Service\ProductionOrderService;
use App\Service\ProductionInspectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProductionOrderController extends AbstractController
{
    public function __construct(
        private readonly ProductionOrderService $productionOrderService,
        private readonly ProductionInspectionService $productionInspectionService,
    ) {}

    #[Route('/production-order', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->productionOrderService->findAll($params);
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

    #[Route('/production-order/select', methods: ['GET'])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->productionOrderService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->productionOrderService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/item/{id}/status', methods: ['PUT'])]
    public function updateItemStatus(
        int $id,
        #[MapRequestPayload] ProductionOrderItemStatusDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->productionOrderService->updateItemStatus(
                $id,
                $dto->status,
                $currentUser,
            );

            return CustomResponse::success($data, t('success.updated'));
        } catch (InsufficientMaterialException $e) {
            return CustomResponse::error($e->getMessage(), $e->getShortages());
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/item/{id}/inspect', methods: ['POST'])]
    public function inspectItem(
        int $id,
        #[MapRequestPayload(validationGroups: ['create'])] ProductionItemInspectDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            return CustomResponse::success(
                $this->productionInspectionService->inspect($id, $dto, $currentUser),
                'Đã kiểm hàng và nhập kho thành phẩm',
            );
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/open-shortages', methods: ['GET'])]
    public function openShortages(Request $request): JsonResponse
    {
        $merchandiseId = (int) $request->query->get('merchandiseId', 0);
        if ($merchandiseId <= 0) {
            return CustomResponse::error('Vui lòng chọn thành phẩm');
        }

        try {
            return CustomResponse::success(
                $this->productionOrderService->findOpenShortages($merchandiseId),
            );
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] ProductionOrderDTO $productionOrderDTO
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->productionOrderService->create(
                $productionOrderDTO,
                $currentUser,
            );
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] ProductionOrderDTO $productionOrderDTO
    ): JsonResponse {
        try {
            $data = $this->productionOrderService->update($id, $productionOrderDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/production-order/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->productionOrderService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/production-order/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->productionOrderService->findAll();
    //     return $this->productionOrderExportService->export($data);
    // }

    // #[Route('/production-order/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->productionOrderImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/production-order/template-import', methods: ['GET'])]
    // public function downloadTemplate(ProductionOrderTemplateImportService $service): Response
    // {
    //     return $service->generateProductionOrderTemplate();
    // }
}
