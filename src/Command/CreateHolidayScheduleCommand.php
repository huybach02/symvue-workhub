<?php

declare(strict_types=1);

namespace App\Command;

use App\Class\Constanst;
use App\Entity\HolidaySchedule;
use Doctrine\ORM\EntityManagerInterface;
use LucNham\LunarCalendar\LunarDateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-holiday-schedule',
    description: 'Tự động tạo lịch nghỉ lễ/tết',
)]
class CreateHolidayScheduleCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Create Holiday Schedule');

        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $years = range($currentYear - 5, $currentYear + 10);

        try {
            $this->entityManager->getRepository(HolidaySchedule::class)->deleteAll();

            foreach ($years as $year) {
                foreach (Constanst::LUNAR_HOLIDAY as $name => $date) {
                    $this->persistHolidaySchedule(
                        $year,
                        $name,
                        $this->createSolarDateFromLunarDate($year, $date),
                    );
                }

                foreach (Constanst::HOLIDAY_SCHEDULE as $name => $date) {
                    $this->persistHolidaySchedule(
                        $year,
                        $name,
                        $this->createSolarDateFromSolarDate($year, $date),
                    );
                }
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $io->error(sprintf('Lỗi khi tạo mới bản ghi trong bảng holiday_schedule: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $totalRecords = count($years) * (count(Constanst::HOLIDAY_SCHEDULE) + count(Constanst::LUNAR_HOLIDAY));
        $io->success(sprintf('Đã tạo mới %d bản ghi trong bảng holiday_schedule với năm hiện tại là %s.', $totalRecords, $now->format('Y')));

        return Command::SUCCESS;
    }

    private function createSolarDateFromLunarDate(int $year, string $date): \DateTime
    {
        [$month, $day] = array_map('intval', explode('/', $date));
        $lunar = new LunarDateTime(sprintf('%d-%02d-%02d 00:00 Asia/Ho_Chi_Minh', $year, $month, $day));
        $solarDate = explode(' ', $lunar->toDateTimeString())[0];
        $holidayDate = \DateTime::createFromFormat('!Y-m-d', $solarDate);

        if ($holidayDate === false) {
            throw new \RuntimeException(sprintf('Ngày dương lịch không hợp lệ sau khi chuyển đổi âm lịch: %s (%s)', $solarDate, $date));
        }

        return $holidayDate;
    }

    private function createSolarDateFromSolarDate(int $year, string $date): \DateTime
    {
        $holidayDate = \DateTime::createFromFormat('!j/n/Y', $date . '/' . $year);

        if ($holidayDate === false) {
            throw new \RuntimeException(sprintf('Ngày nghỉ lễ không hợp lệ: %s/%s', $date, $year));
        }

        return $holidayDate;
    }

    private function persistHolidaySchedule(int $year, string $name, \DateTime $holidayDate): void
    {
        $holidaySchedule = new HolidaySchedule();
        $holidaySchedule->setYear((string) $year);
        $holidaySchedule->setCode($holidayDate->format('Y-m-d'));
        $holidaySchedule->setName($name);
        $holidaySchedule->setDate($holidayDate);
        $holidaySchedule->setStatus(true);

        $this->entityManager->persist($holidaySchedule);
    }
}
