<?php

namespace App\Entity;

use App\Repository\CauHinhChungRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CauHinhChungRepository::class)]
class CauHinhChung
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tenCauHinh = null;

    #[ORM\Column(length: 255)]
    private ?string $giaTri = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $moTa = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenCauHinh(): ?string
    {
        return $this->tenCauHinh;
    }

    public function setTenCauHinh(string $tenCauHinh): static
    {
        $this->tenCauHinh = $tenCauHinh;

        return $this;
    }

    public function getGiaTri(): ?string
    {
        return $this->giaTri;
    }

    public function setGiaTri(string $giaTri): static
    {
        $this->giaTri = $giaTri;

        return $this;
    }

    public function getMoTa(): ?string
    {
        return $this->moTa;
    }

    public function setMoTa(?string $moTa): static
    {
        $this->moTa = $moTa;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
