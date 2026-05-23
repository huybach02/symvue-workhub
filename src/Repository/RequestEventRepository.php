<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Request;
use App\Entity\RequestEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RequestEvent>
 */
class RequestEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestEvent::class);
    }

    /**
     * @return RequestEvent[]
     */
    public function findTimelineByRequest(Request $request): array
    {
        return $this->createQueryBuilder('requestEvent')
            ->andWhere('requestEvent.request = :request')
            ->setParameter('request', $request)
            ->orderBy('requestEvent.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
