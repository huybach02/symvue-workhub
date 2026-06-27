<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseProviderUnitConversionRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseProviderUnitConversionRepository::class)]
#[ORM\Table(name: 'merchandise_provider_unit_conversion')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class MerchandiseProviderUnitConversion
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MerchandiseProvider::class)]
    #[ORM\JoinColumn(name: 'merchandise_provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MerchandiseProvider $merchandiseProvider = null;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'from_unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $fromUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '1.00', 'comment' => 'Giá trị quy đổi từ (thường là 1)'])]
    private ?string $fromValue = '1.00';

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'to_unit_id', referencedColumnName: 'id', nullable: false)]
    private ?Unit $toUnit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => 'Giá trị quy đổi sang'])]
    private ?string $toValue = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => 'Thứ tự sắp xếp'])]
    private ?int $sortOrder = 0;

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

    public function getFromUnit(): ?Unit
    {
        return $this->fromUnit;
    }

    public function setFromUnit(?Unit $fromUnit): static
    {
        $this->fromUnit = $fromUnit;
        return $this;
    }

    public function getFromValue(): ?string
    {
        return $this->fromValue;
    }

    public function setFromValue(?string $fromValue): static
    {
        $this->fromValue = $fromValue;
        return $this;
    }

    public function getToUnit(): ?Unit
    {
        return $this->toUnit;
    }

    public function setToUnit(?Unit $toUnit): static
    {
        $this->toUnit = $toUnit;
        return $this;
    }

    public function getToValue(): ?string
    {
        return $this->toValue;
    }

    public function setToValue(?string $toValue): static
    {
        $this->toValue = $toValue;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'merchandiseProviderId' => $this->merchandiseProvider?->getId(),
            'merchandiseProvider' => $this->merchandiseProvider?->jsonSerialize(),
            'fromUnitId' => $this->fromUnit?->getId(),
            'fromUnit' => $this->fromUnit?->jsonSerialize(),
            'fromValue' => $this->fromValue,
            'toUnitId' => $this->toUnit?->getId(),
            'toUnit' => $this->toUnit?->jsonSerialize(),
            'toValue' => $this->toValue,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
