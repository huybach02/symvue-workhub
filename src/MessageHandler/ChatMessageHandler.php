<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Message\ChatMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// Handler này chạy bất đồng bộ (async) - không block request HTTP
#[AsMessageHandler]
final class ChatMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ChatMessage $message): void
    {

        $sender       = $this->entityManager->getReference(User::class, $message->getSenderId());
        $conversation = $this->entityManager->getReference(Conversation::class, $message->getConversationId());

        $item = new Message();
        $item->setCode($message->getCode());
        $item->setSender($sender);
        if ($message->getReceiverId()) {
            $receiver = $this->entityManager->getReference(User::class, $message->getReceiverId());
            $item->setReceiver($receiver);
        }
        $item->setConversation($conversation);
        $item->setContent($message->getContent());
        $item->setImages($message->getImages());
        $item->setFiles($message->getFiles());
        $item->setTime(new \DateTimeImmutable($message->getTime()));

        $conversation->setLastMessage($message->getLastMessage());
        $conversation->setLastMessageAt(new \DateTimeImmutable($message->getTime()));

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }
}
