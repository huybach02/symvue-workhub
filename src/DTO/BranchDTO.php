<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BranchDTO
{
    public function __construct(
        // Field bắt buộc cho cả create và update
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        // Field chỉ bắt buộc khi create, optional khi update
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email(groups: ['create', 'update'])]
        public readonly ?string $email = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?string $phone = null,

        public readonly ?string $address = null,

        public readonly ?int $status = null,

        public readonly ?string $note = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $image = null,
    ) {}
}
