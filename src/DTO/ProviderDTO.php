<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProviderDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $code = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $phone = null,

        #[Assert\Email(groups: ['create', 'update'])]
        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $email = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $address = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $taxNumber = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $bankName = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $bankNumber = null,

        public readonly ?string $note = null,

        public readonly ?int $status = null,
    ) {}
}
