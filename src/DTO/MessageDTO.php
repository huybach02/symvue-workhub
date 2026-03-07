<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MessageDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?int $receiverId = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?int $conversationId = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?string $content = null,
    ) {}
}
