<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\CustomResponse;
use App\DTO\WorkShiftDTO;
use App\DTO\WorkingTimeDTO;
use App\Entity\WorkShift;
use App\Entity\WorkingTime;
use App\Repository\WorkShiftRepository;
use App\Repository\WorkingTimeRepository;
use Doctrine\ORM\EntityManagerInterface;

class WorkingTimeService
{
    public function __construct(
        private readonly WorkingTimeRepository $thoiGianLamViecRepository,
        private readonly WorkShiftRepository $caLamViecRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(): array
    {
        $thoiGianLamViecList = $this->thoiGianLamViecRepository->findBy(
            [],
            ["id" => "ASC"],
        );

        return array_map(
            fn(WorkingTime $item) => $item->jsonSerialize(),
            $thoiGianLamViecList,
        );
    }

    public function findById(int $id): array
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find($id);

        return $thoiGianLamViec->jsonSerialize();
    }

    public function updateFulltime(int $id, WorkingTimeDTO $thoiGianLamViecDTO)
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find($id);
        if (!$thoiGianLamViec) {
            throw new \Exception("WorkingTime not found");
        }
        $thoiGianLamViec->setGioBatDau($thoiGianLamViecDTO->gioBatDau);
        $thoiGianLamViec->setGioKetThuc($thoiGianLamViecDTO->gioKetThuc);
        $thoiGianLamViec->setGhiChu($thoiGianLamViecDTO->ghiChu);

        $this->entityManager->flush();

        return $thoiGianLamViec->jsonSerialize();
    }

    public function createParttime(WorkShiftDTO $caLamViecDTO)
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find(
            $caLamViecDTO->thoiGianLamViecId,
        );
        if (!$thoiGianLamViec) {
            throw new \Exception(t("error.not_found"));
        }

        $caLamViec = $this->caLamViecRepository->findOneBy([
            "thoiGianLamViec" => $thoiGianLamViec,
            "gioBatDau" => $caLamViecDTO->gioBatDau,
            "gioKetThuc" => $caLamViecDTO->gioKetThuc,
        ]);
        if ($caLamViec) {
            throw new \Exception(t("error.exists"));
        }

        $color = array_rand(Constanst::COLOR_SHIFT);
        
        $caLamViec = new WorkShift();
        $caLamViec->setThoiGianLamViec($thoiGianLamViec);
        $caLamViec->setGioBatDau($caLamViecDTO->gioBatDau);
        $caLamViec->setGioKetThuc($caLamViecDTO->gioKetThuc);
        $caLamViec->setColor(Constanst::COLOR_SHIFT[$color]);

        $caLamViec->setGhiChu($caLamViecDTO->ghiChu);

        $this->entityManager->persist($caLamViec);
        $this->entityManager->flush();

        return $caLamViec->jsonSerialize();
    }

    public function findAllParttimeByThoiGianLamViecId(
        int $thoiGianLamViecId,
    ): array {
        $caLamViecList = $this->caLamViecRepository->findBy(
            [
                "thoiGianLamViec" => $thoiGianLamViecId,
            ],
            ["gioBatDau" => "ASC", "gioKetThuc" => "ASC"],
        );

        return array_map(
            fn(WorkShift $item) => $item->jsonSerialize(),
            $caLamViecList,
        );
    }
}
