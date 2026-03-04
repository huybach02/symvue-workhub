<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDTO
{
    public function __construct(
        // Ảnh đại diện
        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $avatar = null,

        // ===== THÔNG TIN CÁ NHÂN =====

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(max: 50, groups: ['create', 'update'])]
        public readonly ?string $maNhanVien = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 2, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: ['male', 'female'], groups: ['create', 'update'])]
        public readonly ?string $gender = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $birthday = null,

        // CMND/CCCD: 9 số (CMND cũ) hoặc 12 số (CCCD mới)
        #[Assert\Regex(
            pattern: '/^[0-9]{9}$|^[0-9]{12}$/',
            message: 'CMND/CCCD phải là số CMND (9 số) hoặc CCCD (12 số) hợp lệ.',
            groups: ['create', 'update']
        )]
        public readonly ?string $cmnd = null,

        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $ngayCapCmnd = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $noiCapCmnd = null,

        // ===== THÔNG TIN CÔNG VIỆC =====

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $boPhanId = null,

        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $ngayVaoLam = null,

        // Trạng thái: 1=Hoạt động, 0=Không hoạt động
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = null,

        // ===== THÔNG TIN LIÊN HỆ =====

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email(groups: ['create', 'update'])]
        public readonly ?string $email = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Regex(
            pattern: '/^(84|0[3|5|7|8|9])+([0-9]{8})\b/',
            message: 'Số điện thoại không đúng định dạng số điện thoại VN.',
            groups: ['create', 'update']
        )]
        public readonly ?string $phone = null,

        // #[Assert\NotBlank(groups: ['create', 'update'])]
        // #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $province = null,

        // #[Assert\NotBlank(groups: ['create', 'update'])]
        // #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $ward = null,

        // #[Assert\NotBlank(groups: ['create', 'update'])]
        // #[Assert\Length(min: 5, max: 500, groups: ['create', 'update'])]
        public readonly ?string $address = null,

    ) {}
}
