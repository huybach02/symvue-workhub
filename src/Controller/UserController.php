<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\UserDTO;
use App\DTO\UserPositionDTO;
use App\Repository\UserRepository;
use App\Service\Excel\Export\UserExportService;
use App\Service\Excel\Import\UserImportService;
use App\Service\Excel\Template\UserTemplateImportService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserExportService $userExportService,
        private readonly UserImportService $userImportService,
        private readonly UserRepository $userRepository
    ) {}

    #[Route('/nguoi-dung', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->userService->findAll($params);
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

    #[Route('/nguoi-dung/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->userService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] UserDTO $userDTO
    ): JsonResponse {
        try {
            $data = $this->userService->create($userDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] UserDTO $userDTO
    ): JsonResponse {
        try {
            $data = $this->userService->update($id, $userDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/select', methods: ['GET'])]
    public function getDataSelect(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $data = $this->userService->getDataSelect($params);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/get-ma-nhan-vien', methods: ['GET'])]
    public function getMaNhanVien(): JsonResponse
    {
        try {
            $data = $this->userService->getMaNhanVien();
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/export', methods: ['GET'])]
    public function exportUsers(): Response
    {
        $users = $this->userRepository->findAll();
        return $this->userExportService->export($users);
    }

    #[Route('/nguoi-dung/import', methods: ['POST'])]
    public function importUsers(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return CustomResponse::error(t('error.file_not_found'));
        }

        try {
            $errorCount = $this->userImportService->import($file->getPathname(), $file->getClientOriginalName(), $this->getUser());
            if ($errorCount > 0) {
                return CustomResponse::error(t('error.imported_with_errors', ['%count%' => $errorCount]));
            }
            return CustomResponse::success([], t('success.imported'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/template-import', methods: ['GET'])]
    public function downloadTemplate(UserTemplateImportService $service): Response
    {
        return $service->generateUserTemplate();
    }

    // API lấy data province
    #[Route('/nguoi-dung/province', methods: ['GET'])]
    public function getProvince(): JsonResponse
    {
        try {
            $data = $this->userService->getProvince();
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // API lấy data ward
    #[Route('/nguoi-dung/ward/{provinceId}', methods: ['GET'])]
    public function getWard(int $provinceId): JsonResponse
    {
        try {
            $data = $this->userService->getWard($provinceId);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/{id}/vi-tri-cong-viec', methods: ['GET'])]
    public function getUserPosition(int $id): JsonResponse
    {
        try {
            $data = $this->userService->getUserPosition($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/{id}/vi-tri-cong-viec', methods: ['PUT'])]
    public function saveUserPosition(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] UserPositionDTO $userPositionDTO
    ): JsonResponse {
        try {
            $data = $this->userService->saveUserPosition($id, $userPositionDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/nguoi-dung/{id}/hop-dong', methods: ['POST'])]
    public function uploadUserContracts(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->userService->uploadUserContracts($id, $request);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
