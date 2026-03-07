<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\ConversationDTO;
use App\Service\ConversationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    #[Route('/conversation', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->conversationService->findAll($currentUser);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/conversation/search', methods: ['GET'], priority: 1)]
    public function search(Request $request): JsonResponse
    {
        $params = $request->query->all();

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->conversationService->search($params, $currentUser);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/conversation/{id}', methods: ['GET'], priority: -1)]
    // public function getOne(int $id): JsonResponse
    // {
    //     try {
    //         $data = $this->conversationService->findById($id);
    //         return CustomResponse::success($data);
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    #[Route('/conversation', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] ConversationDTO $conversationDTO
    ): JsonResponse {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        try {
            $data = $this->conversationService->create($conversationDTO, $currentUser);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/conversation/{id}', methods: ['PUT'])]
    // public function update(
    //     int $id,
    //     #[MapRequestPayload(validationGroups: ['update'])] ConversationDTO $conversationDTO
    // ): JsonResponse {
    //     try {
    //         $data = $this->conversationService->update($id, $conversationDTO);
    //         return CustomResponse::success($data, t('success.updated'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/conversation/{id}', methods: ['DELETE'])]
    // public function delete(int $id): JsonResponse
    // {
    //     try {
    //         $this->conversationService->delete($id);
    //         return CustomResponse::success([], t('success.deleted'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }
}
