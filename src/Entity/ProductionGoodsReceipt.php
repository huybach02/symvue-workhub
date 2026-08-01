<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionGoodsReceiptRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionGoodsReceiptRepository::class)]
#[ORM\Table(name: 'production_goods_receipt')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCTION_GOODS_RECEIPT_CODE', fields: ['code'])]
class ProductionGoodsReceipt implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => 'Mã phiếu nhập từ sản xuất'])]
    private ?string $code = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'production_item_inspection_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Lần kiểm hàng nguồn'])]
    private ?ProductionItemInspection $productionItemInspection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho nhập thành phẩm'])]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot kho tại thời điểm nhập'])]
    private array $warehouseSnapshot = [];

    #[ORM\Column(length: 30, options: ['default' => 'POSTED', 'comment' => 'Trạng thái phiếu'])]
    private string $status = 'POSTED';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Tổng số lượng nhập theo base unit'])]
    private ?string $totalAcceptedBaseQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 4, options: ['default' => '0.0000', 'comment' => 'Tổng giá trị nhập kho'])]
    private string $totalCost = '0.0000';

    #[ORM\Column(length: 3, options: ['default' => 'VND', 'comment' => 'Tiền tệ'])]
    private string $currency = 'VND';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm nhập kho'])]
    private ?\DateTimeInterface $postedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getProductionItemInspection(): ?ProductionItemInspection
    {
        return $this->productionItemInspection;
    }

    public function setProductionItemInspection(?ProductionItemInspection $productionItemInspection): static
    {
        $this->productionItemInspection = $productionItemInspection;

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

    public function getWarehouseSnapshot(): array
    {
        return $this->warehouseSnapshot;
    }

    public function setWarehouseSnapshot(array $warehouseSnapshot): static
    {
        $this->warehouseSnapshot = $warehouseSnapshot;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalAcceptedBaseQuantity(): ?string
    {
        return $this->totalAcceptedBaseQuantity;
    }

    public function setTotalAcceptedBaseQuantity(string $totalAcceptedBaseQuantity): static
    {
        $this->totalAcceptedBaseQuantity = $totalAcceptedBaseQuantity;

        return $this;
    }

    public function getTotalCost(): string
    {
        return $this->totalCost;
    }

    public function setTotalCost(string $totalCost): static
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

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
            'code' => $this->code,
            'productionItemInspectionId' => $this->productionItemInspection?->getId(),
            'productionItemInspection' => $this->productionItemInspection?->jsonSerialize(),
            'warehouseId' => $this->warehouse?->getId(),
            'warehouse' => $this->warehouse?->jsonSerialize(),
            'warehouseSnapshot' => $this->warehouseSnapshot,
            'status' => $this->status,
            'totalAcceptedBaseQuantity' => $this->totalAcceptedBaseQuantity,
            'totalCost' => $this->totalCost,
            'currency' => $this->currency,
            'postedAt' => $this->postedAt?->format('Y-m-d H:i:s'),
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
