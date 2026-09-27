<?php

declare(strict_types=1);

namespace App\DTO;

use App\Class\SaleOrderPaymentStatus;
use App\Class\SaleOrderStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateSaleOrderStatusDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [
            SaleOrderStatus::Created->value,
            SaleOrderStatus::Processing->value,
            SaleOrderStatus::Shipped->value,
            SaleOrderStatus::Completed->value,
        ])]
        public ?string $status = null,

        #[Assert\Choice(choices: [
            SaleOrderPaymentStatus::Unpaid->value,
            SaleOrderPaymentStatus::Paid->value,
            SaleOrderPaymentStatus::Refunded->value,
        ])]
        public ?int $paymentStatus = null,
    ) {}
}
