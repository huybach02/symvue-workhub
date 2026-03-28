<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\DepartmentDTO;
use App\DTO\PositionDTO;
use App\Service\DepartmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly DepartmentService $boPhanService,
    ) {}

    #[Route('/bo-phan', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->boPhanService->findAll($params);
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

    #[Route('/bo-phan/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->boPhanService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] DepartmentDTO $boPhanDTO
    ): JsonResponse {
        try {
            $data = $this->boPhanService->create($boPhanDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] DepartmentDTO $boPhanDTO
    ): JsonResponse {
        try {
            $data = $this->boPhanService->update($id, $boPhanDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->boPhanService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/select', methods: ['GET'])]
    public function getDataSelect(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->boPhanService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/permission', methods: ['GET'])]
    public function getPermission(): JsonResponse
    {
        try {
            $data = require __DIR__ . '/../../config/permission.php';
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}/chuc-vu', methods: ['GET'])]
    public function getPositions(int $id): JsonResponse
    {
        try {
            $data = $this->boPhanService->getPositions($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}/chuc-vu', methods: ['POST'])]
    public function createPosition(
        int $id,
        #[MapRequestPayload(validationGroups: ['create'])] PositionDTO $positionDTO
    ): JsonResponse
    {
        try {
            $data = $this->boPhanService->createPosition($id, $positionDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}/chuc-vu/{positionId}', methods: ['PUT'])]
    public function updatePosition(
        int $id,
        int $positionId,
        #[MapRequestPayload(validationGroups: ['update'])] PositionDTO $positionDTO
    ): JsonResponse
    {
        try {
            $data = $this->boPhanService->updatePosition($id, $positionId, $positionDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}/chuc-vu/{positionId}', methods: ['DELETE'])]
    public function deletePosition(int $id, int $positionId): JsonResponse
    {
        try {
            $this->boPhanService->deletePosition($id, $positionId);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/bo-phan/{id}/phan-quyen', methods: ['PUT'])]
    public function updatePositionPermission(int $id, Request $request): JsonResponse
    {
        try {
            $body = $request->toArray();
            $data = $this->boPhanService->updatePositionPermissions($id, $body['phanQuyen'] ?? []);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/bo-phan/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->boPhanService->findAll();
    //     return $this->boPhanExportService->export($data);
    // }

    // #[Route('/bo-phan/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->boPhanImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/bo-phan/template-import', methods: ['GET'])]
    // public function downloadTemplate(BoPhanTemplateImportService $service): Response
    // {
    //     return $service->generateBoPhanTemplate();
    // }
}
