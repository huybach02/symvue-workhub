<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InventoryMovementRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryMovementRepository::class)]
#[ORM\Table(name: 'inventory_movement')]
class InventoryMovement
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Loại biến động kho'])]
    private ?string $movementType = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Loại nghiệp vụ nguồn'])]
    private ?string $sourceType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'production_order_item_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Thành phẩm sản xuất liên quan'])]
    private ?ProductionOrderItem $productionOrderItem = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Metadata nguồn chi tiết'])]
    private ?array $sourceRef = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'stock_transfer_id', referencedColumnName: 'id', nullable: true)]
    private ?StockTransfer $stockTransfer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho chịu ảnh hưởng'])]
    private ?Warehouse $warehouse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'merchandise_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Merchandise biến động'])]
    private ?Merchandise $merchandise = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'lot_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Lot biến động'])]
    private ?InventoryLot $lot = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'receipt_provider_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Provider process nguồn'])]
    private ?StockReceiptProvider $receiptProvider = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'receipt_lot_line_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Dòng lot kiểm hàng nguồn'])]
    private ?StockReceiptItemLot $receiptLotLine = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng biến động theo base unit; nhập là số dương, xuất là số âm'])]
    private ?string $quantityBaseDelta = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'base_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Base unit tại thời điểm movement'])]
    private ?Unit $baseUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true, options: ['comment' => 'Giá vốn trên một base unit tại thời điểm post'])]
    private ?string $unitCostBase = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'reversal_of_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Movement gốc nếu đây là movement đảo'])]
    private ?self $reversalOf = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Lý do hoặc ghi chú'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm movement có hiệu lực'])]
    private ?\DateTimeInterface $postedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'posted_by', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Người thực hiện post'])]
    private ?User $postedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovementType(): ?string
    {
        return $this->movementType;
    }

    public function setMovementType(string $movementType): static
    {
        $this->movementType = $movementType;
        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): static
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getProductionOrderItem(): ?ProductionOrderItem
    {
        return $this->productionOrderItem;
    }

    public function setProductionOrderItem(?ProductionOrderItem $productionOrderItem): static
    {
        $this->productionOrderItem = $productionOrderItem;

        return $this;
    }

    public function getSourceRef(): ?array
    {
        return $this->sourceRef;
    }

    public function setSourceRef(?array $sourceRef): static
    {
        $this->sourceRef = $sourceRef;

        return $this;
    }

    public function getStockTransfer(): ?StockTransfer
    {
        return $this->stockTransfer;
    }

    public function setStockTransfer(?StockTransfer $stockTransfer): static
    {
        $this->stockTransfer = $stockTransfer;

        return $this;
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

    public function getReceiptProvider(): ?StockReceiptProvider
    {
        return $this->receiptProvider;
    }

    public function setReceiptProvider(?StockReceiptProvider $receiptProvider): static
    {
        $this->receiptProvider = $receiptProvider;
        return $this;
    }

    public function getReceiptLotLine(): ?StockReceiptItemLot
    {
        return $this->receiptLotLine;
    }

    public function setReceiptLotLine(?StockReceiptItemLot $receiptLotLine): static
    {
        $this->receiptLotLine = $receiptLotLine;
        return $this;
    }

    public function getQuantityBaseDelta(): ?string
    {
        return $this->quantityBaseDelta;
    }

    public function setQuantityBaseDelta(string $quantityBaseDelta): static
    {
        $this->quantityBaseDelta = $quantityBaseDelta;
        return $this;
    }

    public function getBaseUnit(): ?Unit
    {
        return $this->baseUnit;
    }

    public function setBaseUnit(?Unit $baseUnit): static
    {
        $this->baseUnit = $baseUnit;
        return $this;
    }

    public function getUnitCostBase(): ?string
    {
        return $this->unitCostBase;
    }

    public function setUnitCostBase(?string $unitCostBase): static
    {
        $this->unitCostBase = $unitCostBase;
        return $this;
    }

    public function getReversalOf(): ?self
    {
        return $this->reversalOf;
    }

    public function setReversalOf(?self $reversalOf): static
    {
        $this->reversalOf = $reversalOf;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getPostedAt(): ?\DateTimeInterface
    {
        return $this->postedAt;
    }

    public function setPostedAt(\DateTimeInterface $postedAt): static
    {
        $this->postedAt = $postedAt;
        return $this;
    }

    public function getPostedBy(): ?User
    {
        return $this->postedBy;
    }

    public function setPostedBy(?User $postedBy): static
    {
        $this->postedBy = $postedBy;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'movement_type' => $this->movementType,
            'source_type' => $this->sourceType,
            'production_order_item_id' => $this->productionOrderItem?->getId(),
            'source_ref' => $this->sourceRef,
            'stock_transfer_id' => $this->stockTransfer?->getId(),
            'warehouse_id' => $this->warehouse?->getId(),
            'merchandise_id' => $this->merchandise?->getId(),
            'lot_id' => $this->lot?->getId(),
            'receipt_provider_id' => $this->receiptProvider?->getId(),
            'receipt_lot_line_id' => $this->receiptLotLine?->getId(),
            'quantity_base_delta' => $this->quantityBaseDelta,
            'base_unit_id' => $this->baseUnit?->getId(),
            'unit_cost_base' => $this->unitCostBase,
            'reversal_of_id' => $this->reversalOf?->getId(),
            'note' => $this->note,
            'posted_at' => $this->postedAt?->format('Y-m-d H:i:s'),
            'posted_by' => $this->postedBy?->getId(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
