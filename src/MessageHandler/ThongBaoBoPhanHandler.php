<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Entity\UserPermission;
use App\Message\ThongBaoBoPhanMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// Handler này chạy bất đồng bộ (async) - xử lý lưu DB cho toàn bộ user trong bộ phận
#[AsMessageHandler]
final class ThongBaoBoPhanHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ThongBaoBoPhanMessage $message): void
    {
        $userIds = $this->entityManager->getRepository(UserPermission::class)
            ->createQueryBuilder('up')
            ->select('DISTINCT up.userId')
            ->where('up.boPhanId = :boPhanId')
            ->setParameter('boPhanId', $message->getBoPhanId())
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($userIds)) {
            return;
        }

        $batchSize = 100;
        $i = 0;

        foreach ($userIds as $userId) {
            $thongBao = new Notification();
            $thongBao->setCode($message->getCode());
            $thongBao->setFromId($message->getFromUserId());
            $thongBao->setToId($userId);
            $thongBao->setTitle($message->getTitle());
            $thongBao->setBody($message->getBody());
            $thongBao->setLink($message->getLink());
            $thongBao->setIcon('mdi-bell-outline');
            $thongBao->setColor($message->getType());
            $thongBao->setSeen(false);
            $thongBao->setCreatedAt($message->getCreatedAt());

            $this->entityManager->persist($thongBao);

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $i++;
        }

        $this->entityManager->flush();
    }
}
