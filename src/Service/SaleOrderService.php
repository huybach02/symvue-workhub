<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\SaleOrderDTO;
use App\Entity\SaleOrder;
use App\Entity\User;
use App\Entity\UserPosition;
use App\Repository\SaleOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class SaleOrderService
{
    public function __construct(
        private readonly SaleOrderRepository $saleOrderRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params, ?User $currentUser = null): array
    {
        $qb = $this->saleOrderRepository->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        if ($currentUser instanceof User) {
            $this->restrictToAssignedBranches($qb, $currentUser, 'e');
        }

        $relationFields = [
            'diningTableId' => [
                'joinField' => 'e.diningTable',
                'alias' => 'dining_table_filter',
                'targetField' => 'id',
            ],
            'branchId' => [
                'joinField' => 'e.branch',
                'alias' => 'branch_filter',
                'targetField' => 'id',
            ],
            'cashierId' => [
                'joinField' => 'e.cashier',
                'alias' => 'cashier_filter',
                'targetField' => 'id',
            ],
        ];

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'e',
            $relationFields
        );

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(SaleOrder $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params, ?User $currentUser = null): array
    {
        $qb = $this->saleOrderRepository->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        if ($currentUser instanceof User) {
            $this->restrictToAssignedBranches($qb, $currentUser, 'e');
        }

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (SaleOrder $item) {
            return [
                'label' => $item->getCode(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id, ?User $currentUser = null): array
    {
        $qb = $this->saleOrderRepository
            ->createQueryBuilder('e')
            ->andWhere('e.id = :orderId')
            ->setParameter('orderId', $id);

        if ($currentUser instanceof User) {
            $this->restrictToAssignedBranches($qb, $currentUser, 'e');
        }

        $item = $qb->getQuery()->getOneOrNullResult();

        if (!$item instanceof SaleOrder) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(SaleOrderDTO $dto): array
    {
        $item = new SaleOrder();

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, SaleOrderDTO $dto, ?User $currentUser = null): array
    {
        $qb = $this->saleOrderRepository
            ->createQueryBuilder('e')
            ->andWhere('e.id = :orderId')
            ->setParameter('orderId', $id);

        if ($currentUser instanceof User) {
            $this->restrictToAssignedBranches($qb, $currentUser, 'e');
        }

        $item = $qb->getQuery()->getOneOrNullResult();

        if (!$item instanceof SaleOrder) {
            throw new \Exception(t('error.not_found'));
        }

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    /**
     * Giới hạn chi nhánh:
     * - Admin: xem được toàn bộ đơn bán hàng của tất cả chi nhánh.
     * - Không phải admin: chỉ xem được các đơn bán hàng thuộc chi nhánh của người dùng.
     */
    private function restrictToAssignedBranches(
        QueryBuilder $qb,
        User $currentUser,
        string $alias = 'e',
    ): void {
        if (isAdmin($currentUser)) {
            return;
        }

        $currentTimestamp = time();
        $assignmentQb = $this->entityManager
            ->createQueryBuilder()
            ->select('1')
            ->from(UserPosition::class, 'assignedPosition')
            ->innerJoin('assignedPosition.department', 'assignedDepartment')
            ->andWhere('assignedPosition.member = :orderCurrentUser')
            ->andWhere('assignedPosition.status = 1')
            ->andWhere(sprintf('assignedDepartment.branch = %s.branch', $alias))
            ->andWhere(
                '(assignedPosition.startTemp IS NULL OR assignedPosition.startTemp <= :orderCurrentTimestamp)',
            )
            ->andWhere(
                '(assignedPosition.endTemp IS NULL OR assignedPosition.endTemp > :orderCurrentTimestamp)',
            );

        $qb
            ->andWhere($qb->expr()->exists($assignmentQb->getDQL()))
            ->setParameter('orderCurrentUser', $currentUser)
            ->setParameter('orderCurrentTimestamp', $currentTimestamp);
    }
}
