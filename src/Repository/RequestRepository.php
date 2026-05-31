<?php

declare(strict_types=1);

namespace App\Repository;

use App\Class\FilterWithPagination;
use App\Class\Request\RequestConstant;
use App\Entity\Request;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Request>
 */
class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    public function findWithPaginationForUser(
        array $params,
        User $currentUser,
        array $allowedTypes = [],
    ): array
    {
        $type = trim((string) ($params['type'] ?? ''));
        $view = trim((string) ($params['view'] ?? 'mine'));
        $status = trim((string) ($params['status'] ?? ''));

        $qb = $this->createQueryBuilder('request')
            ->leftJoin('request.requester', 'requester')->addSelect('requester')
            ->leftJoin('request.currentApprover', 'currentApprover')->addSelect('currentApprover');

        if ($type !== '') {
            $qb->andWhere('request.type = :type')->setParameter('type', $type);
        }

        if ($allowedTypes !== []) {
            $qb->andWhere('request.type IN (:allowedTypes)')
                ->setParameter('allowedTypes', $allowedTypes);
        }

        if ($status !== '') {
            $qb->andWhere('request.status = :status')->setParameter('status', $status);
        }

        if ($view === 'approval') {
            $qb->andWhere('request.currentApprover = :currentUser')
                ->setParameter('currentUser', $currentUser);

            if ($status === '') {
                $qb->andWhere('request.status = :pendingStatus')
                    ->setParameter('pendingStatus', RequestConstant::STATUS_PENDING);
            }
        } else {
            $qb->andWhere('request.requester = :currentUser')
                ->setParameter('currentUser', $currentUser);
        }

        if (!isset($params['sort_column'])) {
            $params['sort_column'] = 'id';
            $params['sort_direction'] = 'desc';
        }

        return FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'request',
            [
                'requester' => [
                    'joinField' => 'request.requester',
                    'alias' => 'requesterFilter',
                    'targetField' => 'id',
                ],
            ]
        );
    }
}
