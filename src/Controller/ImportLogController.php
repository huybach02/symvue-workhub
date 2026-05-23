<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\ImportLogDTO;
use App\Service\ImportLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ImportLogController extends AbstractController
{
    public function __construct(
        private readonly ImportLogService $importLogService,
    ) {}

    #[Route('/import-history', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);
        $currentUser = $this->getUser();

        try {
            $result = $this->importLogService->findAll($params, $currentUser);
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

    #[Route('/import-history/{id}', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->importLogService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
