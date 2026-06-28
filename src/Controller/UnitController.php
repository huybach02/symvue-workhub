<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\UnitDTO;
use App\Service\UnitService;
use App\Repository\UnitRepository;
use App\Service\Excel\Export\UnitExportService;
use App\Service\Excel\Import\UnitImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UnitController extends AbstractController
{
    public function __construct(
        private readonly UnitService $unitService,
        private readonly UnitRepository $unitRepository,
        private readonly UnitExportService $unitExportService,
        private readonly UnitImportService $unitImportService,
    ) {}

    #[Route('/unit', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->unitService->findAll($params);
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

    #[Route('/unit/select', methods: ['GET'])]
    public function getDataSelect(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->unitService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->unitService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] UnitDTO $unitDTO
    ): JsonResponse {
        try {
            $data = $this->unitService->create($unitDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] UnitDTO $unitDTO
    ): JsonResponse {
        try {
            $data = $this->unitService->update($id, $unitDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->unitService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/export', methods: ['GET'])]
    public function export(): Response
    {
        try {
            $data = $this->unitRepository->findAll();
            return $this->unitExportService->export($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return CustomResponse::error(t('error.file_not_found'));
        }

        try {
            $errorCount = $this->unitImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
            if ($errorCount > 0) {
                return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
            }
            return CustomResponse::success([], t('success.imported'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/unit/template-import', methods: ['GET'])]
    public function downloadTemplate(
        \App\Service\Excel\Template\UnitTemplateImportService $service
    ): Response {
        try {
            return $service->generateUnitTemplate();
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
