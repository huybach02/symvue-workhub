<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\ThongBao;
use App\Message\ThongBaoCaNhanMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// Handler này chạy bất đồng bộ (async) - chỉ xử lý lưu DB, Mercure đã publish đồng bộ
#[AsMessageHandler]
final class ThongBaoCaNhanHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ThongBaoCaNhanMessage $message): void
    {
        $thongBao = new ThongBao();
        $thongBao->setCode($message->getCode());
        $thongBao->setFromId($message->getFromUserId());
        $thongBao->setToId($message->getToUserId());
        $thongBao->setTitle($message->getTitle());
        $thongBao->setBody($message->getBody());
        $thongBao->setLink($message->getLink());
        $thongBao->setIcon('mdi-bell-outline');
        $thongBao->setColor('primary');
        $thongBao->setSeen(false);

        $this->entityManager->persist($thongBao);
        $this->entityManager->flush();
    }
}
