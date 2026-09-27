<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\DiningTableDTO;
use App\DTO\DiningTableRangeDTO;
use App\Entity\Branch;
use App\Entity\DiningTable;
use App\Entity\User;
use App\Repository\BranchRepository;
use App\Repository\DiningTableRepository;
use App\Repository\UserPositionRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiningTableService
{
    public function __construct(
        private readonly DiningTableRepository $diningTableRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly BranchRepository $branchRepository,
        private readonly UserPositionRepository $userPositionRepository,
        private readonly MercureService $mercureService,
    ) {}

    public function findAll(array $params, ?User $currentUser = null): array
    {
        $qb = $this->diningTableRepository->createQueryBuilder('e')
            ->leftJoin('e.branch', 'b')
            ->addSelect('b');

        if (empty($params['sort_column'])) {
            $qb->orderBy('e.tableNumber', 'ASC');
        }

        // Lọc theo chi nhánh cụ thể nếu có
        if (isset($params['branchId']) && $params['branchId'] !== '' && $params['branchId'] !== null) {
            $qb->andWhere('e.branch = :specificBranchId')
                ->setParameter('specificBranchId', (int) $params['branchId']);
        } elseif ($currentUser instanceof User && !isAdmin($currentUser)) {
            // Không phải admin: chỉ xem bàn ăn thuộc các chi nhánh mà user được phân công
            $assignedBranchIds = $this->userPositionRepository->findAssignedBranchIdsByUser($currentUser);
            if (empty($assignedBranchIds)) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('e.branch IN (:assignedBranchIds)')
                    ->setParameter('assignedBranchIds', $assignedBranchIds);
            }
        }

        $relationFields = [
            'branchId' => [
                'joinField' => 'e.branch',
                'alias' => 'branch_filter',
                'targetField' => 'id',
            ],
        ];

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e', $relationFields);

        $result['collection'] = array_map(
            fn(DiningTable $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params, ?User $currentUser = null): array
    {
        $qb = $this->diningTableRepository->createQueryBuilder('e')
            ->leftJoin('e.branch', 'b')
            ->andWhere('e.status = :status')
            ->setParameter('status', DiningTable::STATUS_ACTIVE)
            ->orderBy('e.tableNumber', 'ASC');

        if (isset($params['branchId']) && $params['branchId'] !== '' && $params['branchId'] !== null) {
            $qb->andWhere('e.branch = :selectBranchId')
                ->setParameter('selectBranchId', (int) $params['branchId']);
        } elseif ($currentUser instanceof User && !isAdmin($currentUser)) {
            $assignedBranchIds = $this->userPositionRepository->findAssignedBranchIdsByUser($currentUser);
            if (empty($assignedBranchIds)) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('e.branch IN (:assignedBranchIds)')
                    ->setParameter('assignedBranchIds', $assignedBranchIds);
            }
        }

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (DiningTable $item) {
            return [
                'label' => 'Bàn ' . $item->getTableNumber() . ($item->getBranch() ? ' - ' . $item->getBranch()->getName() : ''),
                'value' => $item->getId(),
                'tableNumber' => $item->getTableNumber(),
                'isUsing' => (bool) $item->isUsing(),
                'branchId' => $item->getBranch()?->getId(),
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

    public function ensureRange(DiningTableRangeDTO $dto, ?User $currentUser = null): array
    {
        $from = $dto->from;
        $to = $dto->to;

        if ($from === null || $to === null || $from < 1 || $to < $from) {
            throw new \InvalidArgumentException('Khoảng số bàn không hợp lệ');
        }

        $branch = null;
        if ($dto->branchId !== null) {
            $branch = $this->branchRepository->find($dto->branchId);
            if (!$branch instanceof Branch) {
                throw new \Exception(sprintf('Chi nhánh ID %d không tồn tại', $dto->branchId));
            }
        } elseif ($currentUser instanceof User && !isAdmin($currentUser)) {
            $assignedBranchIds = $this->userPositionRepository->findAssignedBranchIdsByUser($currentUser);
            if (!empty($assignedBranchIds)) {
                $branch = $this->branchRepository->find($assignedBranchIds[0]);
            }
        }

        $existingTables = $this->diningTableRepository->findTablesByRange($from, $to, $branch?->getId());

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
            $newTable->setBranch($branch);
            $newTable->setStatus(DiningTable::STATUS_ACTIVE);
            $newTable->setIsUsing(false);
            $newTable->setQrCode($this->generateQrCode($num, $branch?->getId()));

            $this->entityManager->persist($newTable);
            $createdCount++;
        }

        if ($createdCount > 0) {
            $this->entityManager->flush();
        }

        return [
            'from' => $from,
            'to' => $to,
            'branchId' => $branch?->getId(),
            'branch' => $branch ? ['id' => $branch->getId(), 'name' => $branch->getName(), 'code' => $branch->getCode()] : null,
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

        $serialized = $table->jsonSerialize();
        $this->mercureService->diningTableUpdated($serialized);

        return $serialized;
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

        if ($dto->branchId !== null) {
            $branch = $this->branchRepository->find($dto->branchId);
            $table->setBranch($branch);
        }

        $this->entityManager->flush();

        $serialized = $table->jsonSerialize();
        $this->mercureService->diningTableUpdated($serialized);

        return $serialized;
    }

    public function generateQrCode(int $tableNumber, ?int $branchId = null): string
    {
        return sprintf('TABLE_%s%d_%s', $branchId ? $branchId . '_' : '', $tableNumber, bin2hex(random_bytes(8)));
    }
}
