<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Entity\BoPhan;
use App\Entity\ThongBao;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Message\ThongBaoBoPhanMessage;
use App\Message\ThongBaoCaNhanMessage;
use App\Message\ThongBaoHeThongMessage;
use App\Repository\ThongBaoRepository;
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
        private ThongBaoRepository $thongBaoRepository,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
    }

    public function findAll(array $params, $user): array
    {
        $qb = $this->thongBaoRepository->createQueryBuilder('tb');

        $qb->where('tb.isCreated = :isCreated')
            ->setParameter('isCreated', true);

        if ($user->getId() && !isAdmin($user)) {
            $qb->andWhere('tb.fromId = :userId')
                ->setParameter('userId', $user->getId());
        }

        if (isset($params['f']) && is_array($params['f'])) {
            foreach ($params['f'] as &$filter) {
                if (isset($filter['field']) && $filter['field'] === 'sendTo') {
                    $filter['field'] = 'typeNotification';
                }
                if (isset($filter['field']) && $filter['field'] === 'fromUser') {
                    $filter['field'] = 'fromId';
                }
            }
            unset($filter);
        }

        $result = FilterWithPagination::findWithPagination($qb, $params, 'tb');

        $result['collection'] = array_map(
            function (ThongBao $thongBao) {
                $data = $thongBao->jsonSerialize();
                $fromUser = $thongBao->getFromId() ? $this->entityManager->getRepository(User::class)->find($thongBao->getFromId()) : null;
                $data['fromUser'] = $fromUser?->jsonSerialize()['name'] ?? 'Không xác định';
                $data['sendTo'] = $this->resolveSendTo($thongBao);
                return $data;
            },
            $result['collection']
        );

        return $result;
    }

    /**
     * Xác định giá trị hiển thị của trường sendTo dựa theo typeNotification và toId.
     */
    private function resolveSendTo(ThongBao $thongBao): string
    {
        $type  = $thongBao->getTypeNotification();
        $toId  = $thongBao->getToId();

        if ($type === 'all') {
            return t('thong_bao.type.all');
        }

        if ($type === 'department') {
            // Join qua entity BoPhan để lấy tên bộ phận
            $boPhan = $this->entityManager->getRepository(BoPhan::class)->find($toId);
            $tenBoPhan = $boPhan?->getTenBoPhan() ?? 'Không xác định';
            return t('thong_bao.type.department') . ': ' . $tenBoPhan;
        }

        if ($type === 'user') {
            // Join qua entity User để lấy tên người dùng
            $user = $this->entityManager->getRepository(User::class)->find($toId);
            $tenNguoiDung = $user?->getName() ?? 'Không xác định';
            return t('thong_bao.type.user') . ': ' . $tenNguoiDung;
        }

        return '';
    }

    public function danhSachThongBao($userId)
    {
        $thongBao = $this->entityManager->getRepository(ThongBao::class)->findBy(['toId' => $userId, 'isCreated' => false], ['id' => 'DESC']);

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

    public function thongBao($data): ThongBao
    {
        $thongBao = new ThongBao();
        $thongBao->setCode(uniqid());
        $thongBao->setFromId($data['fromId']);

        $time = new \DateTime();

        switch ($data['sendTo']) {
            case 'all':
                $this->thongBaoHeThong($data['fromId'], $data['title'], $data['body'], $data['type'], $time);
                $thongBao->setToId(0);
                $thongBao->setTypeNotification("all");
                break;
            case 'department':
                $this->thongBaoPhongBan($data['fromId'], $data['departmentId'], $data['title'], $data['body'], $data['type'], $time);
                $thongBao->setToId($data['departmentId']);
                $thongBao->setTypeNotification("department");
                break;
            case 'user':
                $this->thongBaoCaNhan($data['fromId'], $data['userId'], $data['title'], $data['body'], $data['type'], $time);
                $thongBao->setToId($data['userId']);
                $thongBao->setTypeNotification("user");
                break;
        }

        $thongBao->setTitle($data['title']);
        $thongBao->setBody($data['body']);
        $thongBao->setIcon('mdi-bell-outline');
        $thongBao->setColor($data['type']);
        $thongBao->setSeen(false);
        $thongBao->setIsCreated(true);
        $thongBao->setCreatedAt($time);

        $this->entityManager->persist($thongBao);
        $this->entityManager->flush();

        return $thongBao;
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
            'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $update = new Update(
            $this->mercureConfig['topics']['test'],
            json_encode($data),
            false
        );

        $this->hub->publish($update);
    }

    public function thongBaoHeThong($fromUserId, $title, $body, $type = "primary", $createdAt = null, $link = ""): void
    {
        $code = uniqid();

        $data = [
            'code'   => $code,
            'fromId' => $fromUserId,
            'title'  => $title,
            'body'   => $body,
            'link'   => $link,
            'icon'   => 'mdi-bell-outline',
            'color'  => $type,
            'seen'   => false,
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
        ];

        $update = new Update(
            $this->mercureConfig['topics']['thong-bao-he-thong'],
            json_encode($data),
            false
        );

        $this->hub->publish($update);

        $this->messageBus->dispatch(
            new ThongBaoHeThongMessage($fromUserId, $title, $body, $code, $type, $createdAt, $link)
        );
    }

    public function thongBaoCaNhan($fromUserId, $toUserId, $title, $body, $type = "primary", $createdAt = null, $link = ""): void
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
            'color'  => $type,
            'seen'   => false,
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
        ];

        $update = new Update($topic, json_encode($data), false);
        $this->hub->publish($update);

        $this->messageBus->dispatch(
            new ThongBaoCaNhanMessage($fromUserId, $toUserId, $title, $body, $code, $type, $createdAt, $link)
        );
    }

    public function thongBaoPhongBan($fromUserId, $departmentId, $title, $body, $type = "primary", $createdAt = null, $link = ""): void
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
            'color'  => $type,
            'seen'   => false,
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
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
            new ThongBaoBoPhanMessage($fromUserId, $departmentId, $title, $body, $code, $type, $createdAt, $link)
        );
    }
}
