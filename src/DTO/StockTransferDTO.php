<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockTransferDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'Vui lòng chọn đề xuất chuyển kho')]
        #[Assert\Positive(message: 'Đề xuất chuyển kho không hợp lệ')]
        public ?int $requestId = null,
    ) {
    }
}
