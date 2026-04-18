<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\DTO\WorkScheduleDTO;
use App\DTO\WorkScheduleFulltimeDTO;
use App\DTO\WorkScheduleFulltimeOverrideDTO;
use App\Entity\Example;
use App\Entity\FixedSchedule;
use App\Entity\FixedScheduleGroup;
use App\Entity\FixedScheduleOverride;
use App\Entity\HolidaySchedule;
use App\Entity\LeaveSchedule;
use App\Entity\User;
use App\Repository\ExampleRepository;
use App\Repository\FixedScheduleGroupRepository;
use App\Repository\FixedScheduleOverrideRepository;
use App\Repository\HolidayScheduleRepository;
use App\Repository\LeaveScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\WorkingTimeRepository;
use Doctrine\ORM\EntityManagerInterface;

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

    public function getFulltime(int $departmentId): array
    {
        $members = array_map(
            fn(array $member) => (object) $member,
            array_values(
                array_filter(
                    $this->departmentService->getMembersByDepartment(
                        $departmentId,
                    ),
                    fn(array $member): bool =>
                    (int) ($member["hinhThucLamViec"] ?? 0) === 1,
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
        ]);

        $events = [];

        foreach ($groups as $group) {
            foreach ($this->buildEventsFromFixedScheduleGroup($group) as $event) {
                $events[$this->getEventMapKey($event)] = $event;
            }
        }

        foreach ($overrides as $override) {
            foreach ($this->buildEventsFromOverride($override) as $event) {
                $events[$this->getEventMapKey($event)] = $event;
            }
        }

        foreach ($leaveSchedules as $leaveSchedule) {
            foreach ($this->buildEventsFromLeaveSchedule($leaveSchedule) as $event) {
                $events[$this->getEventMapKey($event)] = $event;
            }
        }

        usort(
            $events,
            fn(array $left, array $right): int => strcmp(
                "{$left['date']}-{$left['user_id']}-{$left['startTime']}",
                "{$right['date']}-{$right['user_id']}-{$right['startTime']}",
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

        $workingTimeByDay = $this->mapWorkingTimesByDayOfWeek($workingTimes);

        if ($workingTimeByDay === []) {
            throw new \Exception("Không tìm thấy cấu hình thời gian làm việc hợp lệ.");
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

            foreach ($workingTimeByDay as $dayOfWeek => $workingTime) {
                $fixedSchedule = new FixedSchedule();
                $fixedSchedule->setFixedScheduleGroup($fixedScheduleGroup);
                $fixedSchedule->setDayOfWeek($dayOfWeek);
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

        $fixedSchedules = $fixedScheduleGroup->getFixedSchedules();

        foreach ($fixedSchedules as $fixedSchedule) {
            $this->entityManager->remove($fixedSchedule);
        }

        $this->entityManager->remove($fixedScheduleGroup);

        $this->fixedScheduleOverrideRepository->clearOverridesByUser($userId);

        $this->entityManager->flush();

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

        // Tìm tất cả các override đang overlap với range mới
        $overlappingOverrides = $this->fixedScheduleOverrideRepository->findOverlappingOverrides(
            $user,
            $newStartDate,
            $newEndDate
        );

        // Xử lý cắt đoạn từng override cũ
        foreach ($overlappingOverrides as $existingOverride) {
            $this->processOverrideSplit($existingOverride, $newStartDate, $newEndDate);
        }

        // Tạo override mới
        $override = new FixedScheduleOverride();
        $override->setMember($user);
        $override->setStartDate($newStartDate);
        $override->setEndDate($newEndDate);
        $override->setStartTime($newStartTime);
        $override->setEndTime($newEndTime);
        $override->setReason($dto->note ?? '');
        $override->setType('fulltime');
        $this->entityManager->persist($override);

        $this->entityManager->flush();

        return $override->jsonSerialize();
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

    private function mapWorkingTimesByDayOfWeek(array $workingTimes): array
    {
        $dayOfWeekMap = array_flip(array_keys(Constanst::THOI_GIAN_LAM_VIEC));
        $dayOfWeekMap = array_map(
            fn(int $index) => $index + 1,
            $dayOfWeekMap,
        );

        $mappedWorkingTimes = [];

        foreach ($workingTimes as $workingTime) {
            $dayOfWeek = $dayOfWeekMap[$workingTime->getThu()] ?? null;

            if ($dayOfWeek === null) {
                continue;
            }

            $mappedWorkingTimes[$dayOfWeek] = $workingTime;
        }

        ksort($mappedWorkingTimes);

        return $mappedWorkingTimes;
    }

    private function buildEventsFromFixedScheduleGroup(FixedScheduleGroup $group): array
    {
        $events = [];
        $scheduleByDay = [];

        foreach ($group->getFixedSchedules() as $schedule) {
            $scheduleByDay[$schedule->getDayOfWeek()] = $schedule;
        }

        $currentDate = (clone $group->getStartDate())->setTime(0, 0);
        $endDate = (clone $group->getEndDate())->setTime(0, 0);

        while ($currentDate <= $endDate) {
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
                    date: $currentDate->format("Y-m-d"),
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
        $currentDate = (clone $override->getStartDate())->setTime(0, 0);
        $endDate = (clone $override->getEndDate())->setTime(0, 0);

        while ($currentDate <= $endDate) {
            $events[] = $this->makeEventItem(
                id: sprintf(
                    "override-%d-%s",
                    $override->getId(),
                    $currentDate->format("Ymd"),
                ),
                userId: $override->getMember()?->getId(),
                date: $currentDate->format("Y-m-d"),
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
        return $leaveSchedule->getType() ?: "Nghỉ phép";
    }

    private function getEventMapKey(array $event): string
    {
        return sprintf("%s-%s", $event["user_id"], $event["date"]);
    }
}
