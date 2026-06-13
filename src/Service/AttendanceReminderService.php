<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\CacheKey;
use App\Class\AttendanceType;
use App\Class\StatusAttendance;
use App\Entity\Attendance;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttendanceReminderService
{
    public const ATTENDANCE_CONFIG_SNAPSHOT_KEYS = [
        'CHECK_IN_GRACE_MINUTES',
        'LATE_LIMIT_MINUTES',
        'CHECK_IN_EARLIEST_MINUTES',
        'CHECK_OUT_GRACE_MINUTES',
        'CHECK_OUT_LATEST_MINUTES',
        'REMIND_MISSING_CHECK_IN',
        'REMIND_MISSING_CHECK_OUT',
        'CHECK_IN_REMINDER_MINUTES_BEFORE',
        'CHECK_OUT_REMINDER_MINUTES_BEFORE',
    ];

    private const REMIND_MISSING_CHECK_IN_KEY = 'REMIND_MISSING_CHECK_IN';
    private const REMIND_MISSING_CHECK_OUT_KEY = 'REMIND_MISSING_CHECK_OUT';
    private const CHECK_IN_REMINDER_MINUTES_BEFORE_KEY = 'CHECK_IN_REMINDER_MINUTES_BEFORE';
    private const CHECK_OUT_REMINDER_MINUTES_BEFORE_KEY = 'CHECK_OUT_REMINDER_MINUTES_BEFORE';

    public function __construct(
        private readonly AttendanceRepository $attendanceRepository,
        private readonly GeneralSettingRepository $generalSettingRepository,
        private readonly MercureService $mercureService,
        private readonly TranslatorInterface $translator,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * @return array{processedCount:int,sentCount:int}
     */
    public function processDueReminders(
        ?\DateTimeImmutable $now = null,
        int $fromUserId = 0,
    ): array {
        $resolvedNow = $now ?? new \DateTimeImmutable();
        $attendances = $this->attendanceRepository->findDueReminderAttendances(
            $resolvedNow,
        );
        $processedCount = 0;
        $sentCount = 0;

        foreach ($attendances as $attendance) {
            $actionDateTime = $this->getScheduledActionDateTime($attendance);
            $employee = $attendance->getEmployee();

            if (
                !$employee ||
                !$actionDateTime ||
                $actionDateTime <= $resolvedNow
            ) {
                $attendance->setReminderAt(null);
                $processedCount++;
                continue;
            }

            $payload = $this->buildReminderNotificationPayload(
                $attendance,
                $this->resolveUserLocale((int) $employee->getId()),
            );
            $this->mercureService->thongBaoCaNhan(
                $fromUserId,
                $employee->getId(),
                $payload['title'],
                $payload['body'],
                'warning',
                \DateTime::createFromImmutable($resolvedNow),
            );

            $attendance->setRemindedAt(\DateTime::createFromImmutable($resolvedNow));
            $processedCount++;
            $sentCount++;
        }

        return [
            'processedCount' => $processedCount,
            'sentCount' => $sentCount,
        ];
    }

    public function recalculateFutureScheduledReminderAttendances(
        ?array $settings = null,
        ?\DateTimeImmutable $now = null,
    ): int {
        $resolvedNow = $now ?? new \DateTimeImmutable();
        $resolvedSettings = $settings ?? $this->generalSettingRepository->getAllConfig();
        $attendances = $this->attendanceRepository
            ->findFutureScheduledAttendancesForReminderRecalculate(
                \DateTime::createFromImmutable(
                    $resolvedNow->modify('-1 day')->setTime(0, 0),
                ),
            );
        $updatedCount = 0;

        foreach ($attendances as $attendance) {
            $beforeState = $this->buildReminderStateHash($attendance);

            $this->syncReminderForAttendance(
                $attendance,
                $resolvedSettings,
                false,
                $resolvedNow,
            );

            if ($beforeState !== $this->buildReminderStateHash($attendance)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function syncReminderForAttendance(
        Attendance $attendance,
        ?array $settings = null,
        bool $resetRemindedAt = false,
        ?\DateTimeImmutable $now = null,
    ): void {
        $resolvedNow = $now ?? new \DateTimeImmutable();
        $resolvedSettings = $settings ?? $this->generalSettingRepository->getAllConfig();

        if ($attendance->getStatus() !== StatusAttendance::Scheduled->value) {
            $attendance->setReminderAt(null);

            if ($resetRemindedAt) {
                $attendance->setRemindedAt(null);
            }

            return;
        }

        $actionDateTime = $this->getScheduledActionDateTime($attendance);
        if (!$actionDateTime || $actionDateTime <= $resolvedNow) {
            $attendance->setReminderAt(null);

            if ($resetRemindedAt) {
                $attendance->setRemindedAt(null);
            }

            return;
        }

        if (!$this->isReminderEnabled($attendance, $resolvedSettings)) {
            $attendance->setReminderAt(null);

            if ($resetRemindedAt) {
                $attendance->setRemindedAt(null);
            }

            return;
        }

        $reminderAt = $this->resolveReminderAt($attendance, $resolvedSettings);
        $attendance->setReminderAt(
            $reminderAt
                ? \DateTime::createFromImmutable($reminderAt)
                : null,
        );

        if ($resetRemindedAt) {
            $attendance->setRemindedAt(null);
        }
    }

    public function resolveReminderAt(
        Attendance $attendance,
        ?array $settings = null,
    ): ?\DateTimeImmutable {
        $resolvedSettings = $settings ?? $this->generalSettingRepository->getAllConfig();
        $actionDateTime = $this->getScheduledActionDateTime($attendance);

        if (!$actionDateTime) {
            return null;
        }

        $minutesBefore = $attendance->getAttendanceType() === AttendanceType::CheckOut->value
            ? (int) ($resolvedSettings[self::CHECK_OUT_REMINDER_MINUTES_BEFORE_KEY] ?? 0)
            : (int) ($resolvedSettings[self::CHECK_IN_REMINDER_MINUTES_BEFORE_KEY] ?? 0);

        return $actionDateTime->modify(sprintf('-%d minutes', max(0, $minutesBefore)));
    }

    public function getScheduledActionDateTime(
        Attendance $attendance,
    ): ?\DateTimeImmutable {
        $workDate = $attendance->getWorkDate();

        if (!$workDate instanceof \DateTimeInterface) {
            return null;
        }

        $baseDate = \DateTimeImmutable::createFromInterface($workDate);
        $startTime = $attendance->getWorkScheduleStartTime();
        $endTime = $attendance->getWorkScheduleEndTime();

        if ($attendance->getAttendanceType() === AttendanceType::CheckIn->value) {
            return $startTime ? parseAttendanceDateTime($baseDate, $startTime) : null;
        }

        if ($attendance->getAttendanceType() !== AttendanceType::CheckOut->value || !$endTime) {
            return null;
        }

        $startDateTime = $startTime
            ? parseAttendanceDateTime($baseDate, $startTime)
            : null;
        $endDateTime = parseAttendanceDateTime($baseDate, $endTime);

        if (!$endDateTime) {
            return null;
        }

        if ($startDateTime && $endDateTime <= $startDateTime) {
            return $endDateTime->modify('+1 day');
        }

        return $endDateTime;
    }

    /**
     * @return array{title:string,body:string}
     */
    private function buildReminderNotificationPayload(
        Attendance $attendance,
        ?string $locale = null,
    ): array {
        $startTime = $attendance->getWorkScheduleStartTime() ?? '--:--';
        $endTime = $attendance->getWorkScheduleEndTime() ?? '--:--';
        $actionTime = $attendance->getAttendanceType() === AttendanceType::CheckOut->value
            ? $endTime
            : $startTime;

        if ($attendance->getAttendanceType() === AttendanceType::CheckOut->value) {
            return [
                'title' => $this->translator->trans(
                    'request.notification.attendance_check_out_reminder',
                    [],
                    null,
                    $locale,
                ),
                'body' => $this->translator->trans(
                    'request.notification.attendance_check_out_reminder_body',
                    [
                        '%startTime%' => $startTime,
                        '%endTime%' => $endTime,
                        '%actionTime%' => $actionTime,
                    ],
                    null,
                    $locale,
                ),
            ];
        }

        return [
            'title' => $this->translator->trans(
                'request.notification.attendance_check_in_reminder',
                [],
                null,
                $locale,
            ),
            'body' => $this->translator->trans(
                'request.notification.attendance_check_in_reminder_body',
                [
                    '%startTime%' => $startTime,
                    '%endTime%' => $endTime,
                    '%actionTime%' => $actionTime,
                ],
                null,
                $locale,
            ),
        ];
    }

    private function resolveUserLocale(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        $locale = $this->cacheService->get(
            sprintf(CacheKey::USER_LOCALE, $userId),
        );

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    private function isReminderEnabled(
        Attendance $attendance,
        array $settings,
    ): bool {
        $key = $attendance->getAttendanceType() === AttendanceType::CheckOut->value
            ? self::REMIND_MISSING_CHECK_OUT_KEY
            : self::REMIND_MISSING_CHECK_IN_KEY;

        return $this->normalizeBooleanSetting($settings[$key] ?? null);
    }

    private function normalizeBooleanSetting(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'TRUE'], true);
    }

    private function buildReminderStateHash(Attendance $attendance): string
    {
        return implode('|', [
            $attendance->getReminderAt()?->format(DATE_ATOM) ?? '',
            $attendance->getRemindedAt()?->format(DATE_ATOM) ?? '',
        ]);
    }
}
