<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BusinessProductVariantRecipeRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BusinessProductVariantRecipeRepository::class)]
#[ORM\Table(name: 'business_product_variant_recipe')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class BusinessProductVariantRecipe implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BusinessProductVariant::class)]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?BusinessProductVariant $variant = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Phiên bản công thức'])]
    private ?int $version = 1;

    #[ORM\Column(name: 'total_cost_snapshot', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['comment' => 'Tổng chi phí tại thời điểm lưu'])]
    private ?string $totalCostSnapshot = null;

    #[ORM\Column(name: 'suggested_price_snapshot', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['comment' => 'Giá bán gợi ý tại thời điểm lưu'])]
    private ?string $suggestedPriceSnapshot = null;

    #[ORM\Column(name: 'target_profit_margin_snapshot', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => 'Tỷ lệ lợi nhuận mục tiêu tại thời điểm lưu'])]
    private ?string $targetProfitMarginSnapshot = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true, 'comment' => 'Công thức đang hoạt động'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Trạng thái (1: active, 0: inactive)'])]
    private ?int $status = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: BusinessProductVariantRecipeItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getTotalCostSnapshot(): ?string
    {
        return $this->totalCostSnapshot;
    }

    public function setTotalCostSnapshot(?string $totalCostSnapshot): static
    {
        $this->totalCostSnapshot = $totalCostSnapshot;
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

    public function getTargetProfitMarginSnapshot(): ?string
    {
        return $this->targetProfitMarginSnapshot;
    }

    public function setTargetProfitMarginSnapshot(?string $targetProfitMarginSnapshot): static
    {
        $this->targetProfitMarginSnapshot = $targetProfitMarginSnapshot;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Collection<int, BusinessProductVariantRecipeItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(BusinessProductVariantRecipeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setRecipe($this);
        }
        return $this;
    }

    public function removeItem(BusinessProductVariantRecipeItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getRecipe() === $this) {
                $item->setRecipe(null);
            }
        }
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'variantId' => $this->variant?->getId(),
            'variant' => $this->variant?->jsonSerialize(),
            'version' => $this->version,
            'totalCostSnapshot' => formatDecimal($this->totalCostSnapshot),
            'suggestedPriceSnapshot' => formatDecimal($this->suggestedPriceSnapshot),
            'targetProfitMarginSnapshot' => formatDecimal($this->targetProfitMarginSnapshot),
            'isActive' => $this->isActive,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => array_map(fn(BusinessProductVariantRecipeItem $item) => $item->jsonSerialize(), $this->items->toArray()),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
