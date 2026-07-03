<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\EntityExists;
use App\Entity\User;

class ConversationDTO
{
    public function __construct(
        // Field bắt buộc cho cả create và update
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[EntityExists(entityClass: User::class, groups: ['create', 'update'])]
        public readonly ?int $userId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $type = null,
    ) {}
}
