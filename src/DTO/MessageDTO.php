<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\EntityExists;
use App\Entity\Conversation;

class MessageDTO
{
    public function __construct(
        public readonly ?int $receiverId = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[EntityExists(entityClass: Conversation::class, groups: ['create'])]
        public readonly ?int $conversationId = null,

        public readonly ?string $content = null,

        public ?array $imageFiles = null,

        public ?array $files = null,
    ) {}
}
