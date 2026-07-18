<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\WarehouseDTO;
use App\Service\WarehouseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WarehouseController extends AbstractController
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    #[Route('/warehouse', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->warehouseService->findAll($params);
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

    #[Route('/warehouse/select', methods: ['GET'])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->warehouseService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/warehouse/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->warehouseService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/warehouse', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] WarehouseDTO $warehouseDTO
    ): JsonResponse {
        try {
            $data = $this->warehouseService->create($warehouseDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/warehouse/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] WarehouseDTO $warehouseDTO
    ): JsonResponse {
        try {
            $data = $this->warehouseService->update($id, $warehouseDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/warehouse/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->warehouseService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/warehouse/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->warehouseService->findAll();
    //     return $this->warehouseExportService->export($data);
    // }

    // #[Route('/warehouse/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->warehouseImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/warehouse/template-import', methods: ['GET'])]
    // public function downloadTemplate(WarehouseTemplateImportService $service): Response
    // {
    //     return $service->generateWarehouseTemplate();
    // }
}
