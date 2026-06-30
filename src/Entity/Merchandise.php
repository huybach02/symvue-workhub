<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchandiseRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MerchandiseRepository::class)]
#[ORM\Table(name: 'merchandise')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_MERCHANDISE_CODE',
    fields: ['code'],
    options: ['where' => 'deleted_at IS NULL']
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Merchandise
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Mã hàng hóa'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tên hàng hóa'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?Category $category = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Loại hàng hóa (finished_product, ingredient)'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '% lợi nhuận mặc định'])]
    private ?string $profit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Mô tả'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => 'Số lượng cảnh báo theo base unit'])]
    private ?string $stockAlertQuantity = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Trạng thái (1 active, 0 inactive)'])]
    private ?int $status = 1;

    #[ORM\ManyToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'base_unit_id', referencedColumnName: 'id', nullable: true)]
    private ?Unit $baseUnit = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'Chỉ có 1 đơn vị tính'])]
    private ?bool $isSingleUnit = false;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(?string $profit): static
    {
        $this->profit = $profit;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStockAlertQuantity(): ?string
    {
        return $this->stockAlertQuantity;
    }

    public function setStockAlertQuantity(?string $stockAlertQuantity): static
    {
        $this->stockAlertQuantity = $stockAlertQuantity;
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

    public function getBaseUnit(): ?Unit
    {
        return $this->baseUnit;
    }

    public function setBaseUnit(?Unit $baseUnit): static
    {
        $this->baseUnit = $baseUnit;
        return $this;
    }

    public function isSingleUnit(): ?bool
    {
        return $this->isSingleUnit;
    }

    public function setIsSingleUnit(?bool $isSingleUnit): static
    {
        $this->isSingleUnit = $isSingleUnit;
        return $this;
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'categoryId' => $this->category?->getId(),
            'category' => $this->category?->jsonSerialize(),
            'type' => $this->type,
            'profit' => $this->profit,
            'description' => $this->description,
            'notes' => $this->notes,
            'stockAlertQuantity' => $this->stockAlertQuantity,
            'status' => $this->status,
            'baseUnitId' => $this->baseUnit?->getId(),
            'baseUnit' => $this->baseUnit?->jsonSerialize(),
            'isSingleUnit' => $this->isSingleUnit,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
