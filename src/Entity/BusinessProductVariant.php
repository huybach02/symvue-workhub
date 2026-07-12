<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BusinessProductVariantRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BusinessProductVariantRepository::class)]
#[ORM\Table(name: 'business_product_variant')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_BUSINESS_PRODUCT_VARIANT_CODE',
    fields: ['code'],
    options: ['where' => 'deleted_at IS NULL']
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class BusinessProductVariant implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BusinessProduct::class)]
    #[ORM\JoinColumn(name: 'business_product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?BusinessProduct $businessProduct = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Mã SKU / Mã biến thể'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tên biến thể'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Unit $unit = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'Mã vạch'])]
    private ?string $barcode = null;

    #[ORM\Column(name: 'selling_price', type: Types::DECIMAL, precision: 15, scale: 2, options: ['comment' => 'Giá bán'])]
    private ?string $sellingPrice = null;

    #[ORM\Column(name: 'suggested_price_snapshot', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['comment' => 'Giá gợi ý tại thời điểm lưu'])]
    private ?string $suggestedPriceSnapshot = null;

    #[ORM\Column(name: 'cost_price_snapshot', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['comment' => 'Giá vốn tại thời điểm lưu'])]
    private ?string $costPriceSnapshot = null;

    #[ORM\Column(name: 'target_profit_margin_snapshot', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => 'Tỷ lệ lợi nhuận mục tiêu tại thời điểm lưu'])]
    private ?string $targetProfitMarginSnapshot = null;

    #[ORM\Column(length: 10, options: ['default' => 'VND', 'comment' => 'Loại tiền tệ'])]
    private ?string $currency = 'VND';

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'Là biến thể mặc định'])]
    private ?bool $isDefault = false;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Trạng thái (1: active, 0: inactive)'])]
    private ?int $status = 1;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự sắp xếp'])]
    private ?int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessProduct(): ?BusinessProduct
    {
        return $this->businessProduct;
    }

    public function setBusinessProduct(?BusinessProduct $businessProduct): static
    {
        $this->businessProduct = $businessProduct;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): static
    {
        $this->barcode = $barcode;
        return $this;
    }

    public function getSellingPrice(): ?string
    {
        return $this->sellingPrice;
    }

    public function setSellingPrice(?string $sellingPrice): static
    {
        $this->sellingPrice = $sellingPrice;
        return $this;
    }

    public function getSuggestedPriceSnapshot(): ?string
    {
        return $this->suggestedPriceSnapshot;
    }

    public function setSuggestedPriceSnapshot(?string $suggestedPriceSnapshot): static
    {
        $this->suggestedPriceSnapshot = $suggestedPriceSnapshot;
        return $this;
    }

    public function getCostPriceSnapshot(): ?string
    {
        return $this->costPriceSnapshot;
    }

    public function setCostPriceSnapshot(?string $costPriceSnapshot): static
    {
        $this->costPriceSnapshot = $costPriceSnapshot;
        return $this;
    }

    public function getTargetProfitMarginSnapshot(): ?string
    {
        return $this->targetProfitMarginSnapshot;
    }

    public function setTargetProfitMarginSnapshot(?string $targetProfitMarginSnapshot): static
    {
        $this->targetProfitMarginSnapshot = $targetProfitMarginSnapshot;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'businessProductId' => $this->businessProduct?->getId(),
            'businessProduct' => $this->businessProduct?->jsonSerialize(),
            'code' => $this->code,
            'name' => $this->name,
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'barcode' => $this->barcode,
            'sellingPrice' => formatDecimal($this->sellingPrice),
            'suggestedPriceSnapshot' => formatDecimal($this->suggestedPriceSnapshot),
            'costPriceSnapshot' => formatDecimal($this->costPriceSnapshot),
            'targetProfitMarginSnapshot' => formatDecimal($this->targetProfitMarginSnapshot),
            'currency' => $this->currency,
            'isDefault' => $this->isDefault,
            'status' => $this->status,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
