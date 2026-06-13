<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\AttendanceDTO;
use App\Entity\User;
use App\Service\AttendanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AttendanceController extends AbstractController
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    #[Route("/attendance/qr-display-access", methods: ["GET"])]
    public function createQrDisplayAccess(): JsonResponse
    {
        try {
            $data = $this->attendanceService->createQrDisplayAccess();
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/attendance/qr-display-access/revoke", methods: ["POST"])]
    public function revokeQrDisplayAccess(): JsonResponse
    {
        try {
            $this->attendanceService->revokeQrDisplayAccess();
            return CustomResponse::success(
                [],
                "Đã thu hồi quyền truy cập trang QR chấm công.",
            );
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/attendance/qr-attendance", methods: ["GET"])]
    public function getQrAttendance(Request $request): JsonResponse
    {
        try {
            $accessToken = (string) $request->query->get("access", "");
            $data = $this->attendanceService->getQrAttendance($accessToken);
            return CustomResponse::success($data);
        } catch (\InvalidArgumentException $th) {
            return CustomResponse::error($th->getMessage(), [], 403);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/attendance/verify", methods: ["POST"])]
    public function verifyAttendance(
        Request $request,
        #[
            MapRequestPayload(validationGroups: ["create"]),
        ]
        AttendanceDTO $attendanceDTO,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->attendanceService->verifyAttendance($request, $attendanceDTO, $currentUser);
            return CustomResponse::success($data, t("success.created"));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/attendance", methods: ["GET"])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->attendanceService->findAll($params);
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

    #[Route("/attendance/{id}", methods: ["GET"], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->attendanceService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
