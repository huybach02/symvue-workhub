<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseRecipeRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseRecipeRepository::class)]
#[ORM\Table(name: 'merchandise_recipe')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class MerchandiseRecipe
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Merchandise::class)]
    #[ORM\JoinColumn(name: 'finished_product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Merchandise $finishedProduct = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['default' => '1.0000', 'comment' => 'Định lượng thành phẩm đầu ra'])]
    private ?string $outputQuantity = '1.0000';

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'output_unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $outputUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => 'Factor quy đổi của đơn vị thành phẩm về base unit tại thời điểm lưu'])]
    private ?string $outputFactorToBaseSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => 'Số lượng thành phẩm quy đổi về base unit tại thời điểm lưu'])]
    private ?string $outputBaseQuantitySnapshot = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Version công thức'])]
    private ?int $version = 1;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => 'Trạng thái hoạt động'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Status'])]
    private ?int $status = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'recipe', targetEntity: MerchandiseRecipeItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOutputQuantity(): ?string
    {
        return $this->outputQuantity;
    }

    public function setOutputQuantity(?string $outputQuantity): static
    {
        $this->outputQuantity = $outputQuantity;
        return $this;
    }

    public function getOutputUnit(): ?Unit
    {
        return $this->outputUnit;
    }

    public function setOutputUnit(?Unit $outputUnit): static
    {
        $this->outputUnit = $outputUnit;
        return $this;
    }

    public function getOutputFactorToBaseSnapshot(): ?string
    {
        return $this->outputFactorToBaseSnapshot;
    }

    public function setOutputFactorToBaseSnapshot(?string $outputFactorToBaseSnapshot): static
    {
        $this->outputFactorToBaseSnapshot = $outputFactorToBaseSnapshot;
        return $this;
    }

    public function getOutputBaseQuantitySnapshot(): ?string
    {
        return $this->outputBaseQuantitySnapshot;
    }

    public function setOutputBaseQuantitySnapshot(?string $outputBaseQuantitySnapshot): static
    {
        $this->outputBaseQuantitySnapshot = $outputBaseQuantitySnapshot;
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
     * @return Collection<int, MerchandiseRecipeItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(MerchandiseRecipeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setRecipe($this);
        }
        return $this;
    }

    public function removeItem(MerchandiseRecipeItem $item): static
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
            'finishedProductId' => $this->finishedProduct?->getId(),
            'outputQuantity' => formatDecimal($this->outputQuantity),
            'outputUnitId' => $this->outputUnit?->getId(),
            'outputUnit' => $this->outputUnit?->jsonSerialize(),
            'outputFactorToBaseSnapshot' => formatDecimal($this->outputFactorToBaseSnapshot),
            'outputBaseQuantitySnapshot' => formatDecimal($this->outputBaseQuantitySnapshot),
            'version' => $this->version,
            'isActive' => $this->isActive,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => array_map(fn(MerchandiseRecipeItem $item) => $item->jsonSerialize(), $this->items->toArray()),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
