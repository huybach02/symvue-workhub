<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockReceiptInspectingItemDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Positive(groups: ['create'])]
        public ?int $receiptItemId = null,

        /** @var StockReceiptInspectingLotDTO[] */
        #[Assert\Valid(groups: ['create'])]
        #[Assert\Count(min: 1, groups: ['create'])]
        public array $lots = [],
    ) {}
}
