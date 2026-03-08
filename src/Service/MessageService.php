<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\MessageDTO;
use App\Entity\Message;
use App\Entity\User;
use App\Message\ChatMessage;
use App\Repository\ConversationRepository;
use App\Repository\ConversationUserRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    private array $mercureConfig;

    public function __construct(
        private HubInterface $hub,
        private MessageBusInterface $messageBus,
        private readonly MessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationUserRepository $conversationUserRepository,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
    }

    public function findAll(array $params): array
    {
        $qb = $this->messageRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Message $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findByConversation(int $conversationId): array
    {
        $conversation = $this->conversationRepository->find($conversationId);

        if (!$conversation) {
            throw new \Exception(t('error.not_found'));
        }

        $messages = $this->messageRepository->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn(Message $msg) => $msg->jsonSerialize(), $messages);
    }

    public function create(MessageDTO $dto, User $currentUser): array
    {
        $code = uniqid();
        $time = new \DateTimeImmutable();

        $topicTemplate = $this->mercureConfig['topics']['message'];

        $data = [
            'type'           => 'message',
            'code'           => $code,
            'conversationId' => $dto->conversationId,
            'senderId'       => $currentUser->getId(),
            'senderName'     => $currentUser->getName(),
            'receiverId'     => $dto->receiverId,
            'content'        => $dto->content,
            'images'         => [],
            'files'          => [],
            'isSeen'         => false,
            'seenAt'         => null,
            'isDeleted'      => false,
            'deletedAt'      => null,
            'time'           => formatMessageTime($time),
        ];

        $payload = json_encode($data);

        // Tăng unread count của người nhận trong DB
        $conversation = $this->conversationRepository->find($dto->conversationId);
        if ($conversation) {
            $receiverConvUser = $this->conversationUserRepository->findOneBy([
                'conversation' => $conversation,
                'member'       => $this->userRepository->find($dto->receiverId),
            ]);
            if ($receiverConvUser) {
                $receiverConvUser->incrementUnread();
                $this->entityManager->flush();
            }
        }

        // Publish đến receiver để receiver nhận được tin nhắn mới
        $topicReceiver = str_replace(':userId', (string) $dto->receiverId, $topicTemplate);
        $this->hub->publish(new Update($topicReceiver, $payload, false));

        // Publish đến sender để sender cũng thấy tin nhắn mình vừa gửi
        $topicSender = str_replace(':userId', (string) $currentUser->getId(), $topicTemplate);
        $this->hub->publish(new Update($topicSender, $payload, false));

        $this->messageBus->dispatch(
            new ChatMessage(
                $code,
                $currentUser->getId(),
                $dto->receiverId,
                $dto->conversationId,
                $dto->content,
                $time
            )
        );

        return $data;
    }

    public function update(int $id, MessageDTO $dto): array
    {
        $item = $this->messageRepository->find($id);

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
        $item = $this->messageRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }
}
