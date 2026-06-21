<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\CategoryDTO;
use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    #[Route('/category', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $params = validateFilterParams($params);

        try {
            $result = $this->categoryService->findAll($params);
            return CustomResponse::success($result);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/category/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->categoryService->findById($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/category', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] CategoryDTO $categoryDTO
    ): JsonResponse {
        try {
            $data = $this->categoryService->create($categoryDTO);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/category/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload(validationGroups: ['update'])] CategoryDTO $categoryDTO
    ): JsonResponse {
        try {
            $data = $this->categoryService->update($id, $categoryDTO);
            return CustomResponse::success($data, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/category/move/{id}', methods: ['PATCH'])]
    public function move(int $id, Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);

            $this->categoryService->move(
                categoryId: $id,
                newParentId: $payload['newParentId'] ?? null,
                targetOrderedIds: $payload['targetOrderedIds'] ?? [],
                sourceOrderedIds: $payload['sourceOrderedIds'] ?? null,
            );

            return CustomResponse::success([], t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/category/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->categoryService->delete($id);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
