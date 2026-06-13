<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\AttendanceReminderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:attendance:send-reminders',
    description: 'Gửi nhắc nhở chấm công đến các attendance scheduled đã tới thời điểm nhắc',
)]
final class SendAttendanceRemindersCommand extends Command
{
    public function __construct(
        private readonly AttendanceReminderService $attendanceReminderService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $systemSenderId = (int) ($this->userRepository->findFirstActiveAdmin()?->getId() ?? 0);
        $result = $this->attendanceReminderService->processDueReminders(
            $now,
            $systemSenderId,
        );

        if ($result['processedCount'] > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf(
            'Đã xử lý %d reminder, gửi thành công %d thông báo.',
            $result['processedCount'],
            $result['sentCount'],
        ));

        return Command::SUCCESS;
    }
}
