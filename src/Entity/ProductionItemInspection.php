<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductionItemInspectionRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionItemInspectionRepository::class)]
#[ORM\Table(name: 'production_item_inspection')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCTION_ITEM_INSPECTION_CODE', fields: ['code'])]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCTION_ITEM_INSPECTION_REQUEST', fields: ['productionOrderItem', 'clientRequestUuid'])]
class ProductionItemInspection implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'Khóa chính'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => 'Mã lần kiểm hàng'])]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: ProductionOrderItem::class, inversedBy: 'inspections')]
    #[ORM\JoinColumn(name: 'production_order_item_id', referencedColumnName: 'id', nullable: false, options: ['comment' => 'Thành phẩm được kiểm'])]
    private ?ProductionOrderItem $productionOrderItem = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'Lần kiểm thứ bao nhiêu'])]
    private ?int $sequenceNo = null;

    #[ORM\Column(type: Types::GUID, options: ['comment' => 'Idempotency key chống submit trùng'])]
    private ?string $clientRequestUuid = null;

    #[ORM\Column(length: 30, options: ['default' => 'POSTED', 'comment' => 'Trạng thái chứng từ'])]
    private string $status = 'POSTED';

    #[ORM\Column(length: 30, options: ['comment' => 'Kết quả xử lý sau kiểm'])]
    private ?string $resolution = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng còn thiếu trước kiểm'])]
    private ?string $remainingBaseQuantityBefore = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 6, options: ['comment' => 'Số lượng còn thiếu sau kiểm'])]
    private ?string $remainingBaseQuantityAfter = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => 'Danh sách lot và kết quả kiểm hàng'])]
    private array $lots = [];

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Lý do tiếp tục hoặc đóng thiếu'])]
    private ?array $resolutionData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm xác nhận và post kho'])]
    private ?\DateTimeInterface $postedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'Thông tin đảo giao dịch'])]
    private ?array $reversalData = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

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

    public function getProductionOrderItem(): ?ProductionOrderItem
    {
        return $this->productionOrderItem;
    }

    public function setProductionOrderItem(?ProductionOrderItem $productionOrderItem): static
    {
        $this->productionOrderItem = $productionOrderItem;

        return $this;
    }

    public function getSequenceNo(): ?int
    {
        return $this->sequenceNo;
    }

    public function setSequenceNo(int $sequenceNo): static
    {
        $this->sequenceNo = $sequenceNo;

        return $this;
    }

    public function getClientRequestUuid(): ?string
    {
        return $this->clientRequestUuid;
    }

    public function setClientRequestUuid(string $clientRequestUuid): static
    {
        $this->clientRequestUuid = $clientRequestUuid;

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

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(string $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getRemainingBaseQuantityBefore(): ?string
    {
        return $this->remainingBaseQuantityBefore;
    }

    public function setRemainingBaseQuantityBefore(string $remainingBaseQuantityBefore): static
    {
        $this->remainingBaseQuantityBefore = $remainingBaseQuantityBefore;

        return $this;
    }

    public function getRemainingBaseQuantityAfter(): ?string
    {
        return $this->remainingBaseQuantityAfter;
    }

    public function setRemainingBaseQuantityAfter(string $remainingBaseQuantityAfter): static
    {
        $this->remainingBaseQuantityAfter = $remainingBaseQuantityAfter;

        return $this;
    }

    public function getLots(): array
    {
        return $this->lots;
    }

    public function setLots(array $lots): static
    {
        $this->lots = $lots;

        return $this;
    }

    public function getResolutionData(): ?array
    {
        return $this->resolutionData;
    }

    public function setResolutionData(?array $resolutionData): static
    {
        $this->resolutionData = $resolutionData;

        return $this;
    }

    public function getPostedAt(): ?\DateTimeInterface
    {
        return $this->postedAt;
    }

    public function setPostedAt(\DateTimeInterface $postedAt): static
    {
        $this->postedAt = $postedAt;

        return $this;
    }

    public function getReversalData(): ?array
    {
        return $this->reversalData;
    }

    public function setReversalData(?array $reversalData): static
    {
        $this->reversalData = $reversalData;

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
            'code' => $this->code,
            'productionOrderItemId' => $this->productionOrderItem?->getId(),
            'sequenceNo' => $this->sequenceNo,
            'clientRequestUuid' => $this->clientRequestUuid,
            'status' => $this->status,
            'resolution' => $this->resolution,
            'remainingBaseQuantityBefore' => formatDecimal($this->remainingBaseQuantityBefore),
            'remainingBaseQuantityAfter' => formatDecimal($this->remainingBaseQuantityAfter),
            'lots' => $this->lots,
            'resolutionData' => $this->resolutionData,
            'postedAt' => $this->postedAt?->format('Y-m-d H:i:s'),
            'reversalData' => $this->reversalData,
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
