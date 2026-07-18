<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InventoryLotRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryLotRepository::class)]
#[ORM\Table(name: 'inventory_lot')]
#[ORM\UniqueConstraint(name: 'UNIQ_INVENTORY_LOT_INTERNAL_CODE', fields: ['internalCode'])]
class InventoryLot
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => 'Mã lot nội bộ tự sinh'])]
    private ?string $internalCode = null;

    #[ORM\OneToOne(targetEntity: StockReceiptItemLot::class, mappedBy: 'inventoryLot')]
    private ?StockReceiptItemLot $sourceReceiptLotLine = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'merchandise_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Merchandise thuộc lot'])]
    private ?Merchandise $merchandise = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Nhà cung cấp của lot'])]
    private ?Provider $provider = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => 'Mã lô của nhà cung cấp nếu có'])]
    private ?string $supplierLotCode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => 'Ngày sản xuất'])]
    private ?\DateTimeInterface $manufactureDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => 'Hạn sử dụng'])]
    private ?\DateTimeInterface $expiryDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm lot được nhận và post vào kho'])]
    private ?\DateTimeInterface $receivedAt = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Trạng thái sử dụng của lot: AVAILABLE, BLOCKED, EXPIRED, DEPLETED'])]
    private ?string $status = 'AVAILABLE';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInternalCode(): ?string
    {
        return $this->internalCode;
    }

    public function setInternalCode(string $internalCode): static
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    public function getSourceReceiptLotLine(): ?StockReceiptItemLot
    {
        return $this->sourceReceiptLotLine;
    }

    public function setSourceReceiptLotLine(?StockReceiptItemLot $sourceReceiptLotLine): static
    {
        if ($this->sourceReceiptLotLine === $sourceReceiptLotLine) {
            return $this;
        }

        $previousSourceReceiptLotLine = $this->sourceReceiptLotLine;
        $this->sourceReceiptLotLine = $sourceReceiptLotLine;

        if ($previousSourceReceiptLotLine?->getInventoryLot() === $this) {
            $previousSourceReceiptLotLine->setInventoryLot(null);
        }

        if ($sourceReceiptLotLine !== null && $sourceReceiptLotLine->getInventoryLot() !== $this) {
            $sourceReceiptLotLine->setInventoryLot($this);
        }

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

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): static
    {
        $this->provider = $provider;
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

    public function getReceivedAt(): ?\DateTimeInterface
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(\DateTimeInterface $receivedAt): static
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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
            'internal_code' => $this->internalCode,
            'source_receipt_lot_line_id' => $this->sourceReceiptLotLine?->getId(),
            'merchandise_id' => $this->merchandise?->getId(),
            'provider_id' => $this->provider?->getId(),
            'supplier_lot_code' => $this->supplierLotCode,
            'manufacture_date' => $this->manufactureDate?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'received_at' => $this->receivedAt?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
