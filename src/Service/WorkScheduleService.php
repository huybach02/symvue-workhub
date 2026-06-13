<?php

namespace App\Service;

use App\Class\AttendanceType;
use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\Class\Request\RequestConstant;
use App\DTO\WorkScheduleDTO;
use App\DTO\WorkScheduleFulltimeDTO;
use App\DTO\WorkScheduleFulltimeOverrideDTO;
use App\Entity\Attendance;
use App\Entity\Example;
use App\Entity\FixedSchedule;
use App\Entity\FixedScheduleGroup;
use App\Entity\FixedScheduleOverride;
use App\Entity\HolidaySchedule;
use App\Entity\LeaveSchedule;
use App\Entity\User;
use App\Repository\ExampleRepository;
use App\Repository\AttendanceRepository;
use App\Repository\FixedScheduleGroupRepository;
use App\Repository\FixedScheduleOverrideRepository;
use App\Repository\GeneralSettingRepository;
use App\Repository\HolidayScheduleRepository;
use App\Repository\LeaveScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\WorkingTimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\WorkScheduleParttimeAssignDTO;
use App\Entity\WorkShiftAssignment;
use App\Repository\WorkShiftAssignmentRepository;
use App\Repository\WorkShiftRepository;

class WorkScheduleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly WorkingTimeRepository $workingTimeRepository,
        private readonly HolidayScheduleRepository $holidayScheduleRepository,
        private readonly FixedScheduleGroupRepository $fixedScheduleGroupRepository,
        private readonly FixedScheduleOverrideRepository $fixedScheduleOverrideRepository,
        private readonly LeaveScheduleRepository $leaveScheduleRepository,
        private readonly DepartmentService $departmentService,
        private readonly WorkShiftAssignmentRepository $workShiftAssignmentRepository,
        private readonly WorkShiftRepository $workShiftRepository,
        private readonly AttendanceRepository $attendanceRepository,
        private readonly GeneralSettingRepository $generalSettingRepository,
        private readonly AttendanceReminderService $attendanceReminderService,
    ) {}

    public function getHolidaySchedule(): array
    {
        $holidaySchedules = $this->holidayScheduleRepository->findBy(
            ["status" => true],
            ["date" => "ASC", "id" => "ASC"],
        );

        $holidayRanges = [];

        foreach ($holidaySchedules as $holidaySchedule) {
            $date = $holidaySchedule->getDate();
            $year = $holidaySchedule->getYear();

            if (!$date instanceof \DateTime || $year === null) {
                continue;
            }

            $holidayRanges[$year][] = [
                "id" => $holidaySchedule->getId(),
                "code" => $holidaySchedule->getCode() ?? "",
                "name" => $holidaySchedule->getName() ?? "",
                "start" => $date->format("Y-m-d"),
                "end" => $date->format("Y-m-d"),
                "status" => $holidaySchedule->isStatus(),
            ];
        }

        return $holidayRanges;
    }

    public function getWorkingScheduleForUserOnDate(
        User $user,
        ?\DateTimeInterface $date = null,
    ): array {
        $workDate = $date
            ? \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)
            : new \DateTimeImmutable("today");

        $workType = $user->getHinhThucLamViec();
        $basePayload = [
            "date" => $workDate->format("Y-m-d"),
            "workType" => $workType,
            "isWorkingDay" => false,
            "source" => null,
            "schedules" => [],
        ];

        if ($this->hasApprovedLeaveOnDate($user, $workDate)) {
            return [
                ...$basePayload,
                "source" => "leave_schedule",
            ];
        }

        return match ($workType) {
            Constanst::HINH_THUC_LAM_VIEC["FULL_TIME"] => $this->resolveFulltimeScheduleForDate(
                $user,
                $workDate,
                $basePayload,
            ),
            Constanst::HINH_THUC_LAM_VIEC["PART_TIME"] => $this->resolveParttimeScheduleForDate(
                $user,
                $workDate,
                $basePayload,
            ),
            default => $basePayload,
        };
    }

    public function getFulltime(int $departmentId): array
    {
        $members = array_map(
            fn(array $member) => (object) $member,
            array_values(
                array_filter(
                    $this->departmentService->getMembersByDepartment(
                        $departmentId,
                    ),
                    fn(array $member): bool => ($member["hinhThucLamViec"] ?? "") === Constanst::HINH_THUC_LAM_VIEC["FULL_TIME"],
                ),
            ),
        );
        $memberIds = array_map(
            fn(object $member) => $member->id,
            $members,
        );

        if ($memberIds === []) {
            return [
                "users" => [],
                "events" => [],
            ];
        }

        $groups = $this->fixedScheduleGroupRepository->findBy([
            "member" => $memberIds,
            "type" => "fulltime",
        ]);
        $overrides = $this->fixedScheduleOverrideRepository->findBy([
            "member" => $memberIds,
        ]);
        $leaveSchedules = $this->leaveScheduleRepository->findBy([
            "member" => $memberIds,
            "status" => RequestConstant::STATUS_APPROVED,
        ]);

        // Lấy danh sách ngày nghỉ lễ để loại trừ
        $holidayDates = $this->getHolidayDatesMap();

        $workEvents = [];
        $leaveEvents = [];

        foreach ($groups as $group) {
            foreach ($this->buildEventsFromFixedScheduleGroup($group, $holidayDates) as $event) {
                $workEvents[$this->getEventMapKey($event)] = $event;
            }
        }

        foreach ($overrides as $override) {
            foreach ($this->buildEventsFromOverride($override) as $event) {
                $workEvents[$this->getEventMapKey($event)] = $event;
            }
        }

        foreach ($leaveSchedules as $leaveSchedule) {
            foreach ($this->buildEventsFromLeaveSchedule($leaveSchedule) as $event) {
                $leaveEvents[] = $event;
            }
        }

        $events = [...array_values($workEvents), ...$leaveEvents];

        usort(
            $events,
            fn(array $left, array $right): int => strcmp(
                sprintf(
                    "%s-%s-%d-%s",
                    $left['date'],
                    $left['user_id'],
                    $this->getEventSortOrder($left),
                    $left['startTime'] ?? '99:99',
                ),
                sprintf(
                    "%s-%s-%d-%s",
                    $right['date'],
                    $right['user_id'],
                    $this->getEventSortOrder($right),
                    $right['startTime'] ?? '99:99',
                ),
            ),
        );

        return [
            "users" => array_map(
                fn(object $member) => (array) $member,
                $members,
            ),
            "events" => array_values($events),
            "holidayRanges" => $this->getHolidaySchedule(),
        ];
    }

    public function createFulltime(WorkScheduleFulltimeDTO $dto): array
    {
        $users = $this->userRepository->findBy(["id" => $dto->userIds]);

        if (count($users) !== count(array_unique($dto->userIds))) {
            throw new \Exception(t('error.not_found'));
        }

        $workingTimes = $this->workingTimeRepository->findAll();

        if ($workingTimes === []) {
            throw new \Exception("Chưa thiết lập thời gian làm việc cố định.");
        }

        $createdGroups = [];

        foreach ($users as $user) {
            $fixedScheduleGroup = $this->fixedScheduleGroupRepository->findOneBy([
                "member" => $user,
                "type" => "fulltime",
            ]);

            if (!$fixedScheduleGroup) {
                $fixedScheduleGroup = new FixedScheduleGroup();
            };

            $fixedScheduleGroup->setMember($user);
            $fixedScheduleGroup->setStartDate(new \DateTime($dto->startDate));
            $fixedScheduleGroup->setEndDate(new \DateTime($dto->endDate));
            $fixedScheduleGroup->setType("fulltime");

            $this->entityManager->persist($fixedScheduleGroup);

            foreach ($workingTimes as $workingTime) {
                $fixedSchedule = new FixedSchedule();
                $fixedSchedule->setFixedScheduleGroup($fixedScheduleGroup);
                $fixedSchedule->setDayOfWeek($workingTime->getDayOfWeek());
                $fixedSchedule->setStartTime(
                    \DateTime::createFromFormat(
                        "H:i",
                        $workingTime->getGioBatDau(),
                    ) ?: null,
                );
                $fixedSchedule->setEndTime(
                    \DateTime::createFromFormat(
                        "H:i",
                        $workingTime->getGioKetThuc(),
                    ) ?: null,
                );

                $this->entityManager->persist($fixedSchedule);
            }

            $createdGroups[] = $fixedScheduleGroup;
        }

        $this->entityManager->flush();

        foreach ($users as $user) {
            $this->resyncFutureScheduledAttendancesForUser(
                $user,
                new \DateTime($dto->startDate),
                new \DateTime($dto->endDate),
            );
        }

        $this->entityManager->flush();

        return array_map(
            fn(FixedScheduleGroup $group) => $group->jsonSerialize(),
            $createdGroups,
        );
    }

    public function clearFulltime(int $departmentId, int $userId): array
    {
        $fixedScheduleGroup = $this->entityManager->getRepository(FixedScheduleGroup::class)->findOneBy([
            "member" => $userId,
            "type" => "fulltime",
        ]);

        if (!$fixedScheduleGroup) {
            throw new \Exception(t('error.not_found'));
        }

        $member = $fixedScheduleGroup->getMember();
        $resyncStartDate = $fixedScheduleGroup->getStartDate();
        $resyncEndDate = $fixedScheduleGroup->getEndDate();

        $fixedSchedules = $fixedScheduleGroup->getFixedSchedules();

        foreach ($fixedSchedules as $fixedSchedule) {
            $this->entityManager->remove($fixedSchedule);
        }

        $this->entityManager->remove($fixedScheduleGroup);

        $this->fixedScheduleOverrideRepository->clearOverridesByUser($userId);

        $this->entityManager->flush();

        if ($member && $resyncStartDate && $resyncEndDate) {
            $this->resyncFutureScheduledAttendancesForUser(
                $member,
                $resyncStartDate,
                $resyncEndDate,
            );
            $this->entityManager->flush();
        }

        return $fixedScheduleGroup->jsonSerialize();
    }

    public function checkOverrideFulltime(int $userId, string $startDate, string $endDate): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $startDateObject = new \DateTime($startDate);
        $endDateObject = new \DateTime($endDate);

        if ($startDateObject > $endDateObject) {
            return [
                'hasOverlap' => false,
                'message' => t('work_schedule.invalid_date_range'),
            ];
        }

        $overlappingOverrides = $this->fixedScheduleOverrideRepository
            ->findOverlappingOverrides($user, $startDateObject, $endDateObject);

        if (empty($overlappingOverrides)) {
            return [
                'hasOverlap' => false,
                'message' => '',
            ];
        }

        // Tính toán khoảng thời gian tổng hợp và số lượng
        $count = count($overlappingOverrides);
        $minStart = $startDateObject;
        $maxEnd = $endDateObject;

        foreach ($overlappingOverrides as $override) {
            $overrideStart = $override->getStartDate();
            $overrideEnd = $override->getEndDate();

            if ($overrideStart && $overrideStart < $minStart) {
                $minStart = $overrideStart;
            }
            if ($overrideEnd && $overrideEnd > $maxEnd) {
                $maxEnd = $overrideEnd;
            }
        }

        return [
            'hasOverlap' => true,
            'message' => t('work_schedule.has_override_overlap', [
                '%count%' => $count,
                '%startDate%' => $minStart->format('d/m/Y'),
                '%endDate%' => $maxEnd->format('d/m/Y'),
            ]),
        ];
    }

    public function overrideFulltime(WorkScheduleFulltimeOverrideDTO $dto): array
    {
        $user = $this->userRepository->find($dto->userId);
        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $newStartDate = new \DateTime($dto->startDate);
        $newEndDate = new \DateTime($dto->endDate);
        $newStartTime = \DateTime::createFromFormat('H:i:s', $dto->startTime) ?: \DateTime::createFromFormat('H:i', $dto->startTime);
        $newEndTime = \DateTime::createFromFormat('H:i:s', $dto->endTime) ?: \DateTime::createFromFormat('H:i', $dto->endTime);

        // Tính toán các segments dựa trên weekendOption
        $segments = $this->calculateOverrideSegments(
            $newStartDate,
            $newEndDate,
            $dto->weekendOption,
            $dto->selectedDates
        );

        $createdOverrides = [];

        foreach ($segments as $segment) {
            // Tìm các override overlap với segment này
            $overlappingOverrides = $this->fixedScheduleOverrideRepository->findOverlappingOverrides(
                $user,
                $segment['start'],
                $segment['end']
            );

            // Xử lý cắt đoạn từng override cũ
            foreach ($overlappingOverrides as $existingOverride) {
                $this->processOverrideSplit($existingOverride, $segment['start'], $segment['end']);
            }

            // Tạo override mới cho segment
            $override = new FixedScheduleOverride();
            $override->setMember($user);
            $override->setStartDate($segment['start']);
            $override->setEndDate($segment['end']);
            $override->setStartTime($newStartTime);
            $override->setEndTime($newEndTime);
            $override->setReason($dto->note ?? '');
            $override->setType('fulltime');
            $this->entityManager->persist($override);

            $createdOverrides[] = $override;
        }

        $this->entityManager->flush();

        $this->resyncFutureScheduledAttendancesForUser(
            $user,
            new \DateTime($dto->startDate),
            new \DateTime($dto->endDate),
        );
        $this->entityManager->flush();

        // Trả về danh sách các override đã tạo
        return array_map(fn($o) => $o->jsonSerialize(), $createdOverrides);
    }

    private function processOverrideSplit(
        FixedScheduleOverride $existing,
        \DateTime $newStart,
        \DateTime $newEnd
    ): void {
        $existingStart = $existing->getStartDate();
        $existingEnd = $existing->getEndDate();

        if (!$existingStart || !$existingEnd) {
            $this->entityManager->remove($existing);
            return;
        }

        // Chuyển về dạng timestamp để so sánh (bỏ qua time)
        $newStartTs = (clone $newStart)->setTime(0, 0)->getTimestamp();
        $newEndTs = (clone $newEnd)->setTime(0, 0)->getTimestamp();
        $existStartTs = (clone $existingStart)->setTime(0, 0)->getTimestamp();
        $existEndTs = (clone $existingEnd)->setTime(0, 0)->getTimestamp();

        // Case 1: New bao phủ hoàn toàn existing -> xóa existing
        if ($newStartTs <= $existStartTs && $newEndTs >= $existEndTs) {
            $this->entityManager->remove($existing);
            return;
        }

        // Case 2: Existing bao phủ hoàn toàn new -> cắt thành 2 phần
        if ($newStartTs > $existStartTs && $newEndTs < $existEndTs) {
            // Phần trước: existingStart -> newStart - 1 ngày
            $leftPart = new FixedScheduleOverride();
            $leftPart->setMember($existing->getMember());
            $leftPart->setStartDate($existingStart);
            $leftPart->setEndDate((clone $newStart)->modify('-1 day'));
            $leftPart->setStartTime($existing->getStartTime());
            $leftPart->setEndTime($existing->getEndTime());
            $leftPart->setType($existing->getType());
            $leftPart->setReason($existing->getReason());
            $this->entityManager->persist($leftPart);

            // Phần sau: newEnd + 1 ngày -> existingEnd
            $rightPart = new FixedScheduleOverride();
            $rightPart->setMember($existing->getMember());
            $rightPart->setStartDate((clone $newEnd)->modify('+1 day'));
            $rightPart->setEndDate($existingEnd);
            $rightPart->setStartTime($existing->getStartTime());
            $rightPart->setEndTime($existing->getEndTime());
            $rightPart->setType($existing->getType());
            $rightPart->setReason($existing->getReason());
            $this->entityManager->persist($rightPart);

            $this->entityManager->remove($existing);
            return;
        }

        // Case 3: Chồng bên trái (new start < existing start, new end trong khoảng existing)
        if ($newStartTs <= $existStartTs && $newEndTs >= $existStartTs && $newEndTs < $existEndTs) {
            // Giữ lại phần bên phải: newEnd + 1 ngày -> existingEnd
            $existing->setStartDate((clone $newEnd)->modify('+1 day'));
            return;
        }

        // Case 4: Chồng bên phải (new start trong khoảng existing, new end > existing end)
        if ($newStartTs > $existStartTs && $newStartTs <= $existEndTs && $newEndTs >= $existEndTs) {
            // Giữ lại phần bên trái: existingStart -> newStart - 1 ngày
            $existing->setEndDate((clone $newStart)->modify('-1 day'));
            return;
        }
    }

    private function calculateOverrideSegments(
        \DateTime $start,
        \DateTime $end,
        string $weekendOption,
        array $selectedDates
    ): array {
        $holidays = $this->holidayScheduleRepository->findByDateRange($start, $end);
        $holidayMap = [];
        foreach ($holidays as $holiday) {
            $holidayMap[$holiday->getDate()->format("Y-m-d")] = true;
        }

        $workingTimes = $this->workingTimeRepository->findAll();
        $workingDays = [];
        foreach ($workingTimes as $wt) {
            if ($wt->getGioBatDau() && $wt->getGioKetThuc()) {
                $workingDays[$wt->getDayOfWeek()] = true;
            }
        }

        $selectedMap = [];
        foreach ($selectedDates as $date) {
            $selectedMap[$date] = true;
        }

        $segments = [];
        $currentSegmentStart = null;
        $currentDate = clone $start;

        while ($currentDate <= $end) {
            $dateStr = $currentDate->format("Y-m-d");
            $dayOfWeek = (int) $currentDate->format("N");

            $isHoliday = isset($holidayMap[$dateStr]);
            $isWorkingDay = isset($workingDays[$dayOfWeek]);

            $shouldInclude = false;
            if ($weekendOption === 'keep') {
                $shouldInclude = $isWorkingDay && !$isHoliday;
            } else {
                $shouldInclude = isset($selectedMap[$dateStr]) || ($isWorkingDay && !$isHoliday);
            }

            if ($shouldInclude) {
                if ($currentSegmentStart === null) {
                    $currentSegmentStart = clone $currentDate;
                }
            } else {
                if ($currentSegmentStart !== null) {
                    $segments[] = [
                        'start' => $currentSegmentStart,
                        'end' => (clone $currentDate)->modify('-1 day'),
                    ];
                    $currentSegmentStart = null;
                }
            }

            $currentDate->modify('+1 day');
        }

        // Đóng segment cuối nếu còn
        if ($currentSegmentStart !== null) {
            $segments[] = [
                'start' => $currentSegmentStart,
                'end' => clone $end,
            ];
        }

        return $segments;
    }

    private function getHolidayDatesMap(): array
    {
        $holidays = $this->holidayScheduleRepository->findBy(["status" => true]);
        $holidayDates = [];
        foreach ($holidays as $holiday) {
            $holidayDates[$holiday->getDate()->format("Y-m-d")] = true;
        }
        return $holidayDates;
    }

    private function hasApprovedLeaveOnDate(
        User $user,
        \DateTimeInterface $date,
    ): bool {
        $leaveSchedules = $this->leaveScheduleRepository->findBy([
            "member" => $user,
            "status" => RequestConstant::STATUS_APPROVED,
        ]);

        $dateKey = $date->format("Y-m-d");

        foreach ($leaveSchedules as $leaveSchedule) {
            $startDate = $leaveSchedule->getStartDatetime()?->format("Y-m-d");
            $endDate = $leaveSchedule->getEndDatetime()?->format("Y-m-d");

            if (!$startDate || !$endDate) {
                continue;
            }

            if ($dateKey >= $startDate && $dateKey <= $endDate) {
                return true;
            }
        }

        return false;
    }

    private function resolveFulltimeScheduleForDate(
        User $user,
        \DateTimeImmutable $workDate,
        array $basePayload,
    ): array {
        $overrides = $this->fixedScheduleOverrideRepository->findOverlappingOverrides(
            $user,
            $workDate,
            $workDate,
        );

        if ($overrides !== []) {
            $override = $overrides[0];

            return [
                ...$basePayload,
                "isWorkingDay" => true,
                "source" => "fixed_schedule_override",
                "schedules" => [[
                    "startTime" => $override->getStartTime()?->format("H:i"),
                    "endTime" => $override->getEndTime()?->format("H:i"),
                ]],
            ];
        }

        $isHoliday = $this->holidayScheduleRepository->findOneBy([
            "date" => \DateTime::createFromInterface($workDate),
            "status" => true,
        ]);
        if ($isHoliday) {
            return $basePayload;
        }

        $group = $this->fixedScheduleGroupRepository->findOneBy([
            "member" => $user,
            "type" => "fulltime",
        ]);

        if (
            !$group ||
            !$group->getStartDate() ||
            !$group->getEndDate()
        ) {
            return $basePayload;
        }

        $dateKey = $workDate->format("Y-m-d");
        $groupStartDate = $group->getStartDate()->format("Y-m-d");
        $groupEndDate = $group->getEndDate()->format("Y-m-d");

        if ($dateKey < $groupStartDate || $dateKey > $groupEndDate) {
            return $basePayload;
        }

        $dayOfWeek = (int) $workDate->format("N");
        foreach ($group->getFixedSchedules() as $schedule) {
            if (
                $schedule->getDayOfWeek() !== $dayOfWeek ||
                !$schedule->getStartTime() ||
                !$schedule->getEndTime()
            ) {
                continue;
            }

            return [
                ...$basePayload,
                "isWorkingDay" => true,
                "source" => "fixed_schedule",
                "schedules" => [[
                    "startTime" => $schedule->getStartTime()->format("H:i"),
                    "endTime" => $schedule->getEndTime()->format("H:i"),
                ]],
            ];
        }

        return $basePayload;
    }

    private function resolveParttimeScheduleForDate(
        User $user,
        \DateTimeImmutable $workDate,
        array $basePayload,
    ): array {
        $assignments = $this->workShiftAssignmentRepository->findBy([
            "member" => $user,
            "date" => \DateTime::createFromInterface($workDate),
        ]);

        if ($assignments === []) {
            return $basePayload;
        }

        $schedules = [];

        foreach ($assignments as $assignment) {
            $workShift = $assignment->getWorkShift();
            if (
                !$workShift ||
                !$workShift->isStatus() ||
                !$workShift->getGioBatDau() ||
                !$workShift->getGioKetThuc()
            ) {
                continue;
            }

            $schedules[] = [
                "workShiftId" => $workShift->getId(),
                "workingTimeId" => $workShift->getThoiGianLamViec()?->getId(),
                "startTime" => formatTimeString($workShift->getGioBatDau()),
                "endTime" => formatTimeString($workShift->getGioKetThuc()),
                "note" => $workShift->getGhiChu() ?? "",
            ];
        }

        usort(
            $schedules,
            fn(array $left, array $right): int => strcmp(
                $left["startTime"],
                $right["startTime"],
            ),
        );

        if ($schedules === []) {
            return $basePayload;
        }

        return [
            ...$basePayload,
            "isWorkingDay" => true,
            "source" => "work_shift_assignment",
            "schedules" => array_values($schedules),
        ];
    }

    private function buildEventsFromFixedScheduleGroup(FixedScheduleGroup $group, array $holidayDates = []): array
    {
        $events = [];
        $scheduleByDay = [];

        foreach ($group->getFixedSchedules() as $schedule) {
            $scheduleByDay[$schedule->getDayOfWeek()] = $schedule;
        }

        $currentDate = (clone $group->getStartDate())->setTime(0, 0);
        $endDate = (clone $group->getEndDate())->setTime(0, 0);

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format("Y-m-d");

            // Bỏ qua ngày nghỉ lễ
            if (isset($holidayDates[$dateStr])) {
                $currentDate->modify("+1 day");
                continue;
            }

            $dayOfWeek = (int) $currentDate->format("N");
            $schedule = $scheduleByDay[$dayOfWeek] ?? null;

            if ($schedule) {
                $events[] = $this->makeEventItem(
                    id: sprintf(
                        "fixed-%d-%s",
                        $group->getId(),
                        $currentDate->format("Ymd"),
                    ),
                    userId: $group->getMember()?->getId(),
                    date: $dateStr,
                    startTime: $schedule->getStartTime()?->format("H:i"),
                    endTime: $schedule->getEndTime()?->format("H:i"),
                    color: "blue",
                    source: "fixed_schedule",
                );
            }

            $currentDate->modify("+1 day");
        }

        return $events;
    }

    private function buildEventsFromOverride(FixedScheduleOverride $override): array
    {
        $events = [];
        $startDate = $override->getStartDate();
        $endDate = $override->getEndDate();

        if (!$startDate || !$endDate) {
            return [];
        }

        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format("Y-m-d");

            $events[] = $this->makeEventItem(
                id: sprintf(
                    "override-%d-%s",
                    $override->getId(),
                    $currentDate->format("Ymd"),
                ),
                userId: $override->getMember()?->getId(),
                date: $dateStr,
                startTime: $override->getStartTime()?->format("H:i"),
                endTime: $override->getEndTime()?->format("H:i"),
                color: "orange",
                source: "fixed_schedule_override",
            );

            $currentDate->modify("+1 day");
        }

        return $events;
    }

    private function buildEventsFromLeaveSchedule(LeaveSchedule $leaveSchedule): array
    {
        $events = [];
        $currentDate = (clone $leaveSchedule->getStartDatetime())->setTime(0, 0);
        $endDate = (clone $leaveSchedule->getEndDatetime())->setTime(0, 0);

        while ($currentDate <= $endDate) {
            $events[] = [
                "id" => sprintf(
                    "leave-%d-%s",
                    $leaveSchedule->getId(),
                    $currentDate->format("Ymd"),
                ),
                "user_id" => $leaveSchedule->getMember()?->getId(),
                "date" => $currentDate->format("Y-m-d"),
                "startTime" => null,
                "endTime" => null,
                "title" => $this->formatLeaveTitle($leaveSchedule),
                "color" => "red",
                "source" => "leave_schedule",
            ];

            $currentDate->modify("+1 day");
        }

        return $events;
    }

    private function makeEventItem(
        string $id,
        ?int $userId,
        string $date,
        ?string $startTime,
        ?string $endTime,
        string $color,
        string $source,
    ): array {
        return [
            "id" => $id,
            "user_id" => $userId,
            "date" => $date,
            "startTime" => $startTime,
            "endTime" => $endTime,
            "title" => $startTime && $endTime
                ? sprintf("%s - %s", $startTime, $endTime)
                : "",
            "color" => $color,
            "source" => $source,
        ];
    }

    private function formatLeaveTitle(LeaveSchedule $leaveSchedule): string
    {
        return match ($leaveSchedule->getType()) {
            "annual_leave" => "Nghỉ phép",
            "unpaid_leave" => "Nghỉ không lương",
            "personal_leave" => "Nghỉ việc riêng",
            default => "Nghỉ phép",
        };
    }

    private function getEventMapKey(array $event): string
    {
        return sprintf("%s-%s", $event["user_id"], $event["date"]);
    }

    private function getEventSortOrder(array $event): int
    {
        return match ($event['source'] ?? '') {
            'fixed_schedule' => 1,
            'fixed_schedule_override' => 2,
            'leave_schedule' => 3,
            default => 9,
        };
    }

    public function getSpecialDays(?string $startDate, ?string $endDate): array
    {
        if (!$startDate || !$endDate) {
            throw new \Exception("Vui lòng cung cấp startDate và endDate");
        }

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        if ($start > $end) {
            throw new \Exception("Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc");
        }

        // Lấy danh sách ngày lễ trong khoảng
        $holidays = $this->holidayScheduleRepository->findByDateRange($start, $end);
        $holidayMap = [];
        foreach ($holidays as $holiday) {
            $dateKey = $holiday->getDate()->format("Y-m-d");
            $holidayMap[$dateKey] = $holiday->getName() ?: "Ngày lễ";
        }

        // Mapping ngày trong tuần
        $dayOfWeekMap = [
            1 => "Thứ 2",
            2 => "Thứ 3",
            3 => "Thứ 4",
            4 => "Thứ 5",
            5 => "Thứ 6",
            6 => "Thứ 7",
            7 => "Chủ nhật",
        ];

        $specialDays = [];
        $currentDate = clone $start;

        $workingTimeByDay = [];
        foreach ($this->workingTimeRepository->findAll() as $workingTime) {
            if ($workingTime->getGioBatDau() && $workingTime->getGioKetThuc()) {
                $workingTimeByDay[$workingTime->getDayOfWeek()] = $workingTime;
            }
        }

        while ($currentDate <= $end) {
            $dateStr = $currentDate->format("Y-m-d");
            $dayOfWeek = (int) $currentDate->format("N"); // 1=Thứ 2, 7=Chủ nhật

            $hasWorkingTime = isset($workingTimeByDay[$dayOfWeek]);
            $isHoliday = isset($holidayMap[$dateStr]);
            $isOfflineDay = !$hasWorkingTime;

            if ($isOfflineDay || $isHoliday) {
                $type = $isOfflineDay && $isHoliday ? "both" : ($isHoliday ? "holiday" : "offline");

                $specialDays[] = [
                    "date" => $dateStr,
                    "dayOfWeek" => $dayOfWeek,
                    "dayLabel" => $dayOfWeekMap[$dayOfWeek],
                    "type" => $type,
                    "holidayName" => $isHoliday ? $holidayMap[$dateStr] : null,
                ];
            }

            $currentDate->modify("+1 day");
        }

        return $specialDays;
    }

    public function getParttimeShifts(?string $startDate, ?string $endDate, ?string $departmentId = null): array
    {
        if (!$startDate || !$endDate) {
            throw new \Exception("Vui lòng cung cấp startDate và endDate");
        }

        $start = (new \DateTime($startDate))->setTime(0, 0);
        $end = (new \DateTime($endDate))->setTime(0, 0);

        if ($start > $end) {
            throw new \Exception("Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc");
        }

        $shiftsByDay = [];
        $workingTimes = $this->workingTimeRepository->findBy([], ["dayOfWeek" => "ASC", "id" => "ASC"]);
        $assignmentMap = [];
        $allowedMemberIds = null;

        if ($departmentId) {
            $allowedMemberIds = array_map(
                fn(User $member): int => $member->getId(),
                $this->userRepository->getListParttimeMembersByDepartmentId($departmentId),
            );
        }

        $assignments = $allowedMemberIds === []
            ? []
            : $this->workShiftAssignmentRepository->findByDateRange($start, $end, $allowedMemberIds);

        // Lấy ra danh sách nhân sự cho từng ca của từng ngày
        foreach ($assignments as $assignment) {
            $member = $assignment->getMember();
            $workShift = $assignment->getWorkShift();
            $assignmentDate = $assignment->getDate();

            if (!$member || !$workShift || !$assignmentDate) {
                continue;
            }

            $assignmentKey = sprintf(
                '%s-%d',
                $assignmentDate->format('Y-m-d'),
                $workShift->getId(),
            );

            $assignmentMap[$assignmentKey][] = [
                'id' => $member->getId(),
                'name' => $member->getName(),
                'email' => $member->getEmail(),
                'image' => $member->getImage(),
            ];
        }

        // Lấy ra danh sách ca làm việc của từng ngày trong tuần và thông tin chi tiết ca làm việc đó
        foreach ($workingTimes as $workingTime) {
            $dayOfWeek = $workingTime->getDayOfWeek();

            if (!$dayOfWeek) {
                continue;
            }

            foreach ($workingTime->getCaLamViecs() as $workShift) {
                if (
                    !$workShift->isStatus() ||
                    !$workShift->getGioBatDau() ||
                    !$workShift->getGioKetThuc()
                ) {
                    continue;
                }

                $shiftsByDay[$dayOfWeek][] = [
                    "workingTimeId" => $workingTime->getId(),
                    "workShiftId" => $workShift->getId(),
                    "startTime" => formatTimeString($workShift->getGioBatDau()),
                    "endTime" => formatTimeString($workShift->getGioKetThuc()),
                    "color" => $workShift->getColor() ?? "#2e7d32",
                    "note" => $workShift->getGhiChu() ?? "",
                ];
            }
        }

        foreach ($shiftsByDay as &$dayShifts) {
            usort($dayShifts, fn(array $a, array $b): int => strcmp($a["startTime"], $b["startTime"]));
        }
        unset($dayShifts);

        $shifts = [];
        $currentDate = clone $start;

        while ($currentDate <= $end) {
            $date = $currentDate->format("Y-m-d");
            $dayOfWeek = (int) $currentDate->format("N");

            foreach (($shiftsByDay[$dayOfWeek] ?? []) as $index => $shift) {
                $assignmentKey = sprintf('%s-%d', $date, $shift["workShiftId"]);
                $assignedMembers = $assignmentMap[$assignmentKey] ?? [];

                $shifts[] = [
                    "id" => sprintf("%s-shift-%d", $date, $shift["workShiftId"]),
                    "workShiftId" => $shift["workShiftId"],
                    "workingTimeId" => $shift["workingTimeId"],
                    "title" => sprintf("Ca %d", $index + 1),
                    "date" => $date,
                    "startTime" => $shift["startTime"],
                    "endTime" => $shift["endTime"],
                    "color" => $shift["color"],
                    "assignedMembers" => $assignedMembers,
                    "assignedUserIds" => array_values(array_map(
                        fn(array $member): ?int => $member["id"] ?? null,
                        $assignedMembers,
                    )),
                    "note" => $shift["note"],
                ];
            }

            $currentDate->modify("+1 day");
        }

        return ["shifts" => $shifts];
    }

    public function getParttimeMembers(?string $departmentId, ?string $shiftId, ?string $date): array
    {
        $optionMembers = [];
        $memberAssigneds = [];

        if (!$departmentId || !$shiftId) {
            throw new \Exception("Vui lòng cung cấp departmentId và shiftId");
        }

        $members = $this->userRepository->getListParttimeMembersByDepartmentId($departmentId);
        $allowedMemberIds = [];

        foreach ($members as $member) {
            $allowedMemberIds[$member->getId()] = true;
            $optionMembers[] = [
                "title" => $member->getName(),
                "value" => $member->getId(),
                "image" => $member->getImage(),
                "email" => $member->getEmail(),
            ];
        }

        $assignments = $this->workShiftAssignmentRepository->findBy(["workShift" => $shiftId, "date" => new \DateTime($date)]);
        foreach ($assignments as $assignment) {
            $member = $assignment->getMember();
            if (!$member || !isset($allowedMemberIds[$member->getId()])) {
                continue;
            }

            $memberAssigneds[] = [
                "id" => $member->getId(),
                "name" => $member->getName(),
                "email" => $member->getEmail(),
                "image" => $member->getImage(),
                "assignment" => $assignment->jsonSerialize(),
            ];
        }

        return [
            "optionMembers" => $optionMembers,
            "memberAssigneds" => array_values($memberAssigneds),
        ];
    }

    public function assignMemberParttimeShift(WorkScheduleParttimeAssignDTO $dto)
    {
        $workShift = $this->workShiftRepository->find($dto->workShiftId);
        if (!$workShift) {
            throw new \Exception("Ca làm việc không tồn tại");
        }
        if (!$workShift->isStatus()) {
            throw new \Exception("Ca làm việc đã ngưng hoạt động");
        }

        $affectedUsers = [];

        foreach ($dto->userIds as $userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                throw new \Exception("User không tồn tại");
            }

            $affectedUsers[$user->getId()] = $user;

            $workShiftAssign = $this->workShiftAssignmentRepository->findOneBy(["member" => $user, "workShift" => $workShift]);
            if ($workShiftAssign) {
                continue;
            }

            $workShiftAssign = new WorkShiftAssignment();
            $workShiftAssign->setMember($user);
            $workShiftAssign->setWorkShift($workShift);
            $workShiftAssign->setDate(new \DateTime($dto->date));
            $this->entityManager->persist($workShiftAssign);
        }

        $this->entityManager->flush();

        foreach ($affectedUsers as $affectedUser) {
            $this->resyncFutureScheduledAttendancesForUser(
                $affectedUser,
                new \DateTime($dto->date),
                new \DateTime($dto->date),
            );
        }

        $this->entityManager->flush();

        return true;
    }

    public function removeMemberParttimeShift(int $workShiftAssignmentId)
    {
        $workShiftAssign = $this->workShiftAssignmentRepository->find($workShiftAssignmentId);
        if (!$workShiftAssign) {
            throw new \Exception(t("error.not_found"));
        }

        $workShift = $workShiftAssign->getWorkShift();
        $dateWorkShift = $workShiftAssign->getDate();
        $startTime = $workShift?->getGioBatDau();

        $currentDate = new \DateTime();

        if (!$workShift || !$dateWorkShift || !$startTime) {
            throw new \Exception(t("error.not_found"));
        }

        $member = $workShiftAssign->getMember();

        $shiftStartDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i',
            sprintf('%s %s', $dateWorkShift->format('Y-m-d'), substr($startTime, 0, 5)),
        );

        if (!$shiftStartDateTime) {
            throw new \Exception(t("error.not_found"));
        }

        // Không cho xóa khi ca làm việc đã bắt đầu hoặc đã qua
        if ($currentDate >= $shiftStartDateTime) {
            throw new \Exception(t("error.cannot_delete_work_shift"));
        }

        $this->entityManager->remove($workShiftAssign);
        $this->entityManager->flush();

        if ($member) {
            $this->resyncFutureScheduledAttendancesForUser(
                $member,
                $dateWorkShift,
                $dateWorkShift,
            );
            $this->entityManager->flush();
        }

        return true;
    }

    public function resyncFutureScheduledAttendancesForUser(
        User $user,
        \DateTimeInterface $fromDate,
        \DateTimeInterface $toDate,
    ): void {
        $now = new \DateTimeImmutable();
        $today = new \DateTimeImmutable('today');
        $resolvedFromDate = \DateTimeImmutable::createFromInterface($fromDate)->setTime(0, 0);
        $resolvedToDate = \DateTimeImmutable::createFromInterface($toDate)->setTime(0, 0);

        if ($resolvedFromDate < $today) {
            $resolvedFromDate = $today;
        }

        if ($resolvedFromDate > $resolvedToDate) {
            return;
        }

        $existingAttendances = $this->attendanceRepository
            ->findScheduledAttendancesByUserAndDateRange(
                $user,
                \DateTime::createFromImmutable($resolvedFromDate),
                \DateTime::createFromImmutable($resolvedToDate),
            );

        foreach ($existingAttendances as $attendance) {
            $actionDateTime = $this->attendanceReminderService
                ->getScheduledActionDateTime($attendance);

            if ($actionDateTime && $actionDateTime <= $now) {
                continue;
            }

            $this->entityManager->remove($attendance);
        }

        $configSnapshot = $this->buildAttendanceConfigSnapshot();
        $currentDate = $resolvedFromDate;

        while ($currentDate <= $resolvedToDate) {
            $workDate = \DateTime::createFromImmutable($currentDate);
            $workingSchedule = $this->getWorkingScheduleForUserOnDate(
                $user,
                $currentDate,
            );

            if (
                !($workingSchedule['isWorkingDay'] ?? false) ||
                empty($workingSchedule['schedules'])
            ) {
                $currentDate = $currentDate->modify('+1 day');
                continue;
            }

            $assignmentByWorkShiftId = $this->buildAttendanceAssignmentMapForUser(
                $user,
                $workDate,
            );

            foreach ($workingSchedule['schedules'] as $schedule) {
                $workShiftAssignment = null;
                $workShiftId = (int) ($schedule['workShiftId'] ?? 0);

                if ($workShiftId > 0) {
                    $workShiftAssignment = $assignmentByWorkShiftId[$workShiftId] ?? null;
                }

                $this->createScheduledAttendance(
                    $user,
                    $workDate,
                    (string) ($workingSchedule['workType'] ?? ''),
                    AttendanceType::CheckIn->value,
                    $workingSchedule,
                    $schedule,
                    $workShiftAssignment,
                    $configSnapshot,
                    $now,
                );

                $this->createScheduledAttendance(
                    $user,
                    $workDate,
                    (string) ($workingSchedule['workType'] ?? ''),
                    AttendanceType::CheckOut->value,
                    $workingSchedule,
                    $schedule,
                    $workShiftAssignment,
                    $configSnapshot,
                    $now,
                );
            }

            $currentDate = $currentDate->modify('+1 day');
        }
    }

    /**
     * @return array<int, WorkShiftAssignment>
     */
    public function buildAttendanceAssignmentMapForUser(
        User $user,
        \DateTimeInterface $workDate,
    ): array {
        $assignments = $this->workShiftAssignmentRepository->findBy([
            'member' => $user,
            'date' => $workDate,
        ]);
        $assignmentMap = [];

        foreach ($assignments as $assignment) {
            $workShiftId = $assignment->getWorkShift()?->getId();
            if (!$workShiftId) {
                continue;
            }

            $assignmentMap[$workShiftId] = $assignment;
        }

        return $assignmentMap;
    }

    /**
     * @param array<string, mixed> $workingSchedule
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $configSnapshot
     */
    private function createScheduledAttendance(
        User $user,
        \DateTimeInterface $workDate,
        string $workType,
        string $attendanceType,
        array $workingSchedule,
        array $schedule,
        ?WorkShiftAssignment $workShiftAssignment,
        array $configSnapshot,
        ?\DateTimeImmutable $now = null,
    ): void {
        $attendance = new Attendance();
        $attendance->setEmployee($user);
        $attendance->setAttendanceType($attendanceType);
        $attendance->setWorkDate(
            \DateTime::createFromFormat('Y-m-d', $workDate->format('Y-m-d')) ?: null,
        );
        $attendance->setWorkScheduleStartTime($schedule['startTime'] ?? null);
        $attendance->setWorkScheduleEndTime($schedule['endTime'] ?? null);
        $attendance->setStatus('scheduled');
        $attendance->setWorkType($workType);
        $attendance->setConfigSnapshot($configSnapshot);
        $attendance->setWorkScheduleSnapshot($workingSchedule);
        $attendance->setWorkShiftAssignment($workShiftAssignment);

        $actionDateTime = $this->attendanceReminderService
            ->getScheduledActionDateTime($attendance);
        if ($now && $actionDateTime && $actionDateTime <= $now) {
            return;
        }

        $this->attendanceReminderService->syncReminderForAttendance(
            $attendance,
            $configSnapshot,
            true,
        );

        $this->entityManager->persist($attendance);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAttendanceConfigSnapshot(): array
    {
        $configs = $this->generalSettingRepository->getAllConfig();
        $snapshot = [];

        foreach (AttendanceReminderService::ATTENDANCE_CONFIG_SNAPSHOT_KEYS as $key) {
            if (!array_key_exists($key, $configs)) {
                continue;
            }

            $snapshot[$key] = $configs[$key];
        }

        return $snapshot;
    }
}
