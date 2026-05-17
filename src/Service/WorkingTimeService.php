<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\CustomResponse;
use App\DTO\WorkShiftDTO;
use App\DTO\WorkShiftStatusDTO;
use App\DTO\WorkingTimeDTO;
use App\Entity\WorkShift;
use App\Entity\WorkingTime;
use App\Repository\FixedScheduleRepository;
use App\Repository\WorkShiftRepository;
use App\Repository\WorkingTimeRepository;
use Doctrine\ORM\EntityManagerInterface;

class WorkingTimeService
{
    public function __construct(
        private readonly WorkingTimeRepository $thoiGianLamViecRepository,
        private readonly WorkShiftRepository $caLamViecRepository,
        private readonly FixedScheduleRepository $fixedScheduleRepository,
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

        if ($thoiGianLamViecDTO->applyToExistingSchedules) {
            $this->updateExistingFulltimeSchedules($thoiGianLamViec);
        }

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

    public function updateParttime(int $id, WorkShiftDTO $caLamViecDTO): array
    {
        $caLamViec = $this->caLamViecRepository->find($id);
        if (!$caLamViec) {
            throw new \Exception(t("error.not_found"));
        }

        $thoiGianLamViec = $this->thoiGianLamViecRepository->find(
            $caLamViecDTO->thoiGianLamViecId,
        );
        if (!$thoiGianLamViec) {
            throw new \Exception(t("error.not_found"));
        }

        $caLamViecTrung = $this->caLamViecRepository
            ->createQueryBuilder("caLamViec")
            ->andWhere("caLamViec.id != :id")
            ->andWhere("caLamViec.thoiGianLamViec = :thoiGianLamViec")
            ->andWhere("caLamViec.gioBatDau = :gioBatDau")
            ->andWhere("caLamViec.gioKetThuc = :gioKetThuc")
            ->setParameter("id", $id)
            ->setParameter("thoiGianLamViec", $thoiGianLamViec)
            ->setParameter("gioBatDau", $caLamViecDTO->gioBatDau)
            ->setParameter("gioKetThuc", $caLamViecDTO->gioKetThuc)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($caLamViecTrung) {
            throw new \Exception(t("error.exists"));
        }

        $hasAssignments = !$caLamViec->getWorkShiftAssignments()->isEmpty();
        $shouldCreateNewShift =
            !$caLamViecDTO->applyToExistingSchedules && $hasAssignments;

        if ($shouldCreateNewShift) {
            $caLamViecMoi = new WorkShift();
            $caLamViecMoi->setThoiGianLamViec($thoiGianLamViec);
            $caLamViecMoi->setGioBatDau($caLamViecDTO->gioBatDau);
            $caLamViecMoi->setGioKetThuc($caLamViecDTO->gioKetThuc);
            $caLamViecMoi->setGhiChu($caLamViecDTO->ghiChu);
            $caLamViecMoi->setColor($caLamViec->getColor());

            $this->entityManager->persist($caLamViecMoi);
            $this->entityManager->flush();

            return $caLamViecMoi->jsonSerialize();
        }

        $caLamViec->setThoiGianLamViec($thoiGianLamViec);
        $caLamViec->setGioBatDau($caLamViecDTO->gioBatDau);
        $caLamViec->setGioKetThuc($caLamViecDTO->gioKetThuc);
        $caLamViec->setGhiChu($caLamViecDTO->ghiChu);

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

    public function updateParttimeStatus(int $id, WorkShiftStatusDTO $dto): array
    {
        $caLamViec = $this->caLamViecRepository->find($id);
        if (!$caLamViec) {
            throw new \Exception(t("error.not_found"));
        }

        $caLamViec->setStatus($dto->status);
        $this->entityManager->flush();

        return $caLamViec->jsonSerialize();
    }

    private function updateExistingFulltimeSchedules(
        WorkingTime $thoiGianLamViec,
    ): void {
        $dayOfWeek = $thoiGianLamViec->getDayOfWeek();

        if (!$dayOfWeek) {
            return;
        }

        $startTime = \DateTime::createFromFormat(
            "H:i",
            formatTimeString($thoiGianLamViec->getGioBatDau()),
        );
        $endTime = \DateTime::createFromFormat(
            "H:i",
            formatTimeString($thoiGianLamViec->getGioKetThuc()),
        );

        if (!$startTime || !$endTime) {
            throw new \Exception("Invalid working time format");
        }

        $fixedSchedules = $this->fixedScheduleRepository
            ->createQueryBuilder("fixedSchedule")
            ->join("fixedSchedule.fixedScheduleGroup", "fixedScheduleGroup")
            ->andWhere("fixedSchedule.dayOfWeek = :dayOfWeek")
            ->andWhere("fixedScheduleGroup.type = :type")
            ->setParameter("dayOfWeek", $dayOfWeek)
            ->setParameter("type", "fulltime")
            ->getQuery()
            ->getResult();

        foreach ($fixedSchedules as $fixedSchedule) {
            $fixedSchedule->setStartTime(clone $startTime);
            $fixedSchedule->setEndTime(clone $endTime);
        }
    }
}
