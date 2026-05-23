<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RequestWatcher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RequestWatcher>
 */
class RequestWatcherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestWatcher::class);
    }
}
