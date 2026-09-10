<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\DiningTableDTO;
use App\DTO\DiningTableRangeDTO;
use App\Entity\DiningTable;
use App\Repository\DiningTableRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiningTableService
{
    public function __construct(
        private readonly DiningTableRepository $diningTableRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->diningTableRepository->createQueryBuilder('e');

        if (empty($params['sort_column'])) {
            $qb->orderBy('e.tableNumber', 'ASC');
        }

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        $result['collection'] = array_map(
            fn(DiningTable $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->diningTableRepository->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', DiningTable::STATUS_ACTIVE)
            ->orderBy('e.tableNumber', 'ASC');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (DiningTable $item) {
            return [
                'label' => 'Bàn ' . $item->getTableNumber(),
                'value' => $item->getId(),
                'tableNumber' => $item->getTableNumber(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->diningTableRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function ensureRange(DiningTableRangeDTO $dto): array
    {
        $from = $dto->from;
        $to = $dto->to;

        if ($from === null || $to === null || $from < 1 || $to < $from) {
            throw new \InvalidArgumentException('Khoảng số bàn không hợp lệ');
        }

        $existingTables = $this->diningTableRepository->findTablesByRange($from, $to);

        $existingMap = [];
        foreach ($existingTables as $table) {
            $existingMap[$table->getTableNumber()] = $table;
        }

        $createdCount = 0;
        $existingCount = 0;

        for ($num = $from; $num <= $to; $num++) {
            if (isset($existingMap[$num])) {
                $existingCount++;
                continue;
            }

            $newTable = new DiningTable();
            $newTable->setTableNumber($num);
            $newTable->setStatus(DiningTable::STATUS_ACTIVE);
            $newTable->setIsUsing(false);
            $newTable->setQrCode($this->generateQrCode($num));

            $this->entityManager->persist($newTable);
            $createdCount++;
        }

        if ($createdCount > 0) {
            $this->entityManager->flush();
        }

        return [
            'from' => $from,
            'to' => $to,
            'created_count' => $createdCount,
            'existing_count' => $existingCount,
            'total_in_range' => $to - $from + 1,
        ];
    }

    public function toggleStatus(int $id): array
    {
        $table = $this->diningTableRepository->find($id);

        if (!$table) {
            throw new \Exception(t('error.not_found'));
        }

        $currentStatus = $table->getStatus() ?? DiningTable::STATUS_INACTIVE;

        if ($currentStatus === DiningTable::STATUS_ACTIVE) {
            if ($table->isUsing() === true) {
                throw new \DomainException('Bàn đang có order đang hoạt động, không thể chuyển sang trạng thái Không hoạt động');
            }
            $table->setStatus(DiningTable::STATUS_INACTIVE);
        } else {
            $table->setStatus(DiningTable::STATUS_ACTIVE);
        }

        $this->entityManager->flush();

        return $table->jsonSerialize();
    }


    public function update(int $id, DiningTableDTO $dto): array
    {
        $table = $this->diningTableRepository->find($id);

        if (!$table) {
            throw new \Exception(t('error.not_found'));
        }

        if ($dto->status !== null) {
            if ($dto->status === DiningTable::STATUS_INACTIVE && $table->isUsing() === true) {
                throw new \DomainException('Bàn đang có order đang hoạt động, không thể chuyển sang trạng thái Không hoạt động');
            }
            $table->setStatus($dto->status);
        }

        if ($dto->isUsing !== null) {
            if ($dto->isUsing === true && $table->getStatus() === DiningTable::STATUS_INACTIVE) {
                throw new \DomainException('Bàn đang ở trạng thái Không hoạt động, không thể kích hoạt sử dụng');
            }
            $table->setIsUsing($dto->isUsing);
        }

        $this->entityManager->flush();

        return $table->jsonSerialize();
    }

    public function generateQrCode(int $tableNumber): string
    {
        return sprintf('TABLE_%d_%s', $tableNumber, bin2hex(random_bytes(8)));
    }
}
