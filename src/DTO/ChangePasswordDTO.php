<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "Mật khẩu hiện tại không được để trống.")]
        public readonly string $currentPassword,

        #[Assert\NotBlank(message: "Mật khẩu mới không được để trống.")]
        #[Assert\Length(min: 6, message: "Mật khẩu mới phải có ít nhất 6 ký tự.")]
        public readonly string $newPassword,

        #[Assert\NotBlank(message: "Xác nhận mật khẩu mới không được để trống.")]
        public readonly string $confirmPassword,
    ) {}
}
