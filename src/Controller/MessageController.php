<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\MessageDTO;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    // #[Route('/message', methods: ['GET'])]
    // public function getAll(Request $request): JsonResponse
    // {
    //     $params = $request->query->all();
    //     $params = validateFilterParams($params);

    //     try {
    //         $result = $this->messageService->findAll($params);
    //         return CustomResponse::success([
    //             'collection' => $result['collection'],
    //             'total' => $result['total'],
    //             'pagination' => [
    //                 'current_page' => $result['current_page'],
    //                 'last_page' => $result['last_page'],
    //                 'from' => $result['from'],
    //                 'to' => $result['to'],
    //                 'total_current' => $result['total_current'],
    //             ]
    //         ]);
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    #[Route('/message/conversation/{id}', methods: ['GET'], priority: -1)]
    public function getOne(int $id): JsonResponse
    {
        try {
            $data = $this->messageService->findByConversation($id);
            return CustomResponse::success($data);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route('/message', methods: ['POST'])]
    public function create(
        Request $request,
        #[MapRequestPayload(validationGroups: ['create'])] MessageDTO $messageDTO
    ): JsonResponse {

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $messageDTO->imageFiles = $request->files->all('imageFiles');
        $messageDTO->files      = $request->files->all('files');

        try {
            $data = $this->messageService->create($request, $messageDTO, $currentUser);
            return CustomResponse::success($data, t('success.created'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    // #[Route('/message/{id}', methods: ['PUT'])]
    // public function update(
    //     int $id,
    //     #[MapRequestPayload(validationGroups: ['update'])] MessageDTO $messageDTO
    // ): JsonResponse {
    //     try {
    //         $data = $this->messageService->update($id, $messageDTO);
    //         return CustomResponse::success($data, t('success.updated'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }

    // #[Route('/message/{id}', methods: ['DELETE'])]
    // public function delete(int $id): JsonResponse
    // {
    //     try {
    //         $this->messageService->delete($id);
    //         return CustomResponse::success([], t('success.deleted'));
    //     } catch (\Throwable $th) {
    //         return CustomResponse::error($th->getMessage());
    //     }
    // }
}
