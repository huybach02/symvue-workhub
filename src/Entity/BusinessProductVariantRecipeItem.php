<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BusinessProductVariantRecipeItemRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BusinessProductVariantRecipeItemRepository::class)]
#[ORM\Table(name: 'business_product_variant_recipe_item')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class BusinessProductVariantRecipeItem implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BusinessProductVariantRecipe::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?BusinessProductVariantRecipe $recipe = null;

    #[ORM\ManyToOne(targetEntity: Merchandise::class)]
    #[ORM\JoinColumn(name: 'finished_product_id', referencedColumnName: 'id', nullable: false)]
    private ?Merchandise $finishedProduct = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Số lượng thành phẩm / nguyên liệu sử dụng'])]
    private ?string $quantity = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $unit = null;

    #[ORM\Column(name: 'factor_to_base_snapshot', type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Hệ số quy đổi sang đơn vị chuẩn tại thời điểm lưu'])]
    private ?string $factorToBaseSnapshot = null;

    #[ORM\Column(name: 'base_quantity_snapshot', type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Số lượng chuẩn tại thời điểm lưu'])]
    private ?string $baseQuantitySnapshot = null;

    #[ORM\Column(name: 'unit_cost_snapshot', type: Types::DECIMAL, precision: 15, scale: 4, nullable: true, options: ['comment' => 'Giá vốn đơn vị tại thời điểm lưu'])]
    private ?string $unitCostSnapshot = null;

    #[ORM\Column(name: 'line_cost_snapshot', type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['comment' => 'Giá vốn dòng tại thời điểm lưu'])]
    private ?string $lineCostSnapshot = null;

    #[ORM\Column(name: 'waste_rate', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00', 'comment' => 'Tỷ lệ hao hụt (%)'])]
    private ?string $wasteRate = '0.00';

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự sắp xếp'])]
    private ?int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipe(): ?BusinessProductVariantRecipe
    {
        return $this->recipe;
    }

    public function setRecipe(?BusinessProductVariantRecipe $recipe): static
    {
        $this->recipe = $recipe;
        return $this;
    }

    public function getFinishedProduct(): ?Merchandise
    {
        return $this->finishedProduct;
    }

    public function setFinishedProduct(?Merchandise $finishedProduct): static
    {
        $this->finishedProduct = $finishedProduct;
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): static
    {
        $this->quantity = $quantity;
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

    public function getFactorToBaseSnapshot(): ?string
    {
        return $this->factorToBaseSnapshot;
    }

    public function setFactorToBaseSnapshot(?string $factorToBaseSnapshot): static
    {
        $this->factorToBaseSnapshot = $factorToBaseSnapshot;
        return $this;
    }

    public function getBaseQuantitySnapshot(): ?string
    {
        return $this->baseQuantitySnapshot;
    }

    public function setBaseQuantitySnapshot(?string $baseQuantitySnapshot): static
    {
        $this->baseQuantitySnapshot = $baseQuantitySnapshot;
        return $this;
    }

    public function getUnitCostSnapshot(): ?string
    {
        return $this->unitCostSnapshot;
    }

    public function setUnitCostSnapshot(?string $unitCostSnapshot): static
    {
        $this->unitCostSnapshot = $unitCostSnapshot;
        return $this;
    }

    public function getLineCostSnapshot(): ?string
    {
        return $this->lineCostSnapshot;
    }

    public function setLineCostSnapshot(?string $lineCostSnapshot): static
    {
        $this->lineCostSnapshot = $lineCostSnapshot;
        return $this;
    }

    public function getWasteRate(): ?string
    {
        return $this->wasteRate;
    }

    public function setWasteRate(?string $wasteRate): static
    {
        $this->wasteRate = $wasteRate;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'recipeId' => $this->recipe?->getId(),
            'finishedProductId' => $this->finishedProduct?->getId(),
            'finishedProduct' => $this->finishedProduct?->jsonSerialize(),
            'quantity' => formatDecimal($this->quantity),
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'factorToBaseSnapshot' => formatDecimal($this->factorToBaseSnapshot),
            'baseQuantitySnapshot' => formatDecimal($this->baseQuantitySnapshot),
            'unitCostSnapshot' => formatDecimal($this->unitCostSnapshot),
            'lineCostSnapshot' => formatDecimal($this->lineCostSnapshot),
            'wasteRate' => formatDecimal($this->wasteRate),
            'sortOrder' => $this->sortOrder,
            'notes' => $this->notes,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
