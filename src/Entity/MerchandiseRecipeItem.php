<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseRecipeItemRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseRecipeItemRepository::class)]
#[ORM\Table(name: 'merchandise_recipe_item')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class MerchandiseRecipeItem
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MerchandiseRecipe::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MerchandiseRecipe $recipe = null;

    #[ORM\ManyToOne(targetEntity: Merchandise::class)]
    #[ORM\JoinColumn(name: 'ingredient_id', referencedColumnName: 'id', nullable: false)]
    private ?Merchandise $ingredient = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Định lượng'])]
    private ?string $quantity = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $unit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => 'Tỉ lệ quy đổi về base unit của nguyên liệu tại thời điểm lưu'])]
    private ?string $factorToBaseSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => 'Số lượng quy đổi ra base unit của nguyên liệu tại thời điểm lưu'])]
    private ?string $baseQuantitySnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00', 'comment' => 'Tỉ lệ hao hụt %'])]
    private ?string $wasteRate = '0.00';

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự sắp xếp'])]
    private ?int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipe(): ?MerchandiseRecipe
    {
        return $this->recipe;
    }

    public function setRecipe(?MerchandiseRecipe $recipe): static
    {
        $this->recipe = $recipe;
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
            'ingredientId' => $this->ingredient?->getId(),
            'ingredient' => $this->ingredient?->jsonSerialize(),
            'quantity' => formatDecimal($this->quantity),
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'factorToBaseSnapshot' => formatDecimal($this->factorToBaseSnapshot),
            'baseQuantitySnapshot' => formatDecimal($this->baseQuantitySnapshot),
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
