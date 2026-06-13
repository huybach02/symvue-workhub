<?php

declare(strict_types=1);

namespace App\Command;

use App\Class\CheckWorkScheduleOfUser;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use App\Service\MercureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:attendance:sync-absent-status',
    description: 'Sync absent status cho attendance check-in quá hạn thời gian',
)]
class SyncAbsentAttendanceStatusCommand extends Command
{
    public function __construct(
        private readonly AttendanceRepository $attendanceRepository,
        private readonly GeneralSettingRepository $generalSettingRepository,
        private readonly CheckWorkScheduleOfUser $checkWorkScheduleOfUser,
        private readonly EntityManagerInterface $entityManager,
        private readonly MercureService $mercureService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $generalSetting = $this->generalSettingRepository->getAllConfig();
        $scheduledCheckInAttendances = $this->attendanceRepository->findScheduledCheckInAttendancesForAbsentSync(
            $now->modify('-1 day'),
            $now,
        );
        $updatedAttendanceCount = 0;
        $updatedAttendances = [];

        foreach ($scheduledCheckInAttendances as $attendance) {
            $latestAllowedTime = $this->checkWorkScheduleOfUser->getCheckInLatestAllowedTime(
                $attendance,
                $generalSetting,
            );

            if (!$latestAllowedTime || $now <= $latestAllowedTime) {
                continue;
            }

            $changedAttendances = $this->checkWorkScheduleOfUser->markAttendanceAndRelatedCheckOutAsAbsent(
                $attendance,
            );

            $updatedAttendanceCount += count($changedAttendances);
            $updatedAttendances = [...$updatedAttendances, ...$changedAttendances];
        }

        if ($updatedAttendanceCount > 0) {
            $this->entityManager->flush();

            foreach ($updatedAttendances as $updatedAttendance) {
                $this->mercureService->attendance(
                    $updatedAttendance->jsonSerialize(),
                );
            }
        }

        $io->success(sprintf(
            'Đã sync absent status cho %d attendance record.',
            $updatedAttendanceCount,
        ));

        return Command::SUCCESS;
    }
}
