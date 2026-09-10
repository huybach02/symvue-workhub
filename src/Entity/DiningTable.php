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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'tableNumber' => $this->tableNumber,
            'qrCode' => $this->qrCode,
            'isUsing' => $this->isUsing,
            'status' => $this->status,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
