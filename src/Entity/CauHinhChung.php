<?php

namespace App\Entity;

use App\Repository\CauHinhChungRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Traits\TimestampableTrait;

#[ORM\Entity(repositoryClass: CauHinhChungRepository::class)]
class CauHinhChung
{
    use TimestampableTrait;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'tenCauHinh' => $this->tenCauHinh,
            'giaTri' => $this->giaTri,
            'moTa' => $this->moTa,
        ];
    }
}
