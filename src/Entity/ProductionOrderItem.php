<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionOrderItemRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionOrderItemRepository::class)]
#[ORM\Table(name: 'production_order_item')]
class ProductionOrderItem implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductionOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'production_order_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Lệnh sản xuất cha'])]
    private ?ProductionOrder $productionOrder = null;

    #[ORM\Column(type: Types::GUID, options: ['comment' => 'UUID của card thành phẩm trong đề xuất'])]
    private ?string $sourceRequestLineId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'finished_product_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Thành phẩm cần sản xuất'])]
    private ?Merchandise $finishedProduct = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot mã, tên và base unit thành phẩm'])]
    private array $productSnapshot = [];

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Công thức được áp dụng'])]
    private ?MerchandiseRecipe $recipe = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot version và output công thức'])]
    private array $recipeSnapshot = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng kế hoạch theo đơn vị người dùng'])]
    private ?string $plannedQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'planned_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đơn vị kế hoạch'])]
    private ?Unit $plannedUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8, options: ['comment' => 'Hệ số quy đổi về base unit'])]
    private ?string $plannedFactorToBase = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng kế hoạch theo base unit'])]
    private ?string $plannedBaseQuantity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'base_unit_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Base unit thành phẩm'])]
    private ?Unit $baseUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00', 'comment' => 'Hao hụt dự kiến'])]
    private string $expectedWastePercent = '0.00';

    #[ORM\Column(length: 30, options: ['default' => 'CREATED', 'comment' => 'Trạng thái riêng của thành phẩm'])]
    private string $status = 'CREATED';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Tổng số lượng đã đạt và nhập kho'])]
    private string $acceptedBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['default' => '0.000000', 'comment' => 'Số lượng đã chấp nhận đóng thiếu'])]
    private string $closedShortBaseQuantity = '0.000000';

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Thông tin đóng thiếu'])]
    private ?array $shortageData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm bắt đầu'])]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm hoàn thành'])]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự card'])]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Optimistic locking'])]
    #[ORM\Version]
    private int $version = 1;

    /**
     * @var Collection<int, ProductionOrderMaterial>
     */
    #[ORM\OneToMany(targetEntity: ProductionOrderMaterial::class, mappedBy: 'productionOrderItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $materials;

    /**
     * @var Collection<int, ProductionItemInspection>
     */
    #[ORM\OneToMany(targetEntity: ProductionItemInspection::class, mappedBy: 'productionOrderItem', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sequenceNo' => 'ASC'])]
    private Collection $inspections;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
        $this->inspections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductionOrder(): ?ProductionOrder
    {
        return $this->productionOrder;
    }

    public function setProductionOrder(?ProductionOrder $productionOrder): static
    {
        $this->productionOrder = $productionOrder;

        return $this;
    }

    public function getSourceRequestLineId(): ?string
    {
        return $this->sourceRequestLineId;
    }

    public function setSourceRequestLineId(string $sourceRequestLineId): static
    {
        $this->sourceRequestLineId = $sourceRequestLineId;

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

    public function getProductSnapshot(): array
    {
        return $this->productSnapshot;
    }

    public function setProductSnapshot(array $productSnapshot): static
    {
        $this->productSnapshot = $productSnapshot;

        return $this;
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

    public function getRecipeSnapshot(): array
    {
        return $this->recipeSnapshot;
    }

    public function setRecipeSnapshot(array $recipeSnapshot): static
    {
        $this->recipeSnapshot = $recipeSnapshot;

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

    public function getExpectedWastePercent(): string
    {
        return $this->expectedWastePercent;
    }

    public function setExpectedWastePercent(string $expectedWastePercent): static
    {
        $this->expectedWastePercent = $expectedWastePercent;

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

    public function getAcceptedBaseQuantity(): string
    {
        return $this->acceptedBaseQuantity;
    }

    public function setAcceptedBaseQuantity(string $acceptedBaseQuantity): static
    {
        $this->acceptedBaseQuantity = $acceptedBaseQuantity;

        return $this;
    }

    public function getClosedShortBaseQuantity(): string
    {
        return $this->closedShortBaseQuantity;
    }

    public function setClosedShortBaseQuantity(string $closedShortBaseQuantity): static
    {
        $this->closedShortBaseQuantity = $closedShortBaseQuantity;

        return $this;
    }

    public function getShortageData(): ?array
    {
        return $this->shortageData;
    }

    public function setShortageData(?array $shortageData): static
    {
        $this->shortageData = $shortageData;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return Collection<int, ProductionOrderMaterial>
     */
    public function getMaterials(): Collection
    {
        return $this->materials;
    }

    public function addMaterial(ProductionOrderMaterial $material): static
    {
        if (!$this->materials->contains($material)) {
            $this->materials->add($material);
            $material->setProductionOrderItem($this);
        }

        return $this;
    }

    public function removeMaterial(ProductionOrderMaterial $material): static
    {
        if ($this->materials->removeElement($material)) {
            if ($material->getProductionOrderItem() === $this) {
                $material->setProductionOrderItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionItemInspection>
     */
    public function getInspections(): Collection
    {
        return $this->inspections;
    }

    public function addInspection(ProductionItemInspection $inspection): static
    {
        if (!$this->inspections->contains($inspection)) {
            $this->inspections->add($inspection);
            $inspection->setProductionOrderItem($this);
        }

        return $this;
    }

    public function removeInspection(ProductionItemInspection $inspection): static
    {
        if ($this->inspections->removeElement($inspection)) {
            if ($inspection->getProductionOrderItem() === $this) {
                $inspection->setProductionOrderItem(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'productionOrderId' => $this->productionOrder?->getId(),
            'sourceRequestLineId' => $this->sourceRequestLineId,
            'finishedProductId' => $this->finishedProduct?->getId(),
            'finishedProduct' => $this->finishedProduct?->jsonSerialize(),
            'productSnapshot' => $this->productSnapshot,
            'recipeId' => $this->recipe?->getId(),
            'recipe' => $this->recipe?->jsonSerialize(),
            'recipeSnapshot' => $this->recipeSnapshot,
            'plannedQuantity' => $this->plannedQuantity,
            'plannedUnitId' => $this->plannedUnit?->getId(),
            'plannedUnit' => $this->plannedUnit?->jsonSerialize(),
            'plannedFactorToBase' => $this->plannedFactorToBase,
            'plannedBaseQuantity' => $this->plannedBaseQuantity,
            'baseUnitId' => $this->baseUnit?->getId(),
            'baseUnit' => $this->baseUnit?->jsonSerialize(),
            'expectedWastePercent' => $this->expectedWastePercent,
            'status' => $this->status,
            'acceptedBaseQuantity' => $this->acceptedBaseQuantity,
            'closedShortBaseQuantity' => $this->closedShortBaseQuantity,
            'shortageData' => $this->shortageData,
            'startedAt' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'sortOrder' => $this->sortOrder,
            'note' => $this->note,
            'version' => $this->version,
            'materials' => array_map(fn(ProductionOrderMaterial $m) => $m->jsonSerialize(), $this->materials->toArray()),
            'inspections' => array_map(fn(ProductionItemInspection $inspection) => $inspection->jsonSerialize(), $this->inspections->toArray()),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
