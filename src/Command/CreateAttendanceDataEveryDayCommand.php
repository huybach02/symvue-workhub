<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Attendance;
use App\Entity\User;
use App\Entity\WorkShiftAssignment;
use App\Repository\AttendanceRepository;
use App\Repository\UserRepository;
use App\Service\AttendanceReminderService;
use App\Service\WorkScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:attendance:create-attendance-data-every-day',
    description: 'Create attendance data every day',
)]
class CreateAttendanceDataEveryDayCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly AttendanceRepository $attendanceRepository,
        private readonly WorkScheduleService $workScheduleService,
        private readonly AttendanceReminderService $attendanceReminderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Create Attendance Data Every Day');

        $now = new \DateTimeImmutable();
        $workDate = \DateTime::createFromImmutable($now->setTime(0, 0));

        try {
            $users = $this->userRepository->findActiveUsers();
            $existingAttendances = $this->attendanceRepository->findByWorkDate($workDate);
            $existingAttendanceMap = $this->buildExistingAttendanceMap($existingAttendances);
            $configSnapshot = $this->workScheduleService->buildAttendanceConfigSnapshot();
            $createdCount = 0;

            foreach ($users as $user) {
                $workingSchedule = $this->workScheduleService->getWorkingScheduleForUserOnDate(
                    $user,
                    $now,
                );

                if (
                    !($workingSchedule['isWorkingDay'] ?? false) ||
                    empty($workingSchedule['schedules'])
                ) {
                    continue;
                }

                $assignmentByWorkShiftId = $this->workScheduleService->buildAttendanceAssignmentMapForUser($user, $workDate);

                foreach ($workingSchedule['schedules'] as $schedule) {
                    $workShiftAssignment = null;
                    $workShiftId = (int) ($schedule['workShiftId'] ?? 0);

                    if ($workShiftId > 0) {
                        $workShiftAssignment = $assignmentByWorkShiftId[$workShiftId] ?? null;
                    }

                    $createdCount += $this->createAttendanceIfMissing(
                        $user,
                        $workDate,
                        (string) ($workingSchedule['workType'] ?? ''),
                        'check_in',
                        $schedule['startTime'] ?? null,
                        $workingSchedule,
                        $schedule,
                        $workShiftAssignment,
                        $configSnapshot,
                        $existingAttendanceMap,
                    );

                    $createdCount += $this->createAttendanceIfMissing(
                        $user,
                        $workDate,
                        (string) ($workingSchedule['workType'] ?? ''),
                        'check_out',
                        $schedule['endTime'] ?? null,
                        $workingSchedule,
                        $schedule,
                        $workShiftAssignment,
                        $configSnapshot,
                        $existingAttendanceMap,
                    );
                }
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $io->error(sprintf('Loi khi create attendance data every day: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Đã tạo thành công %d dữ liệu chấm công scheduled của ngày %s.',
            $createdCount,
            $now->format('Y-m-d'),
        ));

        return Command::SUCCESS;
    }

    /**
     * @param Attendance[] $existingAttendances
     *
     * @return array<string, bool>
     */
    private function buildExistingAttendanceMap(array $existingAttendances): array
    {
        $attendanceMap = [];

        foreach ($existingAttendances as $attendance) {
            $employee = $attendance->getEmployee();
            if (!$employee) {
                continue;
            }

            $scheduledTime = $attendance->getAttendanceType() === 'check_out'
                ? $attendance->getWorkScheduleEndTime()
                : $attendance->getWorkScheduleStartTime();

            $attendanceMap[$this->buildAttendanceKey(
                $employee->getId(),
                $attendance->getAttendanceType(),
                $attendance->getWorkShiftAssignment()?->getId(),
                $scheduledTime,
            )] = true;
        }

        return $attendanceMap;
    }

    /**
     * @param array<string, mixed> $workingSchedule
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $configSnapshot
     * @param array<string, bool> $existingAttendanceMap
     */
    private function createAttendanceIfMissing(
        User $user,
        \DateTimeInterface $workDate,
        string $workType,
        string $attendanceType,
        ?string $time,
        array $workingSchedule,
        array $schedule,
        ?WorkShiftAssignment $workShiftAssignment,
        array $configSnapshot,
        array &$existingAttendanceMap,
    ): int {
        $attendanceKey = $this->buildAttendanceKey(
            $user->getId(),
            $attendanceType,
            $workShiftAssignment?->getId(),
            $time,
        );

        if (isset($existingAttendanceMap[$attendanceKey])) {
            return 0;
        }

        $attendance = new Attendance();
        $attendance->setEmployee($user);
        $attendance->setAttendanceType($attendanceType);
        $attendance->setWorkDate(\DateTime::createFromFormat('Y-m-d', $workDate->format('Y-m-d')) ?: null);
        $attendance->setWorkScheduleStartTime($schedule['startTime'] ?? null);
        $attendance->setWorkScheduleEndTime($schedule['endTime'] ?? null);
        $attendance->setStatus('scheduled');
        $attendance->setWorkType($workType);
        $attendance->setConfigSnapshot($configSnapshot);
        $attendance->setWorkScheduleSnapshot($workingSchedule);
        $attendance->setWorkShiftAssignment($workShiftAssignment);
        $this->attendanceReminderService->syncReminderForAttendance(
            $attendance,
            $configSnapshot,
            true,
        );

        $this->entityManager->persist($attendance);
        $existingAttendanceMap[$attendanceKey] = true;

        return 1;
    }

    private function buildAttendanceKey(
        ?int $userId,
        ?string $attendanceType,
        ?int $workShiftAssignmentId,
        ?string $time,
    ): string {
        return sprintf(
            '%d|%s|%s',
            $userId ?? 0,
            $attendanceType ?? '',
            $workShiftAssignmentId ? 'shift-' . $workShiftAssignmentId : 'time-' . ($time ?? ''),
        );
    }
}
