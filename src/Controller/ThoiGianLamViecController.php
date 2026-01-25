<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\CaLamViecDTO;
use App\DTO\ThoiGianLamViecDTO;
use App\Service\ThoiGianLamViecService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ThoiGianLamViecController extends AbstractController
{
    public function __construct(
        private readonly ThoiGianLamViecService $thoiGianLamViecService,
    ) {}

    #[Route('/thoi-gian-lam-viec/fulltime', methods: ['GET'])]
    public function getAllFulltime(): JsonResponse
    {
        try {
            $thoiGianLamViecList = $this->thoiGianLamViecService->findAll();
            return CustomResponse::success($thoiGianLamViecList);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/thoi-gian-lam-viec/fulltime/{id}', methods: ['GET'])]
    public function getFulltimeById(int $id): JsonResponse
    {
        try {
            $thoiGianLamViec = $this->thoiGianLamViecService->findById($id);
            return CustomResponse::success($thoiGianLamViec);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/thoi-gian-lam-viec/fulltime/{id}', methods: ['PUT'])]
    public function updateFulltime(
        int $id,
        #[MapRequestPayload] ThoiGianLamViecDTO $thoiGianLamViecDTO
    ): JsonResponse {
        try {
            $thoiGianLamViec = $this->thoiGianLamViecService->updateFulltime($id, $thoiGianLamViecDTO);
            return CustomResponse::success($thoiGianLamViec, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/thoi-gian-lam-viec/parttime/{thoiGianLamViecId}', methods: ['GET'])]
    public function getAllParttime(int $thoiGianLamViecId): JsonResponse
    {
        try {
            $thoiGianLamViecList = $this->thoiGianLamViecService->findAllParttimeByThoiGianLamViecId($thoiGianLamViecId);
            return CustomResponse::success($thoiGianLamViecList);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/thoi-gian-lam-viec/parttime', methods: ['POST'])]
    public function createParttime(
        #[MapRequestPayload] CaLamViecDTO $caLamViecDTO
    ): JsonResponse {
        try {
            $thoiGianLamViec = $this->thoiGianLamViecService->createParttime($caLamViecDTO);
            return CustomResponse::success($thoiGianLamViec, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
