<?php

declare(strict_types=1);

namespace App\Message;

final class ChatMessage
{
    public function __construct(
        private readonly string $code,
        private readonly int $senderId,
        private readonly int $receiverId,
        private readonly int $conversationId,
        private readonly string $content,
        private readonly \DateTimeImmutable $time
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function getReceiverId(): int
    {
        return $this->receiverId;
    }

    public function getConversationId(): int
    {
        return $this->conversationId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTime(): \DateTimeImmutable
    {
        return $this->time;
    }
}
