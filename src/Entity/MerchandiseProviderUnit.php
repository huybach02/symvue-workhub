<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseProviderUnitRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MerchandiseProviderUnitRepository::class)]
#[ORM\Table(name: 'merchandise_provider_unit')]
class MerchandiseProviderUnit
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MerchandiseProvider::class)]
    #[ORM\JoinColumn(name: 'merchandise_provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MerchandiseProvider $merchandiseProvider = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $unit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Hệ số quy đổi ra base unit'])]
    private ?string $factorToBase = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Cấp độ đơn vị'])]
    private ?int $level = 0;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'Ví dụ: Thùng 16 chai, Lốc 4 chai'])]
    private ?string $label = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'Có phải đơn vị gốc không'])]
    private ?bool $isBase = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMerchandiseProvider(): ?MerchandiseProvider
    {
        return $this->merchandiseProvider;
    }

    public function setMerchandiseProvider(?MerchandiseProvider $merchandiseProvider): static
    {
        $this->merchandiseProvider = $merchandiseProvider;
        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getFactorToBase(): ?string
    {
        return $this->factorToBase;
    }

    public function setFactorToBase(?string $factorToBase): static
    {
        $this->factorToBase = $factorToBase;
        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function isBase(): ?bool
    {
        return $this->isBase;
    }

    public function setIsBase(?bool $isBase): static
    {
        $this->isBase = $isBase;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'merchandiseProviderId' => $this->merchandiseProvider?->getId(),
            'merchandiseProvider' => $this->merchandiseProvider?->jsonSerialize(),
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'factorToBase' => formatDecimal($this->factorToBase),
            'level' => $this->level,
            'label' => $this->label,
            'isBase' => $this->isBase,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
