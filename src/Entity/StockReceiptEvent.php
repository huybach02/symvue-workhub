<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockReceiptEventRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockReceiptEventRepository::class)]
#[ORM\Table(name: 'stock_receipt_event')]
class StockReceiptEvent
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StockReceipt::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'receipt_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Phiếu nhập kho liên quan'])]
    private ?StockReceipt $receipt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'receipt_provider_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Provider process liên quan; null nếu là sự kiện cấp phiếu tổng'])]
    private ?StockReceiptProvider $receiptProvider = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Loại sự kiện: CREATED, STATUS_CHANGED, INSPECTION_STARTED, INSPECTION_SAVED, INVENTORY_POSTED, SHORTAGE_ACCEPTED, BACKORDER_CREATED, CANCELLED, REVERSED'])]
    private ?string $eventType = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái trước thao tác'])]
    private ?string $fromStatus = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái sau thao tác'])]
    private ?string $toStatus = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Người thực hiện'])]
    private ?User $actor = null;

    #[ORM\Column(length: 20, options: ['comment' => 'Nguồn thao tác: USER, SYSTEM, JOB'])]
    private ?string $actorType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú hoặc lý do thao tác'])]
    private ?string $comment = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Metadata như số lượng thiếu, ID phiếu bổ sung'])]
    private ?array $meta = null;

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

    public function getReceiptProvider(): ?StockReceiptProvider
    {
        return $this->receiptProvider;
    }

    public function setReceiptProvider(?StockReceiptProvider $receiptProvider): static
    {
        $this->receiptProvider = $receiptProvider;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getFromStatus(): ?string
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?string $fromStatus): static
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): ?string
    {
        return $this->toStatus;
    }

    public function setToStatus(?string $toStatus): static
    {
        $this->toStatus = $toStatus;
        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;
        return $this;
    }

    public function getActorType(): ?string
    {
        return $this->actorType;
    }

    public function setActorType(string $actorType): static
    {
        $this->actorType = $actorType;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): static
    {
        $this->meta = $meta;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'receipt_id' => $this->receipt?->getId(),
            'receipt_provider_id' => $this->receiptProvider?->getId(),
            'event_type' => $this->eventType,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'actor_id' => $this->actor?->getId(),
            'actor' => $this->actor ? [
                'id' => $this->actor->getId(),
                'name' => $this->actor->getName(),
                'email' => $this->actor->getEmail(),
            ] : null,
            'actor_type' => $this->actorType,
            'comment' => $this->comment,
            'meta' => $this->meta,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
