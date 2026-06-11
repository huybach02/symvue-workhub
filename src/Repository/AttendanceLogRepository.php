<?php

namespace App\Repository;

use App\Entity\Attendance;
use App\Entity\AttendanceLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AttendanceLog>
 */
class AttendanceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttendanceLog::class);
    }

    public function createAttendanceLog(
        ?Attendance $attendance,
        ?string $ipAddress = null,
        ?string $validationStatus = null,
        ?string $validationReason = null,
        ?string $latitude = null,
        ?string $longtitude = null,
        ?int $gpsAccuracyMeter = null,
        ?int $deviceId = null,
        ?string $qrToken = null,
    ): AttendanceLog {
        $attendanceLog = new AttendanceLog();
        $attendanceLog->setAttendance($attendance);
        $attendanceLog->setIpAddress($ipAddress);
        $attendanceLog->setValidationStatus($validationStatus);
        $attendanceLog->setValidationReason($validationReason);
        $attendanceLog->setLatitude($latitude);
        $attendanceLog->setLongtitude($longtitude);
        $attendanceLog->setGpsAccuracyMeter($gpsAccuracyMeter);
        $attendanceLog->setDeviceId($deviceId);
        $attendanceLog->setQrToken($qrToken);
        $this->getEntityManager()->persist($attendanceLog);
        $this->getEntityManager()->flush();
        return $attendanceLog;
    }

    //    /**
    //     * @return AttendanceLog[] Returns an array of AttendanceLog objects
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

    //    public function findOneBySomeField($value): ?AttendanceLog
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
