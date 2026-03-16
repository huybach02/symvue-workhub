<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\GeneralSettingDTO;
use App\Service\GeneralSettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class GeneralSettingController extends AbstractController
{
    public function __construct(
        private readonly GeneralSettingService $cauHinhChungService,
    ) {}

    #[Route("/cau-hinh-chung", methods: ["GET"])]
    public function index(): Response
    {
        try {
            $cauHinhChungList = $this->cauHinhChungService->findAll();
            return CustomResponse::success($cauHinhChungList);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/cau-hinh-chung", methods: ["POST"])]
    public function update(
        #[MapRequestPayload] GeneralSettingDTO $cauHinhChungDTO
    ) {
        try {
            $this->cauHinhChungService->update($cauHinhChungDTO);
            return CustomResponse::success([], t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
