<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Branch;
use App\Entity\DiningTable;
use App\Validator\EntityExists;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSaleOrderDTO
{
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Count(min: 1, groups: ['create'])]
        #[Assert\Valid(groups: ['create'])]
        /** @var CreateSaleOrderItemDTO[]|null */
        public readonly ?array $items = null,

        #[Assert\NotNull(message: 'Vui lòng chọn bàn ăn', groups: ['create'])]
        #[Assert\Type(type: 'integer', groups: ['create'])]
        #[EntityExists(entityClass: DiningTable::class, groups: ['create'])]
        public readonly ?int $diningTableId = null,

        #[Assert\Type(type: 'integer', groups: ['create'])]
        #[EntityExists(entityClass: Branch::class, groups: ['create'])]
        public readonly ?int $branchId = null,

        #[Assert\Length(max: 1000, groups: ['create'])]
        public readonly ?string $orderNote = null,

        public readonly string|int|float|null $totalAmount = null,

        #[Assert\Length(max: 30, groups: ['create'])]
        public readonly ?string $paymentMethod = null,
    ) {}
}
