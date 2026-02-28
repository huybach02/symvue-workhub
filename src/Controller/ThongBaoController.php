<?php

namespace App\Controller;

use App\Class\CustomResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\MercureService;

class ThongBaoController extends AbstractController
{
    public function __construct(
        private MercureService $mercureService
    ) {}

    #[Route('/mercure/danh-sach-thong-bao/{userId}', name: 'mercure_danh_sach_thong_bao', methods: ['GET'])]
    public function danhSachThongBao(int $userId): JsonResponse
    {
        $thongBao = $this->mercureService->danhSachThongBao($userId);

        return CustomResponse::success($thongBao, "Lấy danh sách thông báo thành công!");
    }

    #[Route('/mercure/danh-sach-thong-bao/{userId}/read-one/{code}', name: 'mercure_danh_sach_thong_bao_doc_mot_thong_bao', methods: ['GET'], priority: -1)]
    public function markOneRead(int $userId, string $code): JsonResponse
    {
        $result = $this->mercureService->markOneRead($userId, $code);

        return CustomResponse::success($result, "Đọc một thông báo thành công!");
    }

    #[Route('/mercure/danh-sach-thong-bao/{userId}/real-all', name: 'mercure_danh_sach_thong_bao_doc_tat_ca', methods: ['GET'], priority: -1)]
    public function markAllRead(int $userId): JsonResponse
    {
        $result = $this->mercureService->markAllRead($userId);

        return CustomResponse::success($result, "Đọc tất cả thông báo thành công!");
    }

    #[Route('/mercure/test', name: 'mercure_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        $this->mercureService->thongBaoTest();

        return CustomResponse::success([], "Gửi thông báo realtime thành công!");
    }

    #[Route('/mercure/thong-bao-he-thong', name: 'mercure_thong_bao_he_thong', methods: ['POST'])]
    public function thongBaoHeThong(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoHeThong($user->getId(), "Thông báo", "Đây là thông báo hệ thống");

        return CustomResponse::success([], "Gửi thông báo realtime thành công!");
    }

    #[Route('/mercure/thong-bao-ca-nhan/{userId}', name: 'mercure_thong_bao_ca_nhan', methods: ['POST'])]
    public function thongBaoDenUser($userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoCaNhan($user->getId(), $userId, "Thông báo", "Đây là thông báo hệ thống đến người dùng " . $userId . uniqid());

        return CustomResponse::success([], "Gửi thông báo realtime thành công!");
    }

    #[Route('/mercure/thong-bao-phong-ban/{departmentId}', name: 'mercure_thong_bao_phong_ban', methods: ['POST'])]
    public function thongBaoDenPhongBan($departmentId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->mercureService->thongBaoPhongBan($user->getId(), $departmentId, "Thông báo", "Đây là thông báo hệ thống đến phòng ban " . $departmentId . uniqid());

        return CustomResponse::success([], "Gửi thông báo realtime thành công!");
    }
}
