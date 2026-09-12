<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\DTO\ConversationDTO;
use App\Entity\Conversation;
use App\Entity\ConversationUser;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ConversationUserRepository;
use App\Repository\ImageRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ConversationService
{
    private array $mercureConfig;

    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationUserRepository $conversationUserRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageRepository $messageRepository,
        private readonly HubInterface $hub,
        private readonly ImageRepository $imageRepository,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
    }

    public function search(array $params, User $currentUser): array
    {
        $keyword = trim($params['keyword'] ?? '');

        if ($keyword === '') {
            return [];
        }

        // Subquery: lấy ra ID của những user đã có private conversation với currentUser
        // Logic: tìm các conversation mà cả currentUser lẫn user kia cùng tham gia (type = 'private')
        $subDql = '
            SELECT IDENTITY(cu2.member)
            FROM App\Entity\ConversationUser cu1
            JOIN App\Entity\ConversationUser cu2 WITH cu2.conversation = cu1.conversation AND cu2.member != :currentUser
            JOIN App\Entity\Conversation c WITH c = cu1.conversation
            WHERE cu1.member = :currentUser
              AND c.type = :type
        ';

        // Query chính: lấy user có tên/email chứa keyword, loại trừ bản thân và những user đã có conversation
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.id != :currentUser')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.status = 1')
            ->andWhere('(LOWER(u.name) LIKE :keyword OR LOWER(u.email) LIKE :keyword)')
            ->andWhere('u.id NOT IN (' . $subDql . ')')
            ->setParameter('currentUser', $currentUser->getId())
            ->setParameter('keyword', '%' . mb_strtolower($keyword) . '%')
            ->setParameter('type', Constanst::TYPE_CONVERSATION['private'])
            ->orderBy('u.name', 'ASC');

        $users = $qb->getQuery()->getResult();
        $userIds = array_map(fn(User $user) => (int) $user->getId(), $users);
        $imagesMap = $this->imageRepository->getImagesMap(User::class, $userIds, 'avatar');

        return array_map(fn(User $user) => [
            'id'     => $user->getId(),
            'name'   => $user->getName(),
            'email'  => $user->getEmail(),
            'avatar' => $imagesMap[(int) $user->getId()] ?? null,
        ], $users);
    }

    public function findAll(User $currentUser): array
    {
        // Lấy tất cả conversation mà currentUser tham gia
        $conversations = $this->conversationRepository->createQueryBuilder('c')
            ->innerJoin('c.conversationUsers', 'cu')
            ->where('cu.member = :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $partnerIds = [];
        foreach ($conversations as $conv) {
            if ($conv->getType() === Constanst::TYPE_CONVERSATION['private']) {
                foreach ($conv->getConversationUsers() as $cu) {
                    $m = $cu->getMember();
                    if ($m && $m->getId() !== $currentUser->getId()) {
                        $partnerIds[] = (int) $m->getId();
                    }
                }
            }
        }
        $partnerImages = $this->imageRepository->getImagesMap(User::class, array_unique($partnerIds), 'avatar');

        return array_map(function (Conversation $conv) use ($currentUser, $partnerImages) {
            $data = $conv->jsonSerialize();

            // Lấy unreadCount của currentUser trong conversation này
            $myEntry = null;
            $partnerEntry = null;

            foreach ($conv->getConversationUsers() as $cu) {
                if ($cu->getMember()?->getId() === $currentUser->getId()) {
                    $myEntry = $cu;
                } else {
                    $partnerEntry = $cu;
                }
            }

            $data['unread']     = $myEntry?->getUnreadCount() ?? 0;
            $data['online']     = false;
            $data['memberCount'] = count(array_filter(
                $conv->getConversationUsers()->toArray(),
                fn(ConversationUser $conversationUser) => $conversationUser->isActive()
            ));

            // Với conversation private: lấy tên/avatar/id từ user đối diện
            $partner = $partnerEntry?->getMember();
            if ($conv->getType() === Constanst::TYPE_CONVERSATION['private']) {
                $data['receiverId']  = $partner?->getId();
                $data['nameUser']   = $partner?->getName() ?? $conv->getName();
                $data['avatarUser'] = ($partner && isset($partnerImages[(int) $partner->getId()])) ? $partnerImages[(int) $partner->getId()] : $conv->getAvatar();
            } else {
                $data['receiverId']  = null;
                $data['nameUser']   = $conv->getName();
                $data['avatarUser'] = $conv->getAvatar();
            }

            // Format thời gian tin nhắn cuối cho FE
            $data['time'] = $conv->getLastMessageAt()?->format('Y-m-d H:i:s');

            return $data;
        }, $conversations);
    }

    public function findById(int $id): array
    {
        $item = $this->conversationRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(ConversationDTO $dto, User $currentUser): array
    {
        $item = new Conversation();
        $item->setType($dto->type);

        $user = $this->userRepository->find($dto->userId);

        $conversationUser = new ConversationUser();
        $conversationUser->setMember($user);

        $currentConversationUser = new ConversationUser();
        $currentConversationUser->setMember($currentUser);

        $item->addConversationUser($conversationUser);
        $item->addConversationUser($currentConversationUser);

        $this->entityManager->persist($item);
        $this->entityManager->persist($conversationUser);
        $this->entityManager->persist($currentConversationUser);
        $this->entityManager->flush();

        $dataRes = $item->jsonSerialize();

        $dataRes['nameUser'] = $user?->getName();
        $dataRes['avatarUser'] = $user ? $this->imageRepository->getOneImage($user, 'avatar') : null;
        $dataRes['receiverId'] = $user?->getId();

        return $dataRes;
    }

    public function update(int $id, ConversationDTO $dto): array
    {
        $item = $this->conversationRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->conversationRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function markAsRead(int $conversationId, User $currentUser): array
    {
        $conversation = $this->conversationRepository->find($conversationId);

        if (!$conversation) {
            throw new \Exception(t('error.not_found'));
        }

        // Tìm bản ghi ConversationUser của current user trong conversation này
        $convUser = $this->conversationUserRepository->findOneBy([
            'conversation' => $conversation,
            'member'       => $currentUser,
        ]);

        if ($convUser) {
            $convUser->resetUnread(); // unreadCount = 0, lastReadAt = now
            $this->entityManager->flush();
        }

        $messages = $this->messageRepository->findBy([
            'conversation' => $conversation,
            'receiver'       => $currentUser,
            'isSeen'         => false,
        ]);

        $seenAt = new \DateTimeImmutable();
        $messageCodes = [];
        $senderIds = [];

        foreach ($messages as $msg) {
            $msg->setIsSeen(true);
            $msg->setSeenAt($seenAt);
            $messageCodes[] = $msg->getCode();
            $senderId = $msg->getSender()?->getId();
            if ($senderId) {
                $senderIds[$senderId] = true;
            }
        }

        if (count($messages) > 0) {
            $this->entityManager->flush();

            $payload = json_encode([
                'type' => 'message_seen',
                'conversationId' => $conversationId,
                'readerId' => $currentUser->getId(),
                'messageCodes' => array_values(array_filter($messageCodes)),
                'seenAt' => $seenAt->format('Y-m-d H:i:s'),
            ]);

            $topicTemplate = $this->mercureConfig['topics']['message'];
            foreach (array_keys($senderIds) as $senderId) {
                $topic = str_replace(':userId', (string) $senderId, $topicTemplate);
                $this->hub->publish(new Update($topic, $payload, false));
            }
        }

        return [
            'type' => 'message_seen',
            'conversationId' => $conversationId,
            'readerId' => $currentUser->getId(),
            'markedCount' => count($messages),
            'messageCodes' => array_values(array_filter($messageCodes)),
            'seenAt' => count($messages) > 0 ? $seenAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
