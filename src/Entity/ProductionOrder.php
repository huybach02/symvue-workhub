<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionOrderRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionOrderRepository::class)]
#[ORM\Table(name: 'production_order')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCTION_ORDER_CODE', fields: ['code'])]
class ProductionOrder implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => 'Mã lệnh sản xuất'])]
    private ?string $code = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đề xuất sản xuất nguồn'])]
    private ?Request $request = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tiêu đề lệnh sản xuất'])]
    private ?string $title = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'material_warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho xuất nguyên liệu'])]
    private ?Warehouse $materialWarehouse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'finished_goods_warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho nhập thành phẩm'])]
    private ?Warehouse $finishedGoodsWarehouse = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot hai kho tại thời điểm tạo lệnh'])]
    private array $warehouseSnapshot = [];

    #[ORM\Column(length: 30, options: ['default' => 'CREATED', 'comment' => 'Trạng thái tổng của lệnh'])]
    private string $status = 'CREATED';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm bắt đầu'])]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm hoàn thành'])]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Optimistic locking'])]
    #[ORM\Version]
    private int $version = 1;

    /**
     * @var Collection<int, ProductionOrderItem>
     */
    #[ORM\OneToMany(targetEntity: ProductionOrderItem::class, mappedBy: 'productionOrder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $items;

    /**
     * @var Collection<int, ProductionEvent>
     */
    #[ORM\OneToMany(targetEntity: ProductionEvent::class, mappedBy: 'productionOrder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $events;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

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

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMaterialWarehouse(): ?Warehouse
    {
        return $this->materialWarehouse;
    }

    public function setMaterialWarehouse(?Warehouse $materialWarehouse): static
    {
        $this->materialWarehouse = $materialWarehouse;

        return $this;
    }

    public function getFinishedGoodsWarehouse(): ?Warehouse
    {
        return $this->finishedGoodsWarehouse;
    }

    public function setFinishedGoodsWarehouse(?Warehouse $finishedGoodsWarehouse): static
    {
        $this->finishedGoodsWarehouse = $finishedGoodsWarehouse;

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
     * @return Collection<int, ProductionOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ProductionOrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setProductionOrder($this);
        }

        return $this;
    }

    public function removeItem(ProductionOrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getProductionOrder() === $this) {
                $item->setProductionOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(ProductionEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setProductionOrder($this);
        }

        return $this;
    }

    public function removeEvent(ProductionEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getProductionOrder() === $this) {
                $event->setProductionOrder(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'requestId' => $this->request?->getId(),
            'request' => $this->request?->jsonSerialize(),
            'title' => $this->title,
            'materialWarehouseId' => $this->materialWarehouse?->getId(),
            'materialWarehouse' => $this->materialWarehouse?->jsonSerialize(),
            'finishedGoodsWarehouseId' => $this->finishedGoodsWarehouse?->getId(),
            'finishedGoodsWarehouse' => $this->finishedGoodsWarehouse?->jsonSerialize(),
            'warehouseSnapshot' => $this->warehouseSnapshot,
            'status' => $this->status,
            'startedAt' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'note' => $this->note,
            'version' => $this->version,
            'items' => array_map(fn(ProductionOrderItem $item) => $item->jsonSerialize(), $this->items->toArray()),
            'events' => array_map(fn(ProductionEvent $e) => $e->jsonSerialize(), array_reverse($this->events->toArray())),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
