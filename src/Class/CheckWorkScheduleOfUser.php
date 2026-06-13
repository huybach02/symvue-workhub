<?php

declare(strict_types=1);

namespace App\Class;

use App\Entity\Attendance;
use App\Entity\User;
use App\Repository\AttendanceLogRepository;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use App\Service\MercureService;
use App\Service\WorkScheduleService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class CheckWorkScheduleOfUser
{
    private const CHECK_IN_EARLIEST_KEY = 'CHECK_IN_EARLIEST_MINUTES';
    private const CHECK_IN_GRACE_KEY = 'CHECK_IN_GRACE_MINUTES';
    private const LATE_LIMIT_KEY = 'LATE_LIMIT_MINUTES';
    private const CHECK_OUT_GRACE_KEY = 'CHECK_OUT_GRACE_MINUTES';
    private const CHECK_OUT_LATEST_KEY = 'CHECK_OUT_LATEST_MINUTES';

    public function __construct(
        private readonly WorkScheduleService $workScheduleService,
        private readonly GeneralSettingRepository $generalSettingRepository,
        private readonly AttendanceRepository $attendanceRepository,
        private readonly AttendanceLogRepository $attendanceLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MercureService $mercureService,
    ) {}

    public function checkWorkingScheduleOfUser(
        DateTimeImmutable $now,
        User $currentUser,
        array $attendanceInfo,
    ): bool {
        $generalSetting = $this->generalSettingRepository->getAllConfig();

        $attendances = $this->attendanceRepository->findAttendancesByWorkDateAndEmployee(
            $now,
            $currentUser,
        );

        if (empty($attendances)) {
            throw new \Exception(t('error.not_in_working_schedule'));
        }

        $attendanceRecord = $this->resolveAttendanceRecordByNow(
            $attendances,
            $now,
            $generalSetting,
        );

        if (!$attendanceRecord) {
            $absentCheckInAttendance = $this->resolveAbsentCheckInRecordByNow(
                $attendances,
                $now,
                $generalSetting,
            );

            if ($absentCheckInAttendance instanceof Attendance) {
                $this->throwMessageAttendanceInvalid(
                    StatusAttendance::Absent->value,
                    attendance: $absentCheckInAttendance,
                );
            }

            throw new \Exception(t('error.not_in_working_schedule'));
        }

        $attendanceDateRange = $this->resolveAttendanceDateRange(
            $attendanceRecord,
        );

        if ($attendanceDateRange === null) {
            throw new \Exception(t('error.not_in_working_schedule'));
        }

        $attendanceSetting = $this->resolveAttendanceSetting(
            $attendanceRecord,
            $generalSetting,
        );

        switch ($attendanceRecord->getWorkType()) {
            case WorkType::FullTime->value:
            case WorkType::PartTime->value:
                if ($attendanceRecord->getAttendanceType() === AttendanceType::CheckIn->value) {
                    return $this->verifyTimeCheckIn(
                        $attendanceSetting,
                        $now,
                        $attendanceDateRange['startTime'],
                        $attendanceDateRange['endTime'],
                        $attendanceRecord,
                        $attendanceInfo,
                    );
                }

                return $this->verifyTimeCheckOut(
                    $attendanceSetting,
                    $now,
                    $attendanceDateRange['startTime'],
                    $attendanceDateRange['endTime'],
                    $attendanceRecord,
                    $attendanceInfo,
                );
            default:
                throw new \Exception(t('error.not_in_working_schedule'));
        }
    }

    private function verifyTimeCheckIn(
        array $generalSetting,
        DateTimeImmutable $now,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        Attendance $attendance,
        array $attendanceInfo,
    ) {
        $checkInEarliestMinutes = (int) ($generalSetting[self::CHECK_IN_EARLIEST_KEY] ?? 0);
        $checkInGraceMinutes = (int) ($generalSetting[self::CHECK_IN_GRACE_KEY] ?? 0);
        $lateLimitMinutes = (int) ($generalSetting[self::LATE_LIMIT_KEY] ?? 0);

        if ($lateLimitMinutes < $checkInGraceMinutes) {
            throw new \Exception(t('error.param_invalid'));
        }

        $checkInEarliestStartTime = $startTime->modify(
            sprintf('-%d minutes', $checkInEarliestMinutes),
        );
        $checkInGraceStartTime = $startTime->modify(
            sprintf('+%d minutes', $checkInGraceMinutes),
        );
        $lateLimitStartTime = $startTime->modify(
            sprintf('+%d minutes', $lateLimitMinutes),
        );

        // Case 1: Chấm công sớm hơn thời gian bắt đầu cho phép chấm công
        if ($now < $checkInEarliestStartTime) {
            $this->attendanceLogRepository->createAttendanceLog(
                attendance: $attendance,
                ipAddress: $attendanceInfo['ipAddress'],
                latitude: $attendanceInfo['latitude'],
                longtitude: $attendanceInfo['longtitude'],
                gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
                deviceId: $attendanceInfo['deviceId'],
                qrToken: $attendanceInfo['qrToken'],
                validationStatus: ValidationStatus::Invalid->value,
                validationReason: StatusAttendance::EarlyCheckIn->value,
            );

            $this->throwMessageAttendanceInvalid(
                StatusAttendance::EarlyCheckIn->value,
                attendance: $attendance,
            );
        }

        // Case 2: Chấm công trong khoảng thời gian tính trễ
        if ($now <= $lateLimitStartTime && $now < $endTime) {
            // Case 2.1: Chấm công trong khoảng checkInGraceStartTime => on_time
            if ($now <= $checkInGraceStartTime) {
                // Tạo log chấm công
                $this->attendanceLogRepository->createAttendanceLog(
                    attendance: $attendance,
                    ipAddress: $attendanceInfo['ipAddress'],
                    latitude: $attendanceInfo['latitude'],
                    longtitude: $attendanceInfo['longtitude'],
                    gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
                    deviceId: $attendanceInfo['deviceId'],
                    qrToken: $attendanceInfo['qrToken'],
                    validationStatus: ValidationStatus::Valid->value,
                    validationReason: StatusAttendance::OnTime->value,
                );
                // Update status attendance
                $attendance->setStatus(StatusAttendance::OnTime->value);
                $attendance->setValidationStatus(ValidationStatus::Valid->value);
                $attendance->setTimeAttendance($now->format('H:i:s'));
                $this->entityManager->flush();
                $this->publishAttendanceRealtime($attendance);

                return true;
            }

            // Case 2.2: Chấm công trong khoảng từ checkInGraceStartTime đến lateLimitStartTime => late
            $this->attendanceLogRepository->createAttendanceLog(
                attendance: $attendance,
                ipAddress: $attendanceInfo['ipAddress'],
                latitude: $attendanceInfo['latitude'],
                longtitude: $attendanceInfo['longtitude'],
                gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
                deviceId: $attendanceInfo['deviceId'],
                qrToken: $attendanceInfo['qrToken'],
                validationStatus: ValidationStatus::Invalid->value,
                validationReason: StatusAttendance::Late->value,
            );
            // Update status attendance
            $attendance->setStatus(StatusAttendance::Late->value);
            $attendance->setValidationStatus(ValidationStatus::Invalid->value);
            $attendance->setTimeAttendance($now->format('H:i:s'));
            $this->entityManager->flush();
            $this->publishAttendanceRealtime($attendance);

            $interval = $now->diff($checkInGraceStartTime);
            $timeLateFromGrace = $interval->h * 60 + $interval->i;

            $this->throwMessageAttendanceInvalid(
                StatusAttendance::Late->value,
                (string) $timeLateFromGrace,
                $attendance,
            );
        }

        // Case 3: Chấm công sau thời gian tính trễ => Vắng
        $this->attendanceLogRepository->createAttendanceLog(
            attendance: $attendance,
            ipAddress: $attendanceInfo['ipAddress'],
            latitude: $attendanceInfo['latitude'],
            longtitude: $attendanceInfo['longtitude'],
            gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
            deviceId: $attendanceInfo['deviceId'],
            qrToken: $attendanceInfo['qrToken'],
            validationStatus: ValidationStatus::Invalid->value,
            validationReason: StatusAttendance::Absent->value,
        );
        // Update status attendance
        $attendance->setStatus(StatusAttendance::Absent->value);
        $attendance->setValidationStatus(ValidationStatus::Invalid->value);
        $attendance->setTimeAttendance($now->format('H:i:s'));
        $this->markRelatedCheckOutAsAbsent($attendance);
        $this->entityManager->flush();
        $this->publishAttendanceRealtime($attendance);

        $relatedCheckOutAttendance = $this->findRelatedCheckOutAttendance($attendance);
        if ($relatedCheckOutAttendance instanceof Attendance) {
            $this->publishAttendanceRealtime($relatedCheckOutAttendance);
        }

        $this->throwMessageAttendanceInvalid(
            StatusAttendance::Absent->value,
            attendance: $attendance,
        );
    }

    private function verifyTimeCheckOut(
        array $generalSetting,
        DateTimeImmutable $now,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        Attendance $attendance,
        array $attendanceInfo,
    ) {
        $checkOutGraceMinutes = (int) ($generalSetting[self::CHECK_OUT_GRACE_KEY] ?? 0);
        $checkOutLatestMinutes = (int) ($generalSetting[self::CHECK_OUT_LATEST_KEY] ?? 0);

        if ($checkOutLatestMinutes < 0 || $checkOutGraceMinutes < 0) {
            throw new \Exception(t('error.param_invalid'));
        }

        $checkOutGraceEndTime = $endTime->modify(
            sprintf('-%d minutes', $checkOutGraceMinutes),
        );
        $checkOutLatestEndTime = $endTime->modify(
            sprintf('+%d minutes', $checkOutLatestMinutes),
        );

        // Case 1: Chấm công sớm hơn thời gian bắt đầu cho phép chấm công ra
        if ($now < $checkOutGraceEndTime) {
            $this->attendanceLogRepository->createAttendanceLog(
                attendance: $attendance,
                ipAddress: $attendanceInfo['ipAddress'],
                latitude: $attendanceInfo['latitude'],
                longtitude: $attendanceInfo['longtitude'],
                gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
                deviceId: $attendanceInfo['deviceId'],
                qrToken: $attendanceInfo['qrToken'],
                validationStatus: ValidationStatus::Invalid->value,
                validationReason: StatusAttendance::EarlyLeave->value,
            );

            throw new \Exception(sprintf(
                '%s (%s)',
                t('error.too_early_to_check_out'),
                $this->formatAttendanceLabel($attendance),
            ));
        }

        // Case 2: Chấm công trong khoảng thời gian cho phép chấm công ra
        if ($now <= $checkOutLatestEndTime) {
            $this->attendanceLogRepository->createAttendanceLog(
                attendance: $attendance,
                ipAddress: $attendanceInfo['ipAddress'],
                latitude: $attendanceInfo['latitude'],
                longtitude: $attendanceInfo['longtitude'],
                gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
                deviceId: $attendanceInfo['deviceId'],
                qrToken: $attendanceInfo['qrToken'],
                validationStatus: ValidationStatus::Valid->value,
                validationReason: StatusAttendance::OnTime->value,
            );

            $attendance->setStatus(StatusAttendance::OnTime->value);
            $attendance->setValidationStatus(ValidationStatus::Valid->value);
            $attendance->setTimeAttendance($now->format('H:i:s'));
            $this->entityManager->flush();
            $this->publishAttendanceRealtime($attendance);

            return true;
        }

        // Case 3: Chấm công sau thời gian cho phép chấm công ra
        $this->attendanceLogRepository->createAttendanceLog(
            attendance: $attendance,
            ipAddress: $attendanceInfo['ipAddress'],
            latitude: $attendanceInfo['latitude'],
            longtitude: $attendanceInfo['longtitude'],
            gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
            deviceId: $attendanceInfo['deviceId'],
            qrToken: $attendanceInfo['qrToken'],
            validationStatus: ValidationStatus::Invalid->value,
            validationReason: StatusAttendance::LateCheckOut->value,
        );

        $attendance->setStatus(StatusAttendance::LateCheckOut->value);
        $attendance->setValidationStatus(ValidationStatus::Invalid->value);
        $attendance->setTimeAttendance($now->format('H:i:s'));
        $this->entityManager->flush();
        $this->publishAttendanceRealtime($attendance);

        $interval = $now->diff($checkOutGraceEndTime);
        $timeLateFromLimit = $interval->h * 60 + $interval->i;

        $this->throwMessageAttendanceInvalid(
            StatusAttendance::LateCheckOut->value,
            $timeLateFromLimit,
            $attendance,
        );
    }

    public function getAttendanceRecordToVerify(Attendance $attendance): ?Attendance
    {
        if ($attendance->getStatus() == StatusAttendance::Scheduled->value) {
            return $attendance;
        }
        return null;
    }

    /**
     * @param Attendance[] $attendances
     */
    public function resolveAttendanceRecordByNow(
        array $attendances,
        DateTimeImmutable $now,
        array $generalSetting,
    ): ?Attendance {
        $candidates = [];

        foreach ($attendances as $attendance) {
            $attendanceRecord = $this->getAttendanceRecordToVerify($attendance);
            if (!$attendanceRecord) {
                continue;
            }

            $attendanceWindow = $this->buildAttendanceActionWindow(
                $attendanceRecord,
                $generalSetting,
            );

            if ($attendanceWindow === null) {
                continue;
            }

            $distanceToWindow = 0;
            $phaseRank = 0;

            if ($now < $attendanceWindow['windowStart']) {
                $phaseRank = 1;
                $distanceToWindow = $attendanceWindow['windowStart']->getTimestamp() - $now->getTimestamp();
            } elseif ($now > $attendanceWindow['windowEnd']) {
                $phaseRank = 2;
                $distanceToWindow = $now->getTimestamp() - $attendanceWindow['windowEnd']->getTimestamp();
            }

            $anchorDistance = abs(
                $now->getTimestamp() - $attendanceWindow['anchorTime']->getTimestamp(),
            );

            $candidates[] = [
                'attendance' => $attendanceRecord,
                'phaseRank' => $phaseRank,
                'distanceToWindow' => $distanceToWindow,
                'typePriority' => $attendanceRecord->getAttendanceType() === AttendanceType::CheckOut->value ? 0 : 1,
                'anchorDistance' => $anchorDistance,
                'attendanceId' => $attendanceRecord->getId() ?? PHP_INT_MAX,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static function (array $left, array $right): int {
                return [$left['phaseRank'], $left['distanceToWindow'], $left['typePriority'], $left['anchorDistance'], $left['attendanceId']]
                    <=> [$right['phaseRank'], $right['distanceToWindow'], $right['typePriority'], $right['anchorDistance'], $right['attendanceId']];
            },
        );

        return $candidates[0]['attendance'];
    }

    /**
     * @param Attendance[] $attendances
     */
    private function resolveAbsentCheckInRecordByNow(
        array $attendances,
        DateTimeImmutable $now,
        array $generalSetting,
    ): ?Attendance {
        $candidates = [];

        foreach ($attendances as $attendance) {
            if (
                $attendance->getAttendanceType() !== AttendanceType::CheckIn->value ||
                $attendance->getStatus() !== StatusAttendance::Absent->value
            ) {
                continue;
            }

            $attendanceWindow = $this->buildAttendanceActionWindow(
                $attendance,
                $generalSetting,
            );

            if ($attendanceWindow === null || $now <= $attendanceWindow['windowEnd']) {
                continue;
            }

            $candidates[] = [
                'attendance' => $attendance,
                'distanceFromWindowEnd' => $now->getTimestamp() - $attendanceWindow['windowEnd']->getTimestamp(),
                'attendanceId' => $attendance->getId() ?? PHP_INT_MAX,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static function (array $left, array $right): int {
                return [$left['distanceFromWindowEnd'], $left['attendanceId']]
                    <=> [$right['distanceFromWindowEnd'], $right['attendanceId']];
            },
        );

        return $candidates[0]['attendance'];
    }

    public function getCheckInLatestAllowedTime(
        Attendance $attendance,
        array $generalSetting,
    ): ?DateTimeImmutable {
        if ($attendance->getAttendanceType() !== AttendanceType::CheckIn->value) {
            return null;
        }

        $attendanceWindow = $this->buildAttendanceActionWindow(
            $attendance,
            $generalSetting,
        );

        if ($attendanceWindow === null) {
            return null;
        }

        return $attendanceWindow['windowEnd'];
    }

    /**
     * @return Attendance[]
     */
    public function markAttendanceAndRelatedCheckOutAsAbsent(
        Attendance $attendance,
    ): array {
        if ($attendance->getStatus() !== StatusAttendance::Scheduled->value) {
            return [];
        }

        $attendance->setStatus(StatusAttendance::Absent->value);
        $attendance->setValidationStatus(ValidationStatus::Invalid->value);

        $updatedAttendances = [$attendance];

        $relatedCheckOutAttendance = $this->markRelatedCheckOutAsAbsent($attendance);
        if ($relatedCheckOutAttendance instanceof Attendance) {
            $updatedAttendances[] = $relatedCheckOutAttendance;
        }

        return $updatedAttendances;
    }

    private function markRelatedCheckOutAsAbsent(Attendance $attendance): ?Attendance
    {
        if ($attendance->getAttendanceType() !== AttendanceType::CheckIn->value) {
            return null;
        }

        $relatedCheckOutAttendance = $this->findRelatedCheckOutAttendance($attendance);

        if (!$relatedCheckOutAttendance) {
            return null;
        }

        if ($relatedCheckOutAttendance->getStatus() !== StatusAttendance::Scheduled->value) {
            return null;
        }

        $relatedCheckOutAttendance->setStatus(StatusAttendance::Absent->value);
        $relatedCheckOutAttendance->setValidationStatus(ValidationStatus::Invalid->value);

        return $relatedCheckOutAttendance;
    }

    private function findRelatedCheckOutAttendance(
        Attendance $attendance,
    ): ?Attendance {
        return $this->attendanceRepository->findOneBy([
            'employee' => $attendance->getEmployee(),
            'workDate' => $attendance->getWorkDate(),
            'attendanceType' => AttendanceType::CheckOut->value,
            'workScheduleStartTime' => $attendance->getWorkScheduleStartTime(),
            'workScheduleEndTime' => $attendance->getWorkScheduleEndTime(),
            'workShiftAssignment' => $attendance->getWorkShiftAssignment(),
        ]);
    }

    private function resolveAttendanceDateRange(
        Attendance $attendance,
    ): ?array {
        $workDate = $attendance->getWorkDate();
        $startTimeValue = $attendance->getWorkScheduleStartTime();
        $endTimeValue = $attendance->getWorkScheduleEndTime();

        if (!$workDate instanceof \DateTimeInterface || !$startTimeValue || !$endTimeValue) {
            return null;
        }

        $baseDate = DateTimeImmutable::createFromInterface($workDate);
        $startTime = parseAttendanceDateTime($baseDate, $startTimeValue);
        $endTime = parseAttendanceDateTime($baseDate, $endTimeValue);

        if (!$startTime || !$endTime) {
            return null;
        }

        if ($endTime <= $startTime) {
            $endTime = $endTime->modify('+1 day');
        }

        return [
            'startTime' => $startTime,
            'endTime' => $endTime,
        ];
    }

    private function buildAttendanceActionWindow(
        Attendance $attendance,
        array $generalSetting,
    ): ?array {
        $attendanceDateRange = $this->resolveAttendanceDateRange($attendance);
        if ($attendanceDateRange === null) {
            return null;
        }

        $attendanceSetting = $this->resolveAttendanceSetting(
            $attendance,
            $generalSetting,
        );
        $startTime = $attendanceDateRange['startTime'];
        $endTime = $attendanceDateRange['endTime'];

        if ($attendance->getAttendanceType() === AttendanceType::CheckIn->value) {
            $checkInEarliestMinutes = (int) ($attendanceSetting[self::CHECK_IN_EARLIEST_KEY] ?? 0);
            $lateLimitMinutes = (int) ($attendanceSetting[self::LATE_LIMIT_KEY] ?? 0);
            $lateLimitStartTime = $startTime->modify(
                sprintf('+%d minutes', $lateLimitMinutes),
            );
            $windowEnd = $lateLimitStartTime->getTimestamp() <= $endTime->getTimestamp()
                ? $lateLimitStartTime
                : $endTime;

            return [
                'windowStart' => $startTime->modify(
                    sprintf('-%d minutes', $checkInEarliestMinutes),
                ),
                'windowEnd' => $windowEnd,
                'anchorTime' => $startTime,
            ];
        }

        if ($attendance->getAttendanceType() === AttendanceType::CheckOut->value) {
            $checkOutGraceMinutes = (int) ($attendanceSetting[self::CHECK_OUT_GRACE_KEY] ?? 0);
            $checkOutLatestMinutes = (int) ($attendanceSetting[self::CHECK_OUT_LATEST_KEY] ?? 0);

            return [
                'windowStart' => $endTime->modify(
                    sprintf('-%d minutes', $checkOutGraceMinutes),
                ),
                'windowEnd' => $endTime->modify(
                    sprintf('+%d minutes', $checkOutLatestMinutes),
                ),
                'anchorTime' => $endTime,
            ];
        }

        return null;
    }

    private function resolveAttendanceSetting(
        Attendance $attendance,
        array $generalSetting,
    ): array {
        $configSnapshot = $attendance->getConfigSnapshot();

        if (!is_array($configSnapshot)) {
            return $generalSetting;
        }

        return array_replace($generalSetting, $configSnapshot);
    }
    public function throwMessageAttendanceInvalid(
        string $code,
        string|int $timeMinutes = '',
        ?Attendance $attendance = null,
    ): void {
        $message = t("attendance_error.$code", ['%time%' => $timeMinutes]);

        if ($attendance instanceof Attendance) {
            $message = sprintf(
                '%s (%s)',
                $message,
                $this->formatAttendanceLabel($attendance),
            );
        }

        throw new \Exception($message);
    }

    private function publishAttendanceRealtime(Attendance $attendance): void
    {
        $this->mercureService->attendance($attendance->jsonSerialize());
    }

    private function formatAttendanceLabel(Attendance $attendance): string
    {
        $startTime = $attendance->getWorkScheduleStartTime() ?? '--:--';
        $endTime = $attendance->getWorkScheduleEndTime() ?? '--:--';

        return sprintf('%s - %s', $startTime, $endTime);
    }
}
