<?php

namespace App\Entity;

use App\Repository\ThoiGianLamViecRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThoiGianLamViecRepository::class)]
class ThoiGianLamViec
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $thu = null;

    #[ORM\Column(length: 255)]
    private ?string $gioBatDau = null;

    #[ORM\Column(length: 255)]
    private ?string $gioKetThuc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ghiChu = null;

    /**
     * @var Collection<int, CaLamViec>
     */
    #[ORM\OneToMany(targetEntity: CaLamViec::class, mappedBy: 'thoiGianLamViec')]
    private Collection $caLamViecs;

    public function __construct()
    {
        $this->caLamViecs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getThu(): ?string
    {
        return $this->thu;
    }

    public function setThu(string $thu): static
    {
        $this->thu = $thu;

        return $this;
    }

    public function getGioBatDau(): ?string
    {
        return $this->gioBatDau;
    }

    public function setGioBatDau(string $gioBatDau): static
    {
        $this->gioBatDau = $gioBatDau;

        return $this;
    }

    public function getGioKetThuc(): ?string
    {
        return $this->gioKetThuc;
    }

    public function setGioKetThuc(string $gioKetThuc): static
    {
        $this->gioKetThuc = $gioKetThuc;

        return $this;
    }

    public function getGhiChu(): ?string
    {
        return $this->ghiChu;
    }

    public function setGhiChu(?string $ghiChu): static
    {
        $this->ghiChu = $ghiChu;

        return $this;
    }

    /**
     * @return Collection<int, CaLamViec>
     */
    public function getCaLamViecs(): Collection
    {
        return $this->caLamViecs;
    }

    public function addCaLamViec(CaLamViec $caLamViec): static
    {
        if (!$this->caLamViecs->contains($caLamViec)) {
            $this->caLamViecs->add($caLamViec);
            $caLamViec->setThoiGianLamViec($this);
        }

        return $this;
    }

    public function removeCaLamViec(CaLamViec $caLamViec): static
    {
        if ($this->caLamViecs->removeElement($caLamViec)) {
            // set the owning side to null (unless already changed)
            if ($caLamViec->getThoiGianLamViec() === $this) {
                $caLamViec->setThoiGianLamViec(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'thu' => $this->thu,
            'gioBatDau' => $this->gioBatDau,
            'gioKetThuc' => $this->gioKetThuc,
            'ghiChu' => $this->ghiChu,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
