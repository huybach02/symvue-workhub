<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InventoryBalanceRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryBalanceRepository::class)]
#[ORM\Table(name: 'inventory_balance')]
class InventoryBalance
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho đang giữ tồn'])]
    private ?Warehouse $warehouse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'merchandise_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Merchandise'])]
    private ?Merchandise $merchandise = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'lot_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Lot cụ thể'])]
    private ?InventoryLot $lot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Tổng tồn vật lý theo base unit'])]
    private string $onHandBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Số đã giữ chỗ cho xuất kho/sản xuất'])]
    private string $reservedBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Số bị khóa, không được sử dụng'])]
    private string $blockedBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Kiểm soát cập nhật đồng thời'])]
    #[ORM\Version]
    private ?int $version = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;
        return $this;
    }

    public function getMerchandise(): ?Merchandise
    {
        return $this->merchandise;
    }

    public function setMerchandise(?Merchandise $merchandise): static
    {
        $this->merchandise = $merchandise;
        return $this;
    }

    public function getLot(): ?InventoryLot
    {
        return $this->lot;
    }

    public function setLot(?InventoryLot $lot): static
    {
        $this->lot = $lot;
        return $this;
    }

    public function getOnHandBaseQuantity(): string
    {
        return $this->onHandBaseQuantity;
    }

    public function setOnHandBaseQuantity(string $onHandBaseQuantity): static
    {
        $this->onHandBaseQuantity = $onHandBaseQuantity;
        return $this;
    }

    public function getReservedBaseQuantity(): string
    {
        return $this->reservedBaseQuantity;
    }

    public function setReservedBaseQuantity(string $reservedBaseQuantity): static
    {
        $this->reservedBaseQuantity = $reservedBaseQuantity;
        return $this;
    }

    public function getBlockedBaseQuantity(): string
    {
        return $this->blockedBaseQuantity;
    }

    public function setBlockedBaseQuantity(string $blockedBaseQuantity): static
    {
        $this->blockedBaseQuantity = $blockedBaseQuantity;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse?->getId(),
            'merchandise_id' => $this->merchandise?->getId(),
            'lot_id' => $this->lot?->getId(),
            'on_hand_base_quantity' => $this->onHandBaseQuantity,
            'reserved_base_quantity' => $this->reservedBaseQuantity,
            'blocked_base_quantity' => $this->blockedBaseQuantity,
            'version' => $this->version,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
