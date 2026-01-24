<?php

namespace App\Repository;

use App\Entity\CauHinhChung;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CauHinhChung>
 */
class CauHinhChungRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CauHinhChung::class);
    }

    public function getAllConfig()
    {
        $data = [];

        $configs = $this->createQueryBuilder('c')
            ->select('c.tenCauHinh', 'c.giaTri')
            ->getQuery()
            ->getResult();

        foreach ($configs as $config) {
            $data[$config['tenCauHinh']] = $config['giaTri'];
        }
        return $data;
    }

    //    /**
    //     * @return CauHinhChung[] Returns an array of CauHinhChung objects
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

    //    public function findOneBySomeField($value): ?CauHinhChung
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
