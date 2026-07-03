<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseUnitRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseUnitRepository::class)]
#[ORM\Table(name: 'merchandise_unit')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class MerchandiseUnit
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Merchandise::class)]
    #[ORM\JoinColumn(name: 'merchandise_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Merchandise $merchandise = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $unit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['comment' => 'Hệ số quy đổi ra base unit (1 đơn vị này = bao nhiêu base unit)'])]
    private ?string $factorToBase = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => 'base = 0, càng lớn càng là đơn vị lớn'])]
    private ?int $level = 0;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'Ví dụ: Thùng 36 chai'])]
    private ?string $label = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'true nếu là base unit'])]
    private ?bool $isBase = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMerchandise(): ?Merchandise
    {
        return $this->merchandise;
    }

    public function setMerchandise(?Merchandise $merchandise): static
    {
        $this->merchandise = $merchandise;
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
            'merchandiseId' => $this->merchandise?->getId(),
            'merchandise' => $this->merchandise?->jsonSerialize(),
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'factorToBase' => formatDecimal($this->factorToBase),
            'level' => $this->level,
            'label' => $this->label,
            'isBase' => $this->isBase,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
