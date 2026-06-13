<?php

namespace App\Repository;

use App\Class\AttendanceType;
use App\Class\StatusAttendance;
use App\Entity\Attendance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendance>
 */
class AttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendance::class);
    }

    /**
     * @return Attendance[]
     */
    public function findByWorkDate(\DateTimeInterface $workDate): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employee', 'e')
            ->addSelect('e')
            ->leftJoin('a.workShiftAssignment', 'wsa')
            ->addSelect('wsa')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.workDate = :workDate')
            ->setParameter('workDate', $workDate->format('Y-m-d'))
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAttendancesByWorkDateAndEmployee(
        \DateTimeInterface $workDate,
        User $employee,
    ): array {
        return $this->createQueryBuilder('a')
            ->andWhere('a.workDate = :workDate')
            ->setParameter('workDate', $workDate->format('Y-m-d'))
            ->andWhere('a.employee = :employee')
            ->setParameter('employee', $employee)
            ->andWhere('a.deletedAt IS NULL')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Attendance[]
     */
    public function findScheduledCheckInAttendancesForAbsentSync(
        \DateTimeInterface $fromDate,
        \DateTimeInterface $toDate,
    ): array {
        return $this->createQueryBuilder('a')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.status = :status')
            ->setParameter('status', StatusAttendance::Scheduled->value)
            ->andWhere('a.attendanceType = :attendanceType')
            ->setParameter('attendanceType', AttendanceType::CheckIn->value)
            ->andWhere('a.workDate BETWEEN :fromDate AND :toDate')
            ->setParameter('fromDate', $fromDate->format('Y-m-d'))
            ->setParameter('toDate', $toDate->format('Y-m-d'))
            ->orderBy('a.workDate', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Attendance[]
     */
    public function findFutureScheduledAttendancesForReminderRecalculate(
        \DateTimeInterface $fromDate,
    ): array {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employee', 'e')
            ->addSelect('e')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.status = :status')
            ->andWhere('a.remindedAt IS NULL')
            ->andWhere('a.workDate >= :fromDate')
            ->setParameter('status', StatusAttendance::Scheduled->value)
            ->setParameter('fromDate', $fromDate->format('Y-m-d'))
            ->orderBy('a.workDate', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Attendance[]
     */
    public function findDueReminderAttendances(
        \DateTimeInterface $now,
    ): array {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.employee', 'e')
            ->addSelect('e')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.status = :status')
            ->andWhere('a.reminderAt IS NOT NULL')
            ->andWhere('a.remindedAt IS NULL')
            ->andWhere('a.reminderAt <= :now')
            ->setParameter('status', StatusAttendance::Scheduled->value)
            ->setParameter('now', $now)
            ->orderBy('a.reminderAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Attendance[]
     */
    public function findScheduledAttendancesByUserAndDateRange(
        User $user,
        \DateTimeInterface $fromDate,
        \DateTimeInterface $toDate,
    ): array {
        return $this->createQueryBuilder('a')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.employee = :employee')
            ->andWhere('a.status = :status')
            ->andWhere('a.workDate BETWEEN :fromDate AND :toDate')
            ->setParameter('employee', $user)
            ->setParameter('status', StatusAttendance::Scheduled->value)
            ->setParameter('fromDate', $fromDate->format('Y-m-d'))
            ->setParameter('toDate', $toDate->format('Y-m-d'))
            ->orderBy('a.workDate', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Attendance[] Returns an array of Attendance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Attendance
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
