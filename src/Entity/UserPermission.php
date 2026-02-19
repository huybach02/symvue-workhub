<?php

namespace App\Entity;

use App\Repository\UserPermissionRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPermissionRepository::class)]
class UserPermission
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?int $boPhanId = null;

    #[ORM\Column(nullable: true)]
    private ?array $phanQuyen = null;

    #[ORM\Column(nullable: true, options: ["default" => false])]
    private ?bool $isDefault = false;

    #[ORM\Column(nullable: true, options: ["default" => false])]
    private ?bool $isManager = false;

    #[ORM\Column(nullable: true, options: ["default" => false])]
    private ?bool $isCustom = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getBoPhanId(): ?int
    {
        return $this->boPhanId;
    }

    public function setBoPhanId(int $boPhanId): static
    {
        $this->boPhanId = $boPhanId;

        return $this;
    }

    public function getPhanQuyen(): ?array
    {
        return $this->phanQuyen;
    }

    public function setPhanQuyen(?array $phanQuyen): static
    {
        $this->phanQuyen = $phanQuyen;

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

    public function isManager(): ?bool
    {
        return $this->isManager;
    }

    public function setIsManager(?bool $isManager): static
    {
        $this->isManager = $isManager;

        return $this;
    }

    public function isCustom(): ?bool
    {
        return $this->isCustom;
    }

    public function setIsCustom(?bool $isCustom): static
    {
        $this->isCustom = $isCustom;

        return $this;
    }
}
