<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BusinessProductVariantPriceRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BusinessProductVariantPriceRepository::class)]
#[ORM\Table(name: 'business_product_variant_price')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class BusinessProductVariantPrice implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BusinessProductVariant::class)]
    #[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?BusinessProductVariant $variant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['comment' => 'Giá bán'])]
    private ?string $price = null;

    #[ORM\Column(length: 10, options: ['default' => 'VND', 'comment' => 'Loại tiền tệ'])]
    private ?string $currency = 'VND';

    #[ORM\Column(name: 'effective_from', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Ngày bắt đầu hiệu lực'])]
    private ?\DateTimeInterface $effectiveFrom = null;

    #[ORM\Column(name: 'effective_to', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Ngày hết hiệu lực'])]
    private ?\DateTimeInterface $effectiveTo = null;

    #[ORM\Column(name: 'is_current', type: Types::BOOLEAN, options: ['default' => true, 'comment' => 'Giá hiện tại đang áp dụng'])]
    private ?bool $isCurrent = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariant(): ?BusinessProductVariant
    {
        return $this->variant;
    }

    public function setVariant(?BusinessProductVariant $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getEffectiveFrom(): ?\DateTimeInterface
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(?\DateTimeInterface $effectiveFrom): static
    {
        $this->effectiveFrom = $effectiveFrom;
        return $this;
    }

    public function getEffectiveTo(): ?\DateTimeInterface
    {
        return $this->effectiveTo;
    }

    public function setEffectiveTo(?\DateTimeInterface $effectiveTo): static
    {
        $this->effectiveTo = $effectiveTo;
        return $this;
    }

    public function isCurrent(): ?bool
    {
        return $this->isCurrent;
    }

    public function setIsCurrent(?bool $isCurrent): static
    {
        $this->isCurrent = $isCurrent;
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
            'variantId' => $this->variant?->getId(),
            'variant' => $this->variant?->jsonSerialize(),
            'price' => formatDecimal($this->price),
            'currency' => $this->currency,
            'effectiveFrom' => $this->effectiveFrom?->format('Y-m-d H:i:s'),
            'effectiveTo' => $this->effectiveTo?->format('Y-m-d H:i:s'),
            'isCurrent' => $this->isCurrent,
            'note' => $this->note,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
