<?php

declare(strict_types=1);

namespace App\Message;

final class ThongBaoCaNhanMessage
{
    public function __construct(
        private readonly int $fromUserId,
        private readonly int $toUserId,
        private readonly string $title,
        private readonly string $body,
        private readonly string $code,
        private readonly string $link = ""
    ) {}

    public function getFromUserId(): int
    {
        return $this->fromUserId;
    }

    public function getToUserId(): int
    {
        return $this->toUserId;
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
}
