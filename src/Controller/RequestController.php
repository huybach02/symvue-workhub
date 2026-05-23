<?php

declare(strict_types=1);

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\RequestActionDTO;
use App\DTO\RequestCreateDTO;
use App\DTO\RequestUpdateDTO;
use App\Entity\User;
use App\Service\RequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class RequestController extends AbstractController
{
    public function __construct(
        private readonly RequestService $requestService,
    ) {}

    #[Route('/request-types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        try {
            return CustomResponse::success($this->requestService->getTypes());
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests', methods: ['GET'])]
    public function getAll(HttpRequest $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $params = validateFilterParams($request->query->all());
            $result = $this->requestService->getAll($params, $currentUser);

            return CustomResponse::success([
                'collection' => $result['collection'],
                'total' => $result['total'],
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to'],
                    'total_current' => $result['total_current'],
                ],
            ]);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] RequestCreateDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $item = $this->requestService->create($dto, $currentUser);
            return CustomResponse::success($item, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            return CustomResponse::success($this->requestService->findById($id, $currentUser));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload] RequestUpdateDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $item = $this->requestService->update($id, $dto, $currentUser);
            return CustomResponse::success($item, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}/approve', methods: ['PATCH'])]
    public function approve(
        int $id,
        #[MapRequestPayload] RequestActionDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $item = $this->requestService->approve($id, $dto, $currentUser);
            return CustomResponse::success($item, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}/reject', methods: ['PATCH'])]
    public function reject(
        int $id,
        #[MapRequestPayload] RequestActionDTO $dto,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $item = $this->requestService->reject($id, $dto, $currentUser);
            return CustomResponse::success($item, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}/cancel', methods: ['PATCH'])]
    public function cancel(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $item = $this->requestService->cancel($id, $currentUser);
            return CustomResponse::success($item, t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->requestService->delete($id, $currentUser);
            return CustomResponse::success([], t('success.deleted'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/requests/{id}/timeline', methods: ['GET'])]
    public function timeline(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            return CustomResponse::success($this->requestService->getTimeline($id, $currentUser));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
