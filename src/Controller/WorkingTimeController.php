<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\WorkShiftDTO;
use App\DTO\WorkShiftStatusDTO;
use App\DTO\WorkingTimeDTO;
use App\Service\WorkingTimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class WorkingTimeController extends AbstractController
{
    public function __construct(
        private readonly WorkingTimeService $thoiGianLamViecService,
    ) {}

    #[Route("/thoi-gian-lam-viec", methods: ["GET"])]
    public function getAll(Request $request): JsonResponse
    {
        $type = $request->query->get("type");
        $id = $request->query->get("id");
        $thoiGianLamViecId = $request->query->get("thoiGianLamViecId");

        try {
            if ($type === "fulltime") {
                if ($id !== null) {
                    $thoiGianLamViec = $this->thoiGianLamViecService->findById(
                        (int) $id,
                    );
                    return CustomResponse::success($thoiGianLamViec);
                }

                $thoiGianLamViecList = $this->thoiGianLamViecService->findAll();
                return CustomResponse::success($thoiGianLamViecList);
            }

            if ($type === "parttime") {
                $thoiGianLamViecList = $this->thoiGianLamViecService->findAllParttimeByThoiGianLamViecId(
                    (int) $thoiGianLamViecId,
                );
                return CustomResponse::success($thoiGianLamViecList);
            }

            return CustomResponse::error(t("error.param_invalid"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/thoi-gian-lam-viec", methods: ["PUT"])]
    public function updateFulltime(
        Request $request,
        #[MapRequestPayload] WorkingTimeDTO $thoiGianLamViecDTO,
    ): JsonResponse {
        $id = $request->query->get("id");

        try {
            $thoiGianLamViec = $this->thoiGianLamViecService->updateFulltime(
                (int) $id,
                $thoiGianLamViecDTO,
            );
            return CustomResponse::success(
                $thoiGianLamViec,
                t("success.updated"),
            );
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/thoi-gian-lam-viec", methods: ["POST"])]
    public function createParttime(
        #[MapRequestPayload] WorkShiftDTO $caLamViecDTO,
    ): JsonResponse {
        try {
            $thoiGianLamViec = $this->thoiGianLamViecService->createParttime(
                $caLamViecDTO,
            );
            return CustomResponse::success(
                $thoiGianLamViec,
                t("success.created"),
            );
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/thoi-gian-lam-viec/ca-lam-viec", methods: ["PUT"])]
    public function updateParttime(
        Request $request,
        #[MapRequestPayload] WorkShiftDTO $caLamViecDTO,
    ): JsonResponse {
        $id = $request->query->get("id");

        try {
            $caLamViec = $this->thoiGianLamViecService->updateParttime(
                (int) $id,
                $caLamViecDTO,
            );
            return CustomResponse::success($caLamViec, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/thoi-gian-lam-viec/ca-lam-viec/status", methods: ["PUT"])]
    public function updateParttimeStatus(
        Request $request,
        #[MapRequestPayload] WorkShiftStatusDTO $dto,
    ): JsonResponse {
        $id = $request->query->get("id");

        try {
            $caLamViec = $this->thoiGianLamViecService->updateParttimeStatus(
                (int) $id,
                $dto,
            );
            return CustomResponse::success($caLamViec, t("success.updated"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
