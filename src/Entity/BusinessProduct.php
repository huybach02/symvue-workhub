<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BusinessProductRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BusinessProductRepository::class)]
#[ORM\Table(name: 'business_product')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_BUSINESS_PRODUCT_CODE',
    fields: ['code'],
    options: ['where' => 'deleted_at IS NULL']
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class BusinessProduct implements \JsonSerializable
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Mã sản phẩm'])]
    private ?string $code = null;

    #[ORM\Column(length: 255, options: ['comment' => 'Tên sản phẩm'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Mô tả'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú'])]
    private ?string $notes = null;

    #[ORM\Column(name: 'image_url', length: 255, nullable: true, options: ['comment' => 'Đường dẫn ảnh'])]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'target_profit_margin', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => 'Tỷ lệ lợi nhuận mục tiêu (%)'])]
    private ?string $targetProfitMargin = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Trạng thái (1: active, 0: inactive)'])]
    private ?int $status = 1;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Thứ tự sắp xếp'])]
    private ?int $sortOrder = 0;

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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getTargetProfitMargin(): ?string
    {
        return $this->targetProfitMargin;
    }

    public function setTargetProfitMargin(?string $targetProfitMargin): static
    {
        $this->targetProfitMargin = $targetProfitMargin;
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
            'code' => $this->code,
            'name' => $this->name,
            'categoryId' => $this->category?->getId(),
            'category' => $this->category?->jsonSerialize(),
            'description' => $this->description,
            'notes' => $this->notes,
            'imageUrl' => $this->imageUrl,
            'targetProfitMargin' => formatDecimal($this->targetProfitMargin),
            'status' => $this->status,
            'sortOrder' => $this->sortOrder,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
