<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\UserPermission;
use App\Service\BoPhanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rebuild-user-permissions-cache',
    description: 'Quét tất cả bản ghi user_permission, merge permissions theo userId và lưu vào Redis cache',
)]
class RebuildUserPermissionsCacheCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BoPhanService $boPhanService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Rebuild User Permissions Cache');

        $startTime = microtime(true);

        // Lấy tất cả userId duy nhất từ bảng user_permission
        $userIds = $this->entityManager
            ->getRepository(UserPermission::class)
            ->createQueryBuilder('up')
            ->select('DISTINCT up.userId')
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($userIds)) {
            $io->warning('Không tìm thấy bản ghi nào trong bảng user_permission.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Tìm thấy %d user cần rebuild cache.', count($userIds)));

        $successCount = 0;
        $errorCount = 0;

        $io->progressStart(count($userIds));

        foreach ($userIds as $userId) {
            try {
                $this->boPhanService->mergeUserPermissions((int) $userId);
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $io->error(sprintf('Lỗi khi xử lý userId %d: %s', $userId, $e->getMessage()));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $elapsed = round(microtime(true) - $startTime, 2);

        $io->success(sprintf(
            'Hoàn thành! Thành công: %d | Lỗi: %d | Thời gian: %ss',
            $successCount,
            $errorCount,
            $elapsed
        ));

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
