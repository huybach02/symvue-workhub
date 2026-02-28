<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\ThongBao;
use App\Message\ThongBaoHeThongMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// Handler này chạy bất đồng bộ (async) - không block request HTTP
#[AsMessageHandler]
final class ThongBaoHeThongHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ThongBaoHeThongMessage $message): void
    {
        $batchSize = 100;
        $i = 0;

        // Stream từng user ID từ DB, không load toàn bộ vào RAM
        $users = $this->userRepository->createQueryBuilder('u')
            ->select('u.id')
            ->getQuery()
            ->toIterable();

        foreach ($users as $user) {
            $thongBao = new ThongBao();
            $thongBao->setCode($message->getCode());
            $thongBao->setFromId($message->getFromUserId());
            $thongBao->setToId($user['id']);
            $thongBao->setTitle($message->getTitle());
            $thongBao->setBody($message->getBody());
            $thongBao->setIcon("mdi-bell-outline");
            $thongBao->setColor("primary");
            $thongBao->setSeen(false);
            $thongBao->setLink($message->getLink());

            $this->entityManager->persist($thongBao);

            // Batch flush mỗi 100 bản ghi
            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $i++;
        }

        // Flush phần còn lại (<100 entity)
        $this->entityManager->flush();
    }
}
