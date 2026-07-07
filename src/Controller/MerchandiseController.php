<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\MerchandiseDTO;
use App\Service\MerchandiseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MerchandiseController extends AbstractController
{
    public function __construct(
        private readonly MerchandiseService $merchandiseService,
    ) {}

    #[Route('/merchandise', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->merchandiseService->findAll($params);
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

    #[Route('/merchandise/select', methods: ['GET'])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->merchandiseService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/merchandise/get-price-by-unit', methods: ['POST'])]
    public function getPriceMerchandiseByUnitId(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        try {
            $data = $this->merchandiseService->getPriceByUnitId(
                $data['merchandiseId'],
                $data['unitId']
            );
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/merchandise/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->merchandiseService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/merchandise', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] MerchandiseDTO $merchandiseDTO
    ): JsonResponse {
        try {
            $data = $this->merchandiseService->create($merchandiseDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/merchandise/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] MerchandiseDTO $merchandiseDTO
    ): JsonResponse {
        try {
            $data = $this->merchandiseService->update($id, $merchandiseDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/merchandise/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->merchandiseService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/merchandise/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->merchandiseService->findAll();
    //     return $this->merchandiseExportService->export($data);
    // }

    // #[Route('/merchandise/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->merchandiseImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/merchandise/template-import', methods: ['GET'])]
    // public function downloadTemplate(MerchandiseTemplateImportService $service): Response
    // {
    //     return $service->generateMerchandiseTemplate();
    // }
}
