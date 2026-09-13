<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SaleOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaleOrder>
 */
class SaleOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleOrder::class);
    }

    /**
     * Sinh mã đơn hàng tự tăng theo ngày theo định dạng HD-YYYYMMDD-XXXX
     */
    public function generateNextCode(?\DateTimeInterface $date = null): string
    {
        $targetDate = $date ?? new \DateTime();
        $prefix = sprintf('HD-%s-', $targetDate->format('Ymd'));

        $maxCode = $this->createQueryBuilder('o')
            ->select('MAX(o.code)')
            ->where('o.code LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->getQuery()
            ->getSingleScalarResult();

        $nextNumber = 1;
        if ($maxCode !== null && is_string($maxCode)) {
            $suffix = substr($maxCode, strlen($prefix));
            $nextNumber = ((int) $suffix) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNumber);
    }
}
