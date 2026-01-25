<?php

namespace App\Service;

use App\Class\CustomResponse;
use App\DTO\CaLamViecDTO;
use App\DTO\ThoiGianLamViecDTO;
use App\Entity\CaLamViec;
use App\Entity\ThoiGianLamViec;
use App\Repository\CaLamViecRepository;
use App\Repository\ThoiGianLamViecRepository;
use Doctrine\ORM\EntityManagerInterface;

class ThoiGianLamViecService
{
    public function __construct(
        private readonly ThoiGianLamViecRepository $thoiGianLamViecRepository,
        private readonly CaLamViecRepository $caLamViecRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(): array
    {
        // Order by bắt buộc phải theo thứ tự Thứ 2 đến Chủ Nhật
        $thoiGianLamViecList = $this->thoiGianLamViecRepository->findBy([], ['id' => 'ASC']);

        return array_map(
            fn(ThoiGianLamViec $item) => $item->jsonSerialize(),
            $thoiGianLamViecList
        );
    }

    public function findById(int $id): array
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find($id);

        return $thoiGianLamViec->jsonSerialize();
    }

    public function updateFulltime(int $id, ThoiGianLamViecDTO $thoiGianLamViecDTO)
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find($id);
        if (!$thoiGianLamViec) {
            throw new \Exception("ThoiGianLamViec not found");
        }
        $thoiGianLamViec->setGioBatDau($thoiGianLamViecDTO->gioBatDau);
        $thoiGianLamViec->setGioKetThuc($thoiGianLamViecDTO->gioKetThuc);
        $thoiGianLamViec->setGhiChu($thoiGianLamViecDTO->ghiChu);

        $this->entityManager->flush();

        return $thoiGianLamViec->jsonSerialize();
    }

    public function createParttime(CaLamViecDTO $caLamViecDTO)
    {
        $thoiGianLamViec = $this->thoiGianLamViecRepository->find($caLamViecDTO->thoiGianLamViecId);
        if (!$thoiGianLamViec) {
            throw new \Exception(t("error.not_found"));
        }

        // Kiểm tra ca làm việc đã tồn tại hay chưa
        $caLamViec = $this->caLamViecRepository->findOneBy([
            'thoiGianLamViec' => $thoiGianLamViec,
            'gioBatDau' => $caLamViecDTO->gioBatDau,
            'gioKetThuc' => $caLamViecDTO->gioKetThuc
        ]);
        if ($caLamViec) {
            throw new \Exception(t("error.exists"));
        }

        $caLamViec = new CaLamViec();
        $caLamViec->setThoiGianLamViec($thoiGianLamViec);
        $caLamViec->setGioBatDau($caLamViecDTO->gioBatDau);
        $caLamViec->setGioKetThuc($caLamViecDTO->gioKetThuc);
        $caLamViec->setGhiChu($caLamViecDTO->ghiChu);

        $this->entityManager->persist($caLamViec);
        $this->entityManager->flush();

        return $caLamViec->jsonSerialize();
    }

    public function findAllParttimeByThoiGianLamViecId(int $thoiGianLamViecId): array
    {
        $caLamViecList = $this->caLamViecRepository->findBy([
            'thoiGianLamViec' => $thoiGianLamViecId
        ], ['gioBatDau' => 'ASC', 'gioKetThuc' => 'ASC']);

        return array_map(
            fn(CaLamViec $item) => $item->jsonSerialize(),
            $caLamViecList
        );
    }
}
