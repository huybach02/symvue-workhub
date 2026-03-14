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
    name: 'app:keep-alive-db',
    description: 'Giữ kết nối với cơ sở dữ liệu để tránh bị đóng',
)]
class KeepAliveDatabaseCommand extends Command
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
        $io->title('Keep Alive Database');

        $now = new \DateTimeImmutable();

        // Bước 1: Từ ngày hôm nay trờ ngược về 30 ngày trước, tìm các bản ghi có dữ liệu ở cột time (kiểu string) trong table keep_alive_db nằm trước khoảng này và clear các bản ghi này, chỉ giữ lại 30 ngày gần nhất
        $thirtyDaysAgo = $now->modify('-30 days')->format('Y-m-d H:i:s');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete('App\Entity\KeepAliveDB', 'kadb')
            ->where($qb->expr()->lt('kadb.time', ':thirtyDaysAgo'))
            ->setParameter('thirtyDaysAgo', $thirtyDaysAgo);

        $result = $qb->getQuery()->execute();

        $io->success(sprintf('Đã xóa %d bản ghi cũ trong bảng keep_alive_db.', $result));

        // Bước 2: Tạo mới một bản ghi trong table keep_alive_db với time là thời gian hiện tại
        try {
            $keepAliveDb = new \App\Entity\KeepAliveDB();
            $keepAliveDb->setTime($now->format('Y-m-d H:i:s'));
            $this->entityManager->persist($keepAliveDb);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $io->error(sprintf('Lỗi khi tạo mới bản ghi trong bảng keep_alive_db: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->success(sprintf('Đã tạo mới một bản ghi trong bảng keep_alive_db với time là %s.', $now->format('Y-m-d H:i:s')));

        return Command::SUCCESS;
    }
}
