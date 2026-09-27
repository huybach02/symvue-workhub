<?php

namespace App\Entity;

use App\Repository\DiningTableRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiningTableRepository::class)]
class DiningTable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $tableNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $isUsing = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\ManyToOne(targetEntity: Branch::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Branch $branch = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableNumber(): ?int
    {
        return $this->tableNumber;
    }

    public function setTableNumber(int $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): static
    {
        $this->qrCode = $qrCode;

        return $this;
    }

    public function isUsing(): ?bool
    {
        return $this->isUsing;
    }

    public function setIsUsing(?bool $isUsing): static
    {
        $this->isUsing = $isUsing;

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

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): static
    {
        $this->branch = $branch;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'tableNumber' => $this->tableNumber,
            'qrCode' => $this->qrCode,
            'isUsing' => $this->isUsing,
            'status' => $this->status,
            'branchId' => $this->branch?->getId(),
            'branch' => $this->branch ? [
                'id' => $this->branch->getId(),
                'name' => $this->branch->getName(),
                'code' => $this->branch->getCode(),
            ] : null,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
