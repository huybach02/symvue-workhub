<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\DTO\MessageDTO;
use App\Entity\ConversationUser;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ConversationUserRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MessageService
{
    private array $mercureConfig;

    public function __construct(
        private HubInterface $hub,
        private readonly MessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
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

    public function create(Request $request, MessageDTO $dto, User $currentUser): array
    {
        $conversation = $this->conversationRepository->find($dto->conversationId);
        if (!$conversation) {
            throw new \Exception(t('error.not_found'));
        }

        $currentConversationUser = $this->conversationUserRepository->findOneBy([
            'conversation' => $conversation,
            'member' => $currentUser,
        ]);
        if (!$currentConversationUser || !$currentConversationUser->isActive()) {
            throw new \Exception(t('error.not_found'));
        }

        $code = uniqid();
        $time = new \DateTimeImmutable();
        $baseUrl = $request->getSchemeAndHttpHost();
        $topicTemplate = $this->mercureConfig['topics']['message'];
        $conversationType = $conversation->getType();

        $images = [];
        $files  = [];

        if (!empty($dto->imageFiles)) {
            $images = array_map(fn($image) => uploadFile($image, 'chats/images', $baseUrl), $dto->imageFiles);
        }

        if (!empty($dto->files)) {
            $files = array_map(function ($file) use ($baseUrl) {
                $originalName = $file->getClientOriginalName();
                $size         = $file->getSize();
                $mime         = $file->getMimeType();
                $url          = uploadFile($file, 'chats/files', $baseUrl, 'file');
                return [
                    'name' => $originalName,
                    'url'  => $url,
                    'size' => $size,
                    'mime' => $mime,
                ];
            }, $dto->files);
        }

        $memberEntries = array_values(array_filter(
            $conversation->getConversationUsers()->toArray(),
            fn(ConversationUser $conversationUser) => $conversationUser->isActive()
        ));

        $recipientEntries = array_values(array_filter(
            $memberEntries,
            fn(ConversationUser $conversationUser) => $conversationUser->getMember()?->getId() !== $currentUser->getId()
        ));

        $resolvedReceiverId = null;
        if ($conversationType === Constanst::TYPE_CONVERSATION['private']) {
            $resolvedReceiverId = $dto->receiverId;
            if (!$resolvedReceiverId && count($recipientEntries) === 1) {
                $resolvedReceiverId = $recipientEntries[0]->getMember()?->getId();
            }

            $recipientEntries = array_values(array_filter(
                $recipientEntries,
                fn(ConversationUser $conversationUser) => $conversationUser->getMember()?->getId() === $resolvedReceiverId
            ));

            if (!$resolvedReceiverId || count($recipientEntries) === 0) {
                throw new \Exception(t('error.not_found'));
            }
        }

        $lastMessage = '';
        if (!$dto->content && count($images) > 0) {
            $lastMessage = t('chat.has_sent_image', ['%count%' => count($images)]);
        } elseif (!$dto->content && count($files) > 0) {
            $lastMessage = t('chat.has_sent_file', ['%count%' => count($files)]);
        } else {
            $lastMessage = $dto->content;
        }

        $message = new Message();
        $message->setCode($code);
        $message->setConversation($conversation);
        $message->setSender($currentUser);
        if ($resolvedReceiverId) {
            $receiverEntry = $recipientEntries[0] ?? null;
            $receiver = $receiverEntry?->getMember();
            if ($receiver) {
                $message->setReceiver($receiver);
            }
        }
        $message->setContent($dto->content);
        $message->setImages($images);
        $message->setFiles($files);
        $message->setTime($time);

        $conversation->setLastMessage($lastMessage);
        $conversation->setLastMessageAt($time);

        $this->entityManager->persist($message);

        $data = [
            'type' => 'message',
            'code' => $code,
            'conversationId' => $dto->conversationId,
            'conversationType' => $conversationType,
            'senderId' => $currentUser->getId(),
            'senderName' => $currentUser->getName(),
            'receiverId' => $resolvedReceiverId,
            'content' => $dto->content,
            'images' => $images,
            'files' => $files,
            'isSeen' => false,
            'seenAt' => null,
            'isDeleted' => false,
            'deletedAt' => null,
            'time' => $time->format('Y-m-d H:i:s'),
        ];

        foreach ($recipientEntries as $recipientEntry) {
            $recipientEntry->incrementUnread();
        }

        $this->entityManager->flush();

        $payload = json_encode($data);

        foreach ($recipientEntries as $recipientEntry) {
            $recipientId = $recipientEntry->getMember()?->getId();
            if (!$recipientId) {
                continue;
            }

            $topicReceiver = str_replace(':userId', (string) $recipientId, $topicTemplate);
            $this->hub->publish(new Update($topicReceiver, $payload, false));
        }

        $topicSender = str_replace(':userId', (string) $currentUser->getId(), $topicTemplate);
        $this->hub->publish(new Update($topicSender, $payload, false));

        return $data;
    }

    public function update(int $id, MessageDTO $dto): array
    {
        $item = $this->messageRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

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
