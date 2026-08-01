<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionEventRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionEventRepository::class)]
#[ORM\Table(name: 'production_event')]
class ProductionEvent implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductionOrder::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'production_order_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Lệnh sản xuất liên quan'])]
    private ?ProductionOrder $productionOrder = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'production_order_item_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Thành phẩm liên quan'])]
    private ?ProductionOrderItem $productionOrderItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'production_item_inspection_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Lần kiểm hàng liên quan'])]
    private ?ProductionItemInspection $productionItemInspection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'production_goods_receipt_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Phiếu nhập kho liên quan'])]
    private ?ProductionGoodsReceipt $productionGoodsReceipt = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Loại sự kiện'])]
    private ?string $eventType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, options: ['comment' => 'Người thực hiện; null nếu hệ thống'])]
    private ?User $actor = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái trước'])]
    private ?string $fromStatus = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái sau'])]
    private ?string $toStatus = null;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => 'Nội dung mô tả'])]
    private ?string $message = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Metadata bổ sung'])]
    private ?array $meta = null;

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

    public function getProductionOrderItem(): ?ProductionOrderItem
    {
        return $this->productionOrderItem;
    }

    public function setProductionOrderItem(?ProductionOrderItem $productionOrderItem): static
    {
        $this->productionOrderItem = $productionOrderItem;

        return $this;
    }

    public function getProductionItemInspection(): ?ProductionItemInspection
    {
        return $this->productionItemInspection;
    }

    public function setProductionItemInspection(?ProductionItemInspection $productionItemInspection): static
    {
        $this->productionItemInspection = $productionItemInspection;

        return $this;
    }

    public function getProductionGoodsReceipt(): ?ProductionGoodsReceipt
    {
        return $this->productionGoodsReceipt;
    }

    public function setProductionGoodsReceipt(?ProductionGoodsReceipt $productionGoodsReceipt): static
    {
        $this->productionGoodsReceipt = $productionGoodsReceipt;

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

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;

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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

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
            'productionOrderId' => $this->productionOrder?->getId(),
            'productionOrderItemId' => $this->productionOrderItem?->getId(),
            'productionItemInspectionId' => $this->productionItemInspection?->getId(),
            'productionGoodsReceiptId' => $this->productionGoodsReceipt?->getId(),
            'eventType' => $this->eventType,
            'actorId' => $this->actor?->getId(),
            'actor' => $this->actor?->jsonSerialize(),
            'fromStatus' => $this->fromStatus,
            'toStatus' => $this->toStatus,
            'message' => $this->message,
            'meta' => $this->meta,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
