<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockReceiptItemLotRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockReceiptItemLotRepository::class)]
#[ORM\Table(name: 'stock_receipt_item_lot')]
#[ORM\UniqueConstraint(name: 'UNIQ_STOCK_RECEIPT_ITEM_LOT_CLIENT_UUID', fields: ['clientLineUuid'])]
#[ORM\UniqueConstraint(
    name: 'UNIQ_STOCK_RECEIPT_ITEM_LOT_DATE_PAIR',
    columns: ['receipt_item_id', 'manufacture_date', 'expiry_date']
)]
class StockReceiptItemLot
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StockReceiptItem::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(name: 'receipt_item_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Merchandise chứa dòng lô'])]
    private ?StockReceiptItem $receiptItem = null;

    #[ORM\Column(type: Types::GUID, options: ['comment' => 'ID tạm ổn định để UI lưu/chỉnh sửa dòng trước khi post'])]
    private ?string $clientLineUuid = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng nhà cung cấp giao thực tế theo đơn vị người dùng chọn'])]
    private ?string $receivedQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'received_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đơn vị nhận thực tế, ví dụ lốc'])]
    private ?Unit $receivedUnit = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Snapshot nhãn đơn vị nhận'])]
    private ?string $receivedUnitLabelSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8, options: ['comment' => 'Hệ số quy đổi đơn vị thực tế về base unit'])]
    private ?string $receivedFactorToBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng giao thực tế đã quy đổi về base unit'])]
    private ?string $receivedBaseQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng chấp nhận nhập kho theo đơn vị nhận thực tế'])]
    private ?string $acceptedQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng chấp nhận nhập kho đã quy đổi về base unit'])]
    private ?string $acceptedBaseQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Số lượng bị từ chối theo đơn vị nhận thực tế'])]
    private string $rejectedQuantity = '0.000000';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Số lượng bị từ chối đã quy đổi về base unit'])]
    private string $rejectedBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Lý do từ chối như hư hỏng hoặc sai chất lượng'])]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => 'Ngày sản xuất của lô'])]
    private ?\DateTimeInterface $manufactureDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => 'Hạn sử dụng của lô'])]
    private ?\DateTimeInterface $expiryDate = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => 'Mã lô do nhà cung cấp/nhà sản xuất cung cấp'])]
    private ?string $supplierLotCode = null;

    #[ORM\OneToOne(targetEntity: InventoryLot::class, inversedBy: 'sourceReceiptLotLine')]
    #[ORM\JoinColumn(name: 'inventory_lot_id', referencedColumnName: 'id', unique: true, nullable: true, options: ['comment' => 'Lot tồn kho được tạo khi post dòng kiểm hàng'])]
    private ?InventoryLot $inventoryLot = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú cho riêng lô'])]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptItem(): ?StockReceiptItem
    {
        return $this->receiptItem;
    }

    public function setReceiptItem(?StockReceiptItem $receiptItem): static
    {
        $this->receiptItem = $receiptItem;
        return $this;
    }

    public function getClientLineUuid(): ?string
    {
        return $this->clientLineUuid;
    }

    public function setClientLineUuid(string $clientLineUuid): static
    {
        $this->clientLineUuid = $clientLineUuid;
        return $this;
    }

    public function getReceivedQuantity(): ?string
    {
        return $this->receivedQuantity;
    }

    public function setReceivedQuantity(string $receivedQuantity): static
    {
        $this->receivedQuantity = $receivedQuantity;
        return $this;
    }

    public function getReceivedUnit(): ?Unit
    {
        return $this->receivedUnit;
    }

    public function setReceivedUnit(?Unit $receivedUnit): static
    {
        $this->receivedUnit = $receivedUnit;
        return $this;
    }

    public function getReceivedUnitLabelSnapshot(): ?string
    {
        return $this->receivedUnitLabelSnapshot;
    }

    public function setReceivedUnitLabelSnapshot(string $receivedUnitLabelSnapshot): static
    {
        $this->receivedUnitLabelSnapshot = $receivedUnitLabelSnapshot;
        return $this;
    }

    public function getReceivedFactorToBase(): ?string
    {
        return $this->receivedFactorToBase;
    }

    public function setReceivedFactorToBase(string $receivedFactorToBase): static
    {
        $this->receivedFactorToBase = $receivedFactorToBase;
        return $this;
    }

    public function getReceivedBaseQuantity(): ?string
    {
        return $this->receivedBaseQuantity;
    }

    public function setReceivedBaseQuantity(string $receivedBaseQuantity): static
    {
        $this->receivedBaseQuantity = $receivedBaseQuantity;
        return $this;
    }

    public function getAcceptedQuantity(): ?string
    {
        return $this->acceptedQuantity;
    }

    public function setAcceptedQuantity(string $acceptedQuantity): static
    {
        $this->acceptedQuantity = $acceptedQuantity;
        return $this;
    }

    public function getAcceptedBaseQuantity(): ?string
    {
        return $this->acceptedBaseQuantity;
    }

    public function setAcceptedBaseQuantity(string $acceptedBaseQuantity): static
    {
        $this->acceptedBaseQuantity = $acceptedBaseQuantity;
        return $this;
    }

    public function getRejectedQuantity(): string
    {
        return $this->rejectedQuantity;
    }

    public function setRejectedQuantity(string $rejectedQuantity): static
    {
        $this->rejectedQuantity = $rejectedQuantity;
        return $this;
    }

    public function getRejectedBaseQuantity(): string
    {
        return $this->rejectedBaseQuantity;
    }

    public function setRejectedBaseQuantity(string $rejectedBaseQuantity): static
    {
        $this->rejectedBaseQuantity = $rejectedBaseQuantity;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getManufactureDate(): ?\DateTimeInterface
    {
        return $this->manufactureDate;
    }

    public function setManufactureDate(\DateTimeInterface $manufactureDate): static
    {
        $this->manufactureDate = $manufactureDate;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(\DateTimeInterface $expiryDate): static
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getSupplierLotCode(): ?string
    {
        return $this->supplierLotCode;
    }

    public function setSupplierLotCode(?string $supplierLotCode): static
    {
        $this->supplierLotCode = $supplierLotCode;
        return $this;
    }

    public function getInventoryLot(): ?InventoryLot
    {
        return $this->inventoryLot;
    }

    public function setInventoryLot(?InventoryLot $inventoryLot): static
    {
        if ($this->inventoryLot === $inventoryLot) {
            return $this;
        }

        $previousInventoryLot = $this->inventoryLot;
        $this->inventoryLot = $inventoryLot;

        if ($previousInventoryLot?->getSourceReceiptLotLine() === $this) {
            $previousInventoryLot->setSourceReceiptLotLine(null);
        }

        if ($inventoryLot !== null && $inventoryLot->getSourceReceiptLotLine() !== $this) {
            $inventoryLot->setSourceReceiptLotLine($this);
        }

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'receipt_item_id' => $this->receiptItem?->getId(),
            'client_line_uuid' => $this->clientLineUuid,
            'received_quantity' => $this->receivedQuantity,
            'received_unit_id' => $this->receivedUnit?->getId(),
            'received_unit_label_snapshot' => $this->receivedUnitLabelSnapshot,
            'received_factor_to_base' => $this->receivedFactorToBase,
            'received_base_quantity' => $this->receivedBaseQuantity,
            'accepted_quantity' => $this->acceptedQuantity,
            'accepted_base_quantity' => $this->acceptedBaseQuantity,
            'rejected_quantity' => $this->rejectedQuantity,
            'rejected_base_quantity' => $this->rejectedBaseQuantity,
            'rejection_reason' => $this->rejectionReason,
            'manufacture_date' => $this->manufactureDate?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'supplier_lot_code' => $this->supplierLotCode,
            'inventory_lot_id' => $this->inventoryLot?->getId(),
            'note' => $this->note,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
