<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\WorkScheduleFulltimeDTO;
use App\DTO\WorkScheduleFulltimeOverrideDTO;
use App\Service\WorkScheduleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WorkScheduleController extends AbstractController
{
    public function __construct(
        private readonly WorkScheduleService $workScheduleService,
    ) {}

    #[Route("/work-schedule/holiday-schedule", methods: ["GET"])]
    public function getHolidaySchedule(): JsonResponse
    {
        try {
            $data = $this->workScheduleService->getHolidaySchedule();
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/fulltime/{departmentId}", methods: ["GET"], priority: -1)]
    public function getFulltime(int $departmentId): JsonResponse
    {
        try {
            $data = $this->workScheduleService->getFulltime($departmentId);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/fulltime", methods: ["POST"])]
    public function createFulltime(
        #[
            MapRequestPayload(validationGroups: ["create"]),
        ]
        WorkScheduleFulltimeDTO $dto,
    ): JsonResponse {
        try {
            $data = $this->workScheduleService->createFulltime($dto);
            return CustomResponse::success($data, t("success.created"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/fulltime/clear", methods: ["POST"])]
    public function clearFulltime(
        Request $request,
    ): JsonResponse {
        try {
            $body = $request->toArray();

            $data = $this->workScheduleService->clearFulltime($body["departmentId"], $body["userId"]);
            return CustomResponse::success($data, t("success.deleted"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/fulltime/check-override", methods: ["POST"])]
    public function checkOverrideFulltime(
        Request $request,
    ): JsonResponse {
        try {
            $body = $request->toArray();
            $data = $this->workScheduleService->checkOverrideFulltime($body["userId"], $body["startDate"], $body["endDate"]);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/special-days", methods: ["GET"])]
    public function getSpecialDays(
        Request $request,
    ): JsonResponse {
        try {
            $startDate = $request->query->get("startDate");
            $endDate = $request->query->get("endDate");
            $data = $this->workScheduleService->getSpecialDays($startDate, $endDate);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/work-schedule/fulltime/override", methods: ["POST"])]
    public function overrideFulltime(
        #[
            MapRequestPayload(validationGroups: ["create"]),
        ]
        WorkScheduleFulltimeOverrideDTO $dto,
    ): JsonResponse {
        try {
            $data = $this->workScheduleService->overrideFulltime($dto);
            return CustomResponse::success($data, t("success.created"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route("/work-schedule/{id}", methods: ["PUT"])]
    // public function update(
    //     int $id,
    //     #[
    //         MapRequestPayload(validationGroups: ["update"]),
    //     ]
    //     WorkScheduleDTO $exampleDTO,
    // ): JsonResponse {
    //     try {
    //         $data = $this->exampleService->update($id, $exampleDTO);
    //         return CustomResponse::success($data, t("success.updated"));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route("/work-schedule/{id}", methods: ["DELETE"])]
    // public function delete(int $id): JsonResponse
    // {
    //     try {
    //         $this->exampleService->delete($id);
    //         return CustomResponse::success([], t("success.deleted"));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }
}
