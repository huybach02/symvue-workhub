<?php

namespace App\Entity;

use App\Repository\WarehouseRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WarehouseRepository::class)]
#[ORM\Table(name: "warehouse")]
#[
    ORM\UniqueConstraint(
        name: "UNIQ_WAREHOUSE_CODE",
        fields: ["code"],
        options: ["where" => "deleted_at IS NULL"],
    ),
]
#[
    Gedmo\SoftDeleteable(
        fieldName: "deletedAt",
        timeAware: false,
        hardDelete: true,
    ),
]
class Warehouse
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: "warehouse", cascade: ["persist", "remove"])]
    private ?Branch $branch = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[
        ORM\Column(
            type: Types::BOOLEAN,
            nullable: true,
            options: ["default" => true],
        ),
    ]
    private ?bool $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): static
    {
        $this->status = $status;

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
            "id" => $this->id,
            "code" => $this->code,
            "name" => $this->name,
            "type" => $this->type,
            "status" => $this->status,
            "note" => $this->note,
            "branch_id" => $this->branch?->getId(),
            "branch" => $this->branch
                ? [
                    "id" => $this->branch->getId(),
                    "code" => $this->branch->getCode(),
                    "name" => $this->branch->getName(),
                ]
                : null,
            "created_at" => $this->createdAt->format("Y-m-d H:i:s"),
            "updated_at" => $this->updatedAt->format("Y-m-d H:i:s"),
        ];
    }
}
