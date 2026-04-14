<?php

namespace App\DataFixtures;

use App\Class\Constanst;
use App\Entity\HolidaySchedule;
use App\Repository\HolidayScheduleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LucNham\LunarCalendar\LunarDateTime;

class HolidayScheduleFixture extends Fixture
{
    public function __construct(
        private readonly HolidayScheduleRepository $holidayScheduleRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $currentYear = (int) (new \DateTimeImmutable())->format('Y');
        $years = range($currentYear - 5, $currentYear + 10);

        $this->holidayScheduleRepository->deleteAll();

        foreach ($years as $year) {
            foreach (Constanst::LUNAR_HOLIDAY as $name => $date) {
                $this->persistHolidaySchedule(
                    $manager,
                    $year,
                    $name,
                    $this->createSolarDateFromLunarDate($year, $date),
                );
            }

            foreach (Constanst::HOLIDAY_SCHEDULE as $name => $date) {
                $this->persistHolidaySchedule(
                    $manager,
                    $year,
                    $name,
                    $this->createSolarDateFromSolarDate($year, $date),
                );
            }
        }

        $manager->flush();
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

    private function persistHolidaySchedule(ObjectManager $manager, int $year, string $name, \DateTime $holidayDate): void
    {
        $holidaySchedule = new HolidaySchedule();
        $holidaySchedule->setYear((string) $year);
        $holidaySchedule->setCode($holidayDate->format('Y-m-d'));
        $holidaySchedule->setName($name);
        $holidaySchedule->setDate($holidayDate);
        $holidaySchedule->setStatus(true);

        $manager->persist($holidaySchedule);
    }
}
