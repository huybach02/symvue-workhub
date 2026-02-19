<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDTO
{
    public function __construct(
        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $avatar = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 2, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email(groups: ['create', 'update'])]
        public readonly ?string $email = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Regex(pattern: '/^[0-9]{10,11}$/', message: 'Số điện thoại phải có 10-11 chữ số', groups: ['create', 'update'])]
        public readonly ?string $phone = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $birthday = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: ['male', 'female', 'other'], groups: ['create', 'update'])]
        public readonly ?string $gender = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $province = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $ward = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 5, max: 500, groups: ['create', 'update'])]
        public readonly ?string $address = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?int $boPhanId = null,

        #[Assert\Choice(choices: [1, 2], groups: ['create', 'update'])]
        public readonly ?int $hinhThucLamViec = null,

        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $isNgoaiGio = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = null,

    ) {}
}
