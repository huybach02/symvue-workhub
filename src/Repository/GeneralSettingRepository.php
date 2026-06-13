<?php

namespace App\Repository;

use App\Entity\GeneralSetting;
use App\Service\CacheService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GeneralSetting>
 */
class GeneralSettingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheService $cacheService,
    ) {
        parent::__construct($registry, GeneralSetting::class);
    }

    public const CACHE_KEY = 'general_settings';

    public function getAllConfig()
    {
        $cacheKey = self::CACHE_KEY;

        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = [];

        $configs = $this->createQueryBuilder('c')
            ->select('c.tenCauHinh', 'c.giaTri')
            ->getQuery()
            ->getResult();

        foreach ($configs as $config) {
            $data[$config['tenCauHinh']] = $config['giaTri'];
        }

        $this->cacheService->set($cacheKey, $data, 60 * 60 * 24 * 365 * 5, true);

        return $data;
    }

    //    /**
    //     * @return GeneralSetting[] Returns an array of GeneralSetting objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GeneralSetting
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
