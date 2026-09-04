<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockTransferRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockTransferRepository::class)]
#[ORM\Table(name: 'stock_transfer')]
#[ORM\UniqueConstraint(name: 'UNIQ_STOCK_TRANSFER_CODE', fields: ['code'])]
#[ORM\UniqueConstraint(name: 'UNIQ_STOCK_TRANSFER_REQUEST', fields: ['request'])]
class StockTransfer implements \JsonSerializable
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true, options: ['comment' => 'Mã phiếu chuyển kho'])]
    private ?string $code = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: false)]
    private ?Request $request = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'source_warehouse_id', referencedColumnName: 'id', nullable: false)]
    private ?Warehouse $sourceWarehouse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'destination_warehouse_id', referencedColumnName: 'id', nullable: false)]
    private ?Warehouse $destinationWarehouse = null;

    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true, 'comment' => 'Snapshot kho nguồn và kho đích'])]
    private array $warehouseSnapshot = [];

    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true, 'comment' => 'Snapshot các dòng hàng đã thực hiện chuyển kho'])]
    private array $itemsSnapshot = [];

    #[ORM\Column(options: ['default' => 1, 'comment' => 'Phiên bản cấu trúc snapshot'])]
    private int $snapshotVersion = 1;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => 'Lý do chuyển kho'])]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 30, options: ['default' => 'COMPLETED'])]
    private string $status = 'COMPLETED';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $executedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'executed_by', referencedColumnName: 'id', nullable: false)]
    private ?User $executedBy = null;

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

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getSourceWarehouse(): ?Warehouse
    {
        return $this->sourceWarehouse;
    }

    public function setSourceWarehouse(Warehouse $sourceWarehouse): static
    {
        $this->sourceWarehouse = $sourceWarehouse;

        return $this;
    }

    public function getDestinationWarehouse(): ?Warehouse
    {
        return $this->destinationWarehouse;
    }

    public function setDestinationWarehouse(Warehouse $destinationWarehouse): static
    {
        $this->destinationWarehouse = $destinationWarehouse;

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

    public function getItemsSnapshot(): array
    {
        return $this->itemsSnapshot;
    }

    public function setItemsSnapshot(array $itemsSnapshot): static
    {
        $this->itemsSnapshot = $itemsSnapshot;

        return $this;
    }

    public function getSnapshotVersion(): int
    {
        return $this->snapshotVersion;
    }

    public function setSnapshotVersion(int $snapshotVersion): static
    {
        $this->snapshotVersion = $snapshotVersion;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExecutedAt(): ?\DateTimeInterface
    {
        return $this->executedAt;
    }

    public function setExecutedAt(\DateTimeInterface $executedAt): static
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    public function getExecutedBy(): ?User
    {
        return $this->executedBy;
    }

    public function setExecutedBy(User $executedBy): static
    {
        $this->executedBy = $executedBy;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'requestId' => $this->request?->getId(),
            'sourceWarehouse' => $this->sourceWarehouse?->jsonSerialize(),
            'destinationWarehouse' => $this->destinationWarehouse?->jsonSerialize(),
            'warehouseSnapshot' => $this->warehouseSnapshot,
            'itemsSnapshot' => $this->itemsSnapshot,
            'snapshotVersion' => $this->snapshotVersion,
            'reason' => $this->reason,
            'note' => $this->note,
            'status' => $this->status,
            'executedAt' => $this->executedAt?->format('Y-m-d H:i:s'),
            'executedBy' => $this->executedBy ? [
                'id' => $this->executedBy->getId(),
                'name' => $this->executedBy->getName(),
            ] : null,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
