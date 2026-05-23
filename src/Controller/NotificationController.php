<?php

namespace App\Controller;

use App\Class\CustomResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\MercureService;
use Symfony\Component\HttpFoundation\Request;

class NotificationController extends AbstractController
{
    public function __construct(
        private MercureService $mercureService
    ) {}


    #[Route('/notifications', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        /** @var User $user */
        $user = $this->getUser();

        try {
            $result = $this->mercureService->findAll($params, $user);
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

    #[Route('/mercure/notification-list/{userId}', name: 'mercure_notification_list', methods: ['GET'])]
    public function danhSachThongBao(int $userId): JsonResponse
    {
        $thongBao = $this->mercureService->danhSachThongBao($userId);

        return CustomResponse::success($thongBao, "Lấy danh sách thông báo thành công!");
    }

    #[Route('/mercure/notification-list/{userId}/read/{code}', name: 'mercure_notification_list_read_one', methods: ['GET'], priority: -1)]
    public function markOneRead(int $userId, string $code): JsonResponse
    {
        $result = $this->mercureService->markOneRead($userId, $code);

        return CustomResponse::success($result, "Đọc một thông báo thành công!");
    }

    #[Route('/mercure/notification-list/{userId}/read-all', name: 'mercure_notification_list_read_all', methods: ['GET'], priority: -1)]
    public function markAllRead(int $userId): JsonResponse
    {
        $result = $this->mercureService->markAllRead($userId);

        return CustomResponse::success($result, "Đọc tất cả thông báo thành công!");
    }

    #[Route('/notifications', name: 'notifications', methods: ['POST'])]
    public function thongBao(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $data['fromId'] = $user->getId();
        $thongBao = $this->mercureService->thongBao($data);

        return CustomResponse::success($thongBao, "Gửi thông báo realtime thành công!");
    }

    #[Route('/mercure/test', name: 'mercure_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        $this->mercureService->thongBaoTest();

        return CustomResponse::success([], "Gửi thông báo thành công!");
    }

    #[Route('/mercure/system-notification', name: 'mercure_system_notification', methods: ['POST'])]
    public function thongBaoHeThong(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoHeThong($user->getId(), "Thông báo", "Đây là thông báo hệ thống");

        return CustomResponse::success([], "Gửi thông báo thành công!");
    }

    #[Route('/mercure/user-notification/{userId}', name: 'mercure_user_notification', methods: ['POST'])]
    public function thongBaoDenUser($userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoCaNhan($user->getId(), $userId, "Thông báo", "Đây là thông báo hệ thống đến người dùng " . $userId . uniqid());

        return CustomResponse::success([], "Gửi thông báo thành công!");
    }

    #[Route('/mercure/department-notification/{departmentId}', name: 'mercure_department_notification', methods: ['POST'])]
    public function thongBaoDenPhongBan($departmentId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoPhongBan($user->getId(), $departmentId, "Thông báo", "Đây là thông báo hệ thống đến phòng ban " . $departmentId . uniqid());

        return CustomResponse::success([], "Gửi thông báo thành công!");
    }
}
