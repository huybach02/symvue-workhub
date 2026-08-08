<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionOrderMaterialRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionOrderMaterialRepository::class)]
#[ORM\Table(name: 'production_order_material')]
class ProductionOrderMaterial implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductionOrderItem::class, inversedBy: 'materials')]
    #[ORM\JoinColumn(name: 'production_order_item_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Thành phẩm sử dụng nguyên liệu'])]
    private ?ProductionOrderItem $productionOrderItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'source_recipe_item_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Dòng công thức nguồn'])]
    private ?MerchandiseRecipeItem $sourceRecipeItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'ingredient_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Nguyên liệu'])]
    private ?Merchandise $ingredient = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot mã, tên và base unit nguyên liệu'])]
    private array $ingredientSnapshot = [];

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot định lượng gốc trong công thức'])]
    private array $recipeItemSnapshot = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Lượng nguyên liệu kế hoạch'])]
    private ?string $plannedQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'planned_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đơn vị nguyên liệu kế hoạch'])]
    private ?Unit $plannedUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8, options: ['comment' => 'Hệ số quy đổi về base unit'])]
    private ?string $plannedFactorToBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Lượng nguyên liệu theo base unit'])]
    private ?string $plannedBaseQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'base_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Base unit nguyên liệu'])]
    private ?Unit $baseUnit = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot đơn giá và tiền tệ'])]
    private array $pricingSnapshot = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 4, options: ['default' => '0.0000', 'comment' => 'Tổng cost kế hoạch'])]
    private string $plannedCost = '0.0000';

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 4, nullable: true, options: ['comment' => 'Tổng cost thực tế khi xuất kho'])]
    private ?string $actualCost = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự hiển thị'])]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSourceRecipeItem(): ?MerchandiseRecipeItem
    {
        return $this->sourceRecipeItem;
    }

    public function setSourceRecipeItem(?MerchandiseRecipeItem $sourceRecipeItem): static
    {
        $this->sourceRecipeItem = $sourceRecipeItem;

        return $this;
    }

    public function getIngredient(): ?Merchandise
    {
        return $this->ingredient;
    }

    public function setIngredient(?Merchandise $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getIngredientSnapshot(): array
    {
        return $this->ingredientSnapshot;
    }

    public function setIngredientSnapshot(array $ingredientSnapshot): static
    {
        $this->ingredientSnapshot = $ingredientSnapshot;

        return $this;
    }

    public function getRecipeItemSnapshot(): array
    {
        return $this->recipeItemSnapshot;
    }

    public function setRecipeItemSnapshot(array $recipeItemSnapshot): static
    {
        $this->recipeItemSnapshot = $recipeItemSnapshot;

        return $this;
    }

    public function getPlannedQuantity(): ?string
    {
        return $this->plannedQuantity;
    }

    public function setPlannedQuantity(string $plannedQuantity): static
    {
        $this->plannedQuantity = $plannedQuantity;

        return $this;
    }

    public function getPlannedUnit(): ?Unit
    {
        return $this->plannedUnit;
    }

    public function setPlannedUnit(?Unit $plannedUnit): static
    {
        $this->plannedUnit = $plannedUnit;

        return $this;
    }

    public function getPlannedFactorToBase(): ?string
    {
        return $this->plannedFactorToBase;
    }

    public function setPlannedFactorToBase(string $plannedFactorToBase): static
    {
        $this->plannedFactorToBase = $plannedFactorToBase;

        return $this;
    }

    public function getPlannedBaseQuantity(): ?string
    {
        return $this->plannedBaseQuantity;
    }

    public function setPlannedBaseQuantity(string $plannedBaseQuantity): static
    {
        $this->plannedBaseQuantity = $plannedBaseQuantity;

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

    public function getPricingSnapshot(): array
    {
        return $this->pricingSnapshot;
    }

    public function setPricingSnapshot(array $pricingSnapshot): static
    {
        $this->pricingSnapshot = $pricingSnapshot;

        return $this;
    }

    public function getPlannedCost(): string
    {
        return $this->plannedCost;
    }

    public function setPlannedCost(string $plannedCost): static
    {
        $this->plannedCost = $plannedCost;

        return $this;
    }

    public function getActualCost(): ?string
    {
        return $this->actualCost;
    }

    public function setActualCost(string $actualCost): static
    {
        $this->actualCost = $actualCost;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

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
            'productionOrderItemId' => $this->productionOrderItem?->getId(),
            'sourceRecipeItemId' => $this->sourceRecipeItem?->getId(),
            'ingredientId' => $this->ingredient?->getId(),
            'ingredient' => $this->ingredient?->jsonSerialize(),
            'ingredientSnapshot' => $this->ingredientSnapshot,
            'recipeItemSnapshot' => $this->recipeItemSnapshot,
            'plannedQuantity' => formatDecimal($this->plannedQuantity),
            'plannedUnitId' => $this->plannedUnit?->getId(),
            'plannedUnit' => $this->plannedUnit?->jsonSerialize(),
            'plannedFactorToBase' => formatDecimal($this->plannedFactorToBase),
            'plannedBaseQuantity' => formatDecimal($this->plannedBaseQuantity),
            'baseUnitId' => $this->baseUnit?->getId(),
            'baseUnit' => $this->baseUnit?->jsonSerialize(),
            'pricingSnapshot' => $this->pricingSnapshot,
            'plannedCost' => formatDecimal($this->plannedCost),
            'actualCost' => formatDecimal($this->actualCost),
            'sortOrder' => $this->sortOrder,
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
