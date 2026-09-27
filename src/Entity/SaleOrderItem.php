<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SaleOrderItemRepository;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleOrderItemRepository::class)]
#[ORM\Table(name: 'sale_order_item')]
class SaleOrderItem implements \JsonSerializable
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính chi tiết đơn bán'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SaleOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'sale_order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?SaleOrder $saleOrder = null;

    #[ORM\ManyToOne(targetEntity: BusinessProduct::class)]
    #[ORM\JoinColumn(name: 'business_product_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BusinessProduct $businessProduct = null;

    #[ORM\ManyToOne(targetEntity: BusinessProductVariant::class)]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BusinessProductVariant $variant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '1.00', 'comment' => 'Số lượng bán'])]
    private string $quantity = '1.00';

    #[ORM\Column(name: 'unit_price', type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Đơn giá bán tại thời điểm tạo đơn'])]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00', 'comment' => 'Thành tiền = quantity * unit_price'])]
    private string $subtotal = '0.00';

    #[ORM\Column(length: 500, nullable: true, options: ['comment' => 'Ghi chú cho món'])]
    private ?string $note = null;

    #[ORM\Column(name: 'product_snapshot', type: Types::JSON, options: ['comment' => 'Toàn bộ dữ liệu snapshot của BusinessProduct'])]
    private array $productSnapshot = [];

    #[ORM\Column(name: 'variant_snapshot', type: Types::JSON, options: ['comment' => 'Toàn bộ dữ liệu snapshot của BusinessProductVariant'])]
    private array $variantSnapshot = [];

    #[ORM\Column(name: 'recipe_snapshot', type: Types::JSON, nullable: true, options: ['comment' => 'Toàn bộ dữ liệu snapshot công thức định lượng của variant tại thời điểm tạo đơn'])]
    private ?array $recipeSnapshot = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSaleOrder(): ?SaleOrder
    {
        return $this->saleOrder;
    }

    public function setSaleOrder(?SaleOrder $saleOrder): static
    {
        $this->saleOrder = $saleOrder;
        return $this;
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

    public function getVariant(): ?BusinessProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?BusinessProductVariant $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
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

    public function getProductSnapshot(): array
    {
        return $this->productSnapshot;
    }

    public function setProductSnapshot(array $productSnapshot): static
    {
        $this->productSnapshot = $productSnapshot;
        return $this;
    }

    public function getVariantSnapshot(): array
    {
        return $this->variantSnapshot;
    }

    public function setVariantSnapshot(array $variantSnapshot): static
    {
        $this->variantSnapshot = $variantSnapshot;
        return $this;
    }

    public function getRecipeSnapshot(): ?array
    {
        return $this->recipeSnapshot;
    }

    public function setRecipeSnapshot(?array $recipeSnapshot): static
    {
        $this->recipeSnapshot = $recipeSnapshot;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'saleOrderId' => $this->saleOrder?->getId(),
            'businessProductId' => $this->businessProduct?->getId(),
            'businessProduct' => $this->businessProduct ? [
                'id' => $this->businessProduct->getId(),
                'code' => $this->businessProduct->getCode(),
                'name' => $this->businessProduct->getName(),
            ] : null,
            'variantId' => $this->variant?->getId(),
            'variant' => $this->variant ? [
                'id' => $this->variant->getId(),
                'code' => $this->variant->getCode(),
                'name' => $this->variant->getName(),
            ] : null,
            'quantity' => formatDecimal($this->quantity),
            'unitPrice' => formatDecimal($this->unitPrice),
            'subtotal' => formatDecimal($this->subtotal),
            'note' => $this->note,
            'productSnapshot' => $this->productSnapshot,
            'variantSnapshot' => $this->variantSnapshot,
            'recipeSnapshot' => $this->recipeSnapshot,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
