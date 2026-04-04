<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CachePersist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-cache-persist-in-database',
    description: 'Clear expired cache_persist records from the database',
)]
class ClearCachePersistInDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Clear Cache Persist In Database');

        try {
            $deletedCount = $this->entityManager
                ->createQueryBuilder()
                ->delete(CachePersist::class, 'cp')
                ->where('cp.expireAt < :now')
                ->setParameter('now', time())
                ->getQuery()
                ->execute();
        } catch (\Throwable $e) {
            $io->error(sprintf('Loi khi clear cache persist trong database: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf('Da clear %d cache persist het han trong database.', $deletedCount));

        return Command::SUCCESS;
    }
}
