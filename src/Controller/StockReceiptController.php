<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\StockReceiptDTO;
use App\DTO\StockReceiptInspectingDTO;
use App\DTO\StockReceiptProviderStatusDTO;
use App\Service\StockReceiptService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StockReceiptController extends AbstractController
{
    public function __construct(
        private readonly StockReceiptService $stockReceiptService,
    ) {}

    #[Route("/stock-receipt", methods: ["GET"])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->stockReceiptService->findAll($params);
            return CustomResponse::success([
                "collection" => $result["collection"],
                "total" => $result["total"],
                "pagination" => [
                    "current_page" => $result["current_page"],
                    "last_page" => $result["last_page"],
                    "from" => $result["from"],
                    "to" => $result["to"],
                    "total_current" => $result["total_current"],
                ],
            ]);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/select", methods: ["GET"])]
    public function select(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->stockReceiptService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/{id}", methods: ["GET"], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->stockReceiptService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt", methods: ["POST"])]
    public function create(
        #[
            MapRequestPayload(validationGroups: ["create"]),
        ]
        StockReceiptDTO $stockReceiptDTO,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->stockReceiptService->create(
                $stockReceiptDTO,
                $currentUser,
            );
            return CustomResponse::success($data, t("success.created"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/{id}", methods: ["PUT"])]
    public function update(
        int $id,
        #[
            MapRequestPayload(validationGroups: ["update"]),
        ]
        StockReceiptDTO $stockReceiptDTO,
    ): JsonResponse {
        try {
            $data = $this->stockReceiptService->update($id, $stockReceiptDTO);
            return CustomResponse::success($data, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/provider/{id}/status", methods: ["PUT"])]
    public function updateProviderStatus(
        int $id,
        #[MapRequestPayload] StockReceiptProviderStatusDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->stockReceiptService->updateProviderStatus(
                $id,
                $dto->status,
                $currentUser,
            );
            return CustomResponse::success($data, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/{id}", methods: ["DELETE"])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->stockReceiptService->delete($id);
            return CustomResponse::success([], t("success.deleted"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/stock-receipt/inspecting", methods: ["POST"])]
    public function inspecting(
        #[
            MapRequestPayload(validationGroups: ["create"]),
        ]
        StockReceiptInspectingDTO $stockReceiptInspectingDTO,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->stockReceiptService->inspecting(
                $stockReceiptInspectingDTO,
                $currentUser,
            );
            return CustomResponse::success($data, t("success.created"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/stock-receipt/export', methods: ['GET'])]
    // public function export(): Response
    // {
    //     $data = $this->stockReceiptService->findAll();
    //     return $this->stockReceiptExportService->export($data);
    // }

    // #[Route('/stock-receipt/import', methods: ['POST'])]
    // public function import(Request $request): JsonResponse
    // {
    //     $file = $request->files->get('file');
    //     if (!$file) {
    //         return CustomResponse::error(t('error.file_not_found'));
    //     }

    //     try {
    //         $errorCount = $this->stockReceiptImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
    //         if ($errorCount > 0) {
    //             return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
    //         }
    //         return CustomResponse::success([], t('success.imported'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/stock-receipt/template-import', methods: ['GET'])]
    // public function downloadTemplate(StockReceiptTemplateImportService $service): Response
    // {
    //     return $service->generateStockReceiptTemplate();
    // }
}
