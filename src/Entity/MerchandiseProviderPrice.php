<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseProviderPriceRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MerchandiseProviderPriceRepository::class)]
#[ORM\Table(name: 'merchandise_provider_price')]
class MerchandiseProviderPrice
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

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'Ví dụ: Thùng 36 chai'])]
    private ?string $unitLabelSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true, options: ['comment' => 'Snapshot hệ số tại thời điểm tạo giá'])]
    private ?string $factorToBaseSnapshot = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['comment' => 'Giá gốc'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['default' => '0.00', 'comment' => '% giảm giá'])]
    private ?string $discountRate = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true, options: ['default' => '0.00', 'comment' => 'Số tiền giảm trực tiếp'])]
    private ?string $discountAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['comment' => 'Giá sau giảm'])]
    private ?string $priceAfterDiscount = null;

    #[ORM\Column(length: 10, options: ['default' => 'VND', 'comment' => 'VND'])]
    private ?string $currency = 'VND';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Ngày bắt đầu'])]
    private ?\DateTimeInterface $effectiveFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Ngày kết thúc'])]
    private ?\DateTimeInterface $effectiveTo = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'Giá mặc định'])]
    private ?bool $isDefault = false;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Trạng thái giá'])]
    private ?int $status = 1;

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

    public function getUnitLabelSnapshot(): ?string
    {
        return $this->unitLabelSnapshot;
    }

    public function setUnitLabelSnapshot(?string $unitLabelSnapshot): static
    {
        $this->unitLabelSnapshot = $unitLabelSnapshot;
        return $this;
    }

    public function getFactorToBaseSnapshot(): ?string
    {
        return $this->factorToBaseSnapshot;
    }

    public function setFactorToBaseSnapshot(?string $factorToBaseSnapshot): static
    {
        $this->factorToBaseSnapshot = $factorToBaseSnapshot;
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

    public function getDiscountRate(): ?string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(?string $discountRate): static
    {
        $this->discountRate = $discountRate;
        return $this;
    }

    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?string $discountAmount): static
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    public function getPriceAfterDiscount(): ?string
    {
        return $this->priceAfterDiscount;
    }

    public function setPriceAfterDiscount(?string $priceAfterDiscount): static
    {
        $this->priceAfterDiscount = $priceAfterDiscount;
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

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): static
    {
        $this->isDefault = $isDefault;
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
            'merchandiseProviderId' => $this->merchandiseProvider?->getId(),
            'merchandiseProvider' => $this->merchandiseProvider?->jsonSerialize(),
            'unitId' => $this->unit?->getId(),
            'unit' => $this->unit?->jsonSerialize(),
            'unitLabelSnapshot' => $this->unitLabelSnapshot,
            'factorToBaseSnapshot' => formatDecimal($this->factorToBaseSnapshot),
            'price' => formatDecimal($this->price),
            'discountRate' => formatDecimal($this->discountRate),
            'discountAmount' => formatDecimal($this->discountAmount),
            'priceAfterDiscount' => formatDecimal($this->priceAfterDiscount),
            'currency' => $this->currency,
            'effectiveFrom' => $this->effectiveFrom?->format('Y-m-d H:i:s'),
            'effectiveTo' => $this->effectiveTo?->format('Y-m-d H:i:s'),
            'isDefault' => $this->isDefault,
            'status' => $this->status,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
