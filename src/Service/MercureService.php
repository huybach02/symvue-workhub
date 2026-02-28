<?php

namespace App\Service;

use App\Entity\ThongBao;
use App\Entity\UserPermission;
use App\Message\ThongBaoBoPhanMessage;
use App\Message\ThongBaoCaNhanMessage;
use App\Message\ThongBaoHeThongMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;

class MercureService
{
    private array $mercureConfig;

    public function __construct(
        private HubInterface $hub,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
    }

    public function danhSachThongBao($userId)
    {
        $thongBao = $this->entityManager->getRepository(ThongBao::class)->findBy(['toId' => $userId]);

        return array_map(fn(ThongBao $tb) => $tb->jsonSerialize(), $thongBao);
    }

    public function markAllRead($userId)
    {
        $thongBao = $this->entityManager->getRepository(ThongBao::class)->findBy(['toId' => $userId]);

        foreach ($thongBao as $tb) {
            $tb->setSeen(true);
            $this->entityManager->persist($tb);
        }

        $this->entityManager->flush();

        return true;
    }

    public function markOneRead($userId, $code)
    {
        $thongBao = $this->entityManager->getRepository(ThongBao::class)->findOneBy(['toId' => $userId, 'code' => $code]);

        if ($thongBao) {
            $thongBao->setSeen(true);
            $this->entityManager->persist($thongBao);
            $this->entityManager->flush();
        }

        return true;
    }

    public function thongBaoTest(): void
    {
        $data = [
            'id'     => 0,
            'fromId' => 1,
            'title'  => 'Thông báo test',
            'body'   => 'Đây là thông báo test từ Mercure',
            'link'   => '',
            'icon'   => 'mdi-bell-outline',
            'color'  => 'primary',
            'seen'   => false,
        ];

        $update = new Update(
            $this->mercureConfig['topics']['test'],
            json_encode($data),
            false
        );

        $this->hub->publish($update);
    }

    public function thongBaoHeThong($fromUserId, $title, $body, $link = ""): void
    {
        $code = uniqid();

        $data = [
            'code'   => $code,
            'fromId' => $fromUserId,
            'title'  => $title,
            'body'   => $body,
            'link'   => $link,
            'icon'   => 'mdi-bell-outline',
            'color'  => 'primary',
            'seen'   => false,
        ];

        $update = new Update(
            $this->mercureConfig['topics']['thong-bao-he-thong'],
            json_encode($data),
            false
        );

        $this->hub->publish($update);

        $this->messageBus->dispatch(
            new ThongBaoHeThongMessage($fromUserId, $title, $body, $code, $link)
        );
    }

    public function thongBaoCaNhan($fromUserId, $toUserId, $title, $body, $link = ""): void
    {
        $code = uniqid();

        $topic = str_replace(':userId', (string) $toUserId, $this->mercureConfig['topics']['thong-bao-ca-nhan']);
        $data = [
            'code'   => $code,
            'fromId' => $fromUserId,
            'toId'   => $toUserId,
            'title'  => $title,
            'body'   => $body,
            'link'   => $link,
            'icon'   => 'mdi-bell-outline',
            'color'  => 'primary',
            'seen'   => false,
        ];

        $update = new Update($topic, json_encode($data), false);
        $this->hub->publish($update);

        $this->messageBus->dispatch(
            new ThongBaoCaNhanMessage($fromUserId, $toUserId, $title, $body, $code, $link)
        );
    }

    public function thongBaoPhongBan($fromUserId, $departmentId, $title, $body, $link = ""): void
    {
        $code = uniqid();

        $userIds = $this->entityManager->getRepository(UserPermission::class)
            ->createQueryBuilder('up')
            ->select('DISTINCT up.userId')
            ->where('up.boPhanId = :boPhanId')
            ->setParameter('boPhanId', $departmentId)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($userIds)) {
            return;
        }

        $personalTopicTemplate = $this->mercureConfig['topics']['thong-bao-ca-nhan'];
        $data = [
            'code'   => $code,
            'fromId' => $fromUserId,
            'title'  => $title,
            'body'   => $body,
            'link'   => $link,
            'icon'   => 'mdi-bell-outline',
            'color'  => 'primary',
            'seen'   => false,
        ];

        foreach ($userIds as $userId) {
            $topic = str_replace(':userId', (string) $userId, $personalTopicTemplate);
            $update = new Update(
                $topic,
                json_encode(array_merge($data, ['toId' => $userId])),
                false
            );
            $this->hub->publish($update);
        }

        $this->messageBus->dispatch(
            new ThongBaoBoPhanMessage($fromUserId, $departmentId, $title, $body, $code, $link)
        );
    }
}
