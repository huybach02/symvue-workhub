<?php

namespace App;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)

            // Rebuild cache user permissions mỗi ngày lúc 00:00
            ->add(RecurringMessage::cron(
                '0 0 * * *',
                new RunCommandMessage('app:rebuild-user-permissions-cache')
            ))
            // Keep alive database vào 23h mỗi ngày
            ->add(RecurringMessage::cron(
                '0 23 * * *',
                new RunCommandMessage('app:keep-alive-db')
            ))
            // Clear cache persist mỗi ngày lúc 00:00
            ->add(RecurringMessage::cron(
                '0 0 * * *',
                new RunCommandMessage('app:clear-cache-persist-in-database')
            ))
            // Rebuild cache user permissions mỗi phút
            // ->add(RecurringMessage::cron(
            //     '* * * * *',
            //     new RunCommandMessage('app:rebuild-user-permissions-cache')
            // ))
        ;
    }
}
