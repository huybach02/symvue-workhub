<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\BranchDTO;
use App\Service\BranchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BranchController extends AbstractController
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    #[Route('/branch', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->branchService->findAll($params);
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

    #[Route('/branch/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->branchService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/branch', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] BranchDTO $branchDTO
    ): JsonResponse {
        try {
            $data = $this->branchService->create($branchDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/branch/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] BranchDTO $branchDTO
    ): JsonResponse {
        try {
            $data = $this->branchService->update($id, $branchDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/branch/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->branchService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/branch/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->branchService->findAll();
    //     return $this->branchExportService->export($data);
    // }

    // #[Route('/branch/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->branchImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/branch/template-import', methods: ['GET'])]
    // public function downloadTemplate(BranchTemplateImportService $service): Response
    // {
    //     return $service->generateBranchTemplate();
    // }
}
