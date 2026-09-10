<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DiningTableRangeDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'Số bàn bắt đầu không được để trống')]
        #[Assert\Positive(message: 'Số bàn bắt đầu phải là số nguyên dương')]
        public readonly ?int $from = null,

        #[Assert\NotNull(message: 'Số bàn kết thúc không được để trống')]
        #[Assert\Positive(message: 'Số bàn kết thúc phải là số nguyên dương')]
        #[Assert\GreaterThanOrEqual(
            propertyPath: 'from',
            message: 'Số bàn kết thúc phải lớn hơn hoặc bằng số bàn bắt đầu'
        )]
        public readonly ?int $to = null,
    ) {}
}
