<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\ConversationDTO;
use App\Entity\Conversation;
use App\Entity\ConversationUser;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ConversationUserRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationUserRepository $conversationUserRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageRepository $messageRepository,
    ) {}

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
            ->setParameter('type', 'private')
            ->orderBy('u.name', 'ASC');

        $users = $qb->getQuery()->getResult();

        return array_map(fn(User $user) => [
            'id'     => $user->getId(),
            'name'   => $user->getName(),
            'email'  => $user->getEmail(),
            'avatar' => $user->getImage(),
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

        return array_map(function (Conversation $conv) use ($currentUser) {
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

            // Với conversation private: lấy tên/avatar/id từ user đối diện
            $partner = $partnerEntry?->getMember();
            $data['receiverId']  = $partner?->getId();
            $data['nameUser']   = $partner?->getName() ?? $conv->getName();
            $data['avatarUser'] = $partner?->getImage() ?? $conv->getAvatar();

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
        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

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

        $dataRes['nameUser'] = $user->getName();
        $dataRes['avatarUser'] = $user->getImage();
        $dataRes['receiverId'] = $user->getId();

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

    public function markAsRead(int $conversationId, User $currentUser): void
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

        foreach ($messages as $msg) {
            $msg->setIsSeen(true);
            $msg->setSeenAt(new \DateTimeImmutable());
        }

        if (count($messages) > 0) {
            $this->entityManager->flush();
        }
    }
}
