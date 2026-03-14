<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MessageDTO
{
    public function __construct(
        public readonly ?int $receiverId = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?int $conversationId = null,

        public readonly ?string $content = null,

        public ?array $imageFiles = null,

        public ?array $files = null,
    ) {}
}
