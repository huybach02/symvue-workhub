<?php

namespace App\Repository;

use App\Class\Constanst;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Tìm user theo email, chỉ lấy những user chưa bị soft delete
     * 
     * @param string $email
     * @return User|null
     */
    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return User[]
     */
    public function getListParttimeMembersByDepartmentId(string $departmentId): array
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.hinhThucLamViec = :hinhThucLamViec')
            ->andWhere($qb->expr()->exists(
                $this->getEntityManager()->createQueryBuilder()
                    ->select('1')
                    ->from('App\\Entity\\UserPosition', 'up')
                    ->innerJoin('up.department', 'd')
                    ->andWhere('up.member = u')
                    ->andWhere('d.id = :departmentId')
                    ->andWhere('up.deletedAt IS NULL')
                    ->getDQL()
            ))
            ->setParameter('departmentId', $departmentId)
            ->setParameter('hinhThucLamViec', Constanst::HINH_THUC_LAM_VIEC['PART_TIME'])
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFirstActiveAdmin(): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.status = 1')
            ->andWhere("LOWER(CONCAT(u.roles, '')) LIKE :adminRole")
            ->setParameter('adminRole', '%role_admin%')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.status = 1')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
