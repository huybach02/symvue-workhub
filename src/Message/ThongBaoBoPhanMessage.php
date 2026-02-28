<?php

declare(strict_types=1);

namespace App\Message;

final class ThongBaoBoPhanMessage
{
    public function __construct(
        private readonly int $fromUserId,
        private readonly int $boPhanId,
        private readonly string $title,
        private readonly string $body,
        private readonly string $code,
        private readonly string $type = "primary",
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly string $link = ""
    ) {}

    public function getFromUserId(): int
    {
        return $this->fromUserId;
    }

    public function getBoPhanId(): int
    {
        return $this->boPhanId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
