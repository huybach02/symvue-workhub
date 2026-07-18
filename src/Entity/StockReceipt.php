<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockReceiptRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockReceiptRepository::class)]
#[ORM\Table(name: 'stock_receipt')]
#[ORM\UniqueConstraint(name: 'UNIQ_STOCK_RECEIPT_CODE', fields: ['code'])]
class StockReceipt
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => 'Mã phiếu, ví dụ PN-202607-000001'])]
    private ?string $code = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Đề xuất nhập kho nguồn'])]
    private ?Request $request = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'warehouse_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Kho sẽ nhận hàng'])]
    private ?Warehouse $warehouse = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_receipt_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Phiếu gốc nếu đây là phiếu nhập bổ sung'])]
    private ?self $parentReceipt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự bổ sung, 0 là phiếu gốc, 1 là bổ sung lần 1'])]
    private int $supplementNo = 0;

    #[ORM\Column(length: 30, options: ['comment' => 'Trạng thái tổng hợp từ các nhà cung cấp: CREATED, IN_PROGRESS, PARTIALLY_COMPLETED, COMPLETED, CANCELLED'])]
    private ?string $status = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Kết quả đáp ứng hàng hóa tổng thể: PENDING, FULL, PARTIAL_CLOSED, BACKORDER_OPEN'])]
    private ?string $fulfillmentStatus = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tiêu đề phiếu; phiếu bổ sung có thể hiển thị “Bổ sung cho PN-...”'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot code, name, branch của warehouse tại thời điểm tạo phiếu'])]
    private array $warehouseSnapshot = [];

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú chung'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm toàn bộ provider process hoàn thành'])]
    private ?\DateTimeInterface $completedAt = null;

    /**
     * @var Collection<int, StockReceiptProvider>
     */
    #[ORM\OneToMany(targetEntity: StockReceiptProvider::class, mappedBy: 'receipt', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $providers;

    /**
     * @var Collection<int, StockReceiptEvent>
     */
    #[ORM\OneToMany(targetEntity: StockReceiptEvent::class, mappedBy: 'receipt', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $events;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
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

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;
        return $this;
    }

    public function getParentReceipt(): ?self
    {
        return $this->parentReceipt;
    }

    public function setParentReceipt(?self $parentReceipt): static
    {
        $this->parentReceipt = $parentReceipt;
        return $this;
    }

    public function getSupplementNo(): int
    {
        return $this->supplementNo;
    }

    public function setSupplementNo(int $supplementNo): static
    {
        $this->supplementNo = $supplementNo;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getFulfillmentStatus(): ?string
    {
        return $this->fulfillmentStatus;
    }

    public function setFulfillmentStatus(string $fulfillmentStatus): static
    {
        $this->fulfillmentStatus = $fulfillmentStatus;
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

    public function getWarehouseSnapshot(): array
    {
        return $this->warehouseSnapshot;
    }

    public function setWarehouseSnapshot(array $warehouseSnapshot): static
    {
        $this->warehouseSnapshot = $warehouseSnapshot;
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

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    /**
     * @return Collection<int, StockReceiptProvider>
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }

    public function addProvider(StockReceiptProvider $provider): static
    {
        if (!$this->providers->contains($provider)) {
            $this->providers->add($provider);
            $provider->setReceipt($this);
        }
        return $this;
    }

    public function removeProvider(StockReceiptProvider $provider): static
    {
        if ($this->providers->removeElement($provider)) {
            if ($provider->getReceipt() === $this) {
                $provider->setReceipt(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StockReceiptEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(StockReceiptEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setReceipt($this);
        }
        return $this;
    }

    public function removeEvent(StockReceiptEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getReceipt() === $this) {
                $event->setReceipt(null);
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
            'requestCode' => $this->request?->getCode(),
            'warehouseId' => $this->warehouse?->getId(),
            'parentReceiptId' => $this->parentReceipt?->getId(),
            'supplementNo' => $this->supplementNo,
            'status' => $this->status,
            'fulfillmentStatus' => $this->fulfillmentStatus,
            'title' => $this->title,
            'warehouseSnapshot' => $this->warehouseSnapshot,
            'note' => $this->note,
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'providers' => array_map(fn(StockReceiptProvider $p) => $p->jsonSerialize(), $this->providers->toArray()),
        ];
    }
}
