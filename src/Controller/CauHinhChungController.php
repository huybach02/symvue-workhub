<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\DTO\CauHinhChungDTO;
use App\Service\CauHinhChungService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CauHinhChungController extends AbstractController
{
    public function __construct(
        private readonly CauHinhChungService $cauHinhChungService,
    ) {}

    #[Route("/cau-hinh-chung", name: "api_cau_hinh_chung", methods: ["GET"])]
    public function index(): Response
    {
        try {
            $cauHinhChungList = $this->cauHinhChungService->findAll();
            return CustomResponse::success($cauHinhChungList);
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }

    #[Route("/cau-hinh-chung", name: "api_cau_hinh_chung_update", methods: ["POST"])]
    public function update(
        #[MapRequestPayload] CauHinhChungDTO $cauHinhChungDTO
    ) {
        try {
            $this->cauHinhChungService->update($cauHinhChungDTO);
            return CustomResponse::success([], t('success.updated'));
        } catch (\Throwable $th) {
            return CustomResponse::error($th->getMessage());
        }
    }
}
