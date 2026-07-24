<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockReceiptProviderRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockReceiptProviderRepository::class)]
#[ORM\Table(name: 'stock_receipt_provider')]
class StockReceiptProvider
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StockReceipt::class, inversedBy: 'providers')]
    #[ORM\JoinColumn(name: 'receipt_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Phiếu nhập kho tổng'])]
    private ?StockReceipt $receipt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'source_receipt_provider_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Provider nguồn nếu đây là phiếu bổ sung'])]
    private ?self $sourceReceiptProvider = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Nhà cung cấp'])]
    private ?Provider $provider = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Trạng thái vận chuyển/kiểm hàng của riêng nhà cung cấp: CREATED, AWAITING_SHIPMENT, IN_TRANSIT, ARRIVED, INSPECTING, COMPLETED, CANCELLED'])]
    private ?string $status = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Kết quả nhập đủ, chấp nhận thiếu hoặc tạo bổ sung: PENDING, FULL, PARTIAL_CLOSED, BACKORDER_CREATED'])]
    private ?string $fulfillmentStatus = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái giải quyết backorder của provider: NONE, OPEN, RESOLVED_FULL, RESOLVED_PARTIAL, CANCELLED'])]
    private ?string $backorderResolutionStatus = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Cách xử lý khi có thiếu hàng: ACCEPT_SHORTAGE, CREATE_BACKORDER'])]
    private ?string $shortageResolution = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Snapshot code, name, phone, address, tax number của nhà cung cấp'])]
    private array $providerSnapshot = [];

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => 'Mã phiếu giao hàng hoặc vận đơn của nhà cung cấp'])]
    private ?string $supplierDeliveryNo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời gian dự kiến giao'])]
    private ?\DateTimeInterface $expectedDeliveryAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm bắt đầu vận chuyển'])]
    private ?\DateTimeInterface $shippedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm hàng thực tế đến kho'])]
    private ?\DateTimeInterface $arrivedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm bắt đầu kiểm hàng'])]
    private ?\DateTimeInterface $inspectionStartedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm hoàn tất nhập dữ liệu kiểm hàng'])]
    private ?\DateTimeInterface $inspectedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm đã tạo lot, movement và cộng tồn kho'])]
    private ?\DateTimeInterface $postedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'inspected_by', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Người kiểm hàng'])]
    private ?User $inspectedBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'posted_by', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Người xác nhận cộng kho'])]
    private ?User $postedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú riêng của nhà cung cấp'])]
    private ?string $note = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Optimistic lock, chống hai người đồng thời xử lý'])]
    #[ORM\Version]
    private ?int $version = null;

    /**
     * @var Collection<int, StockReceiptItem>
     */
    #[ORM\OneToMany(targetEntity: StockReceiptItem::class, mappedBy: 'receiptProvider', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceipt(): ?StockReceipt
    {
        return $this->receipt;
    }

    public function setReceipt(?StockReceipt $receipt): static
    {
        $this->receipt = $receipt;
        return $this;
    }

    public function getSourceReceiptProvider(): ?self
    {
        return $this->sourceReceiptProvider;
    }

    public function setSourceReceiptProvider(?self $sourceReceiptProvider): static
    {
        $this->sourceReceiptProvider = $sourceReceiptProvider;
        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): static
    {
        $this->provider = $provider;
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

    public function getBackorderResolutionStatus(): ?string
    {
        return $this->backorderResolutionStatus;
    }

    public function setBackorderResolutionStatus(?string $backorderResolutionStatus): static
    {
        $this->backorderResolutionStatus = $backorderResolutionStatus;
        return $this;
    }

    public function getShortageResolution(): ?string
    {
        return $this->shortageResolution;
    }

    public function setShortageResolution(?string $shortageResolution): static
    {
        $this->shortageResolution = $shortageResolution;
        return $this;
    }

    public function getProviderSnapshot(): array
    {
        return $this->providerSnapshot;
    }

    public function setProviderSnapshot(array $providerSnapshot): static
    {
        $this->providerSnapshot = $providerSnapshot;
        return $this;
    }

    public function getSupplierDeliveryNo(): ?string
    {
        return $this->supplierDeliveryNo;
    }

    public function setSupplierDeliveryNo(?string $supplierDeliveryNo): static
    {
        $this->supplierDeliveryNo = $supplierDeliveryNo;
        return $this;
    }

    public function getExpectedDeliveryAt(): ?\DateTimeInterface
    {
        return $this->expectedDeliveryAt;
    }

    public function setExpectedDeliveryAt(?\DateTimeInterface $expectedDeliveryAt): static
    {
        $this->expectedDeliveryAt = $expectedDeliveryAt;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeInterface
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeInterface $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function getArrivedAt(): ?\DateTimeInterface
    {
        return $this->arrivedAt;
    }

    public function setArrivedAt(?\DateTimeInterface $arrivedAt): static
    {
        $this->arrivedAt = $arrivedAt;
        return $this;
    }

    public function getInspectionStartedAt(): ?\DateTimeInterface
    {
        return $this->inspectionStartedAt;
    }

    public function setInspectionStartedAt(?\DateTimeInterface $inspectionStartedAt): static
    {
        $this->inspectionStartedAt = $inspectionStartedAt;
        return $this;
    }

    public function getInspectedAt(): ?\DateTimeInterface
    {
        return $this->inspectedAt;
    }

    public function setInspectedAt(?\DateTimeInterface $inspectedAt): static
    {
        $this->inspectedAt = $inspectedAt;
        return $this;
    }

    public function getPostedAt(): ?\DateTimeInterface
    {
        return $this->postedAt;
    }

    public function setPostedAt(?\DateTimeInterface $postedAt): static
    {
        $this->postedAt = $postedAt;
        return $this;
    }

    public function getInspectedBy(): ?User
    {
        return $this->inspectedBy;
    }

    public function setInspectedBy(?User $inspectedBy): static
    {
        $this->inspectedBy = $inspectedBy;
        return $this;
    }

    public function getPostedBy(): ?User
    {
        return $this->postedBy;
    }

    public function setPostedBy(?User $postedBy): static
    {
        $this->postedBy = $postedBy;
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

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return Collection<int, StockReceiptItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(StockReceiptItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setReceiptProvider($this);
        }
        return $this;
    }

    public function removeItem(StockReceiptItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getReceiptProvider() === $this) {
                $item->setReceiptProvider(null);
            }
        }
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'receiptId' => $this->receipt?->getId(),
            'providerId' => $this->provider?->getId(),
            'status' => $this->status,
            'fulfillmentStatus' => $this->fulfillmentStatus,
            'shortageResolution' => $this->shortageResolution,
            'providerSnapshot' => $this->providerSnapshot,
            'supplierDeliveryNo' => $this->supplierDeliveryNo,
            'expectedDeliveryAt' => $this->expectedDeliveryAt?->format('Y-m-d H:i:s'),
            'shippedAt' => $this->shippedAt?->format('Y-m-d H:i:s'),
            'arrivedAt' => $this->arrivedAt?->format('Y-m-d H:i:s'),
            'inspectionStartedAt' => $this->inspectionStartedAt?->format('Y-m-d H:i:s'),
            'inspectedAt' => $this->inspectedAt?->format('Y-m-d H:i:s'),
            'postedAt' => $this->postedAt?->format('Y-m-d H:i:s'),
            'inspectedBy' => $this->inspectedBy?->getId(),
            'postedBy' => $this->postedBy?->getId(),
            'note' => $this->note,
            'version' => $this->version,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'items' => array_map(fn(StockReceiptItem $item) => $item->jsonSerialize(), $this->items->toArray()),
        ];
    }
}
