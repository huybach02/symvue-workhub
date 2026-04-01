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
    private ?int $departmentId = null;

    #[ORM\Column]
    private ?int $positionId = null;

    #[ORM\Column(nullable: true)]
    private ?array $phanQuyen = null;

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

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(int $departmentId): static
    {
        $this->departmentId = $departmentId;

        return $this;
    }

    public function getPositionId(): ?int
    {
        return $this->positionId;
    }

    public function setPositionId(int $positionId): static
    {
        $this->positionId = $positionId;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'departmentId' => $this->departmentId,
            'positionId' => $this->positionId,
            'phanQuyen' => $this->phanQuyen,
        ];
    }
}
