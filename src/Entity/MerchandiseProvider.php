<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseProviderRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseProviderRepository::class)]
#[ORM\Table(name: 'merchandise_provider')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class MerchandiseProvider
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

    #[ORM\ManyToOne(targetEntity: Provider::class)]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Provider $provider = null;

    #[ORM\Column(length: 50, options: ['default' => 'general', 'comment' => 'general hoặc custom'])]
    private ?string $unitConfigMode = 'general';

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'default_purchase_unit_id', referencedColumnName: 'id', nullable: true)]
    private ?Unit $defaultPurchaseUnit = null;

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

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getUnitConfigMode(): ?string
    {
        return $this->unitConfigMode;
    }

    public function setUnitConfigMode(?string $unitConfigMode): static
    {
        $this->unitConfigMode = $unitConfigMode;
        return $this;
    }

    public function getDefaultPurchaseUnit(): ?Unit
    {
        return $this->defaultPurchaseUnit;
    }

    public function setDefaultPurchaseUnit(?Unit $defaultPurchaseUnit): static
    {
        $this->defaultPurchaseUnit = $defaultPurchaseUnit;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'merchandiseId' => $this->merchandise?->getId(),
            'merchandise' => $this->merchandise?->jsonSerialize(),
            'providerId' => $this->provider?->getId(),
            'provider' => $this->provider?->jsonSerialize(),
            'unitConfigMode' => $this->unitConfigMode,
            'defaultPurchaseUnitId' => $this->defaultPurchaseUnit?->getId(),
            'defaultPurchaseUnit' => $this->defaultPurchaseUnit?->jsonSerialize(),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
