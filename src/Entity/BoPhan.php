<?php

namespace App\Entity;

use App\Repository\BoPhanRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoPhanRepository::class)]
class BoPhan
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $maBoPhan = null;

    #[ORM\Column(length: 255)]
    private ?string $tenBoPhan = null;

    #[ORM\Column(options: ["default" => 1])]
    private ?int $status = 1;

    #[ORM\Column(nullable: true)]
    private ?array $phanQuyen = null;

    #[ORM\ManyToOne(inversedBy: 'boPhans')]
    private ?User $quanLyBoPhan = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaBoPhan(): ?string
    {
        return $this->maBoPhan;
    }

    public function setMaBoPhan(string $maBoPhan): static
    {
        $this->maBoPhan = $maBoPhan;

        return $this;
    }

    public function getTenBoPhan(): ?string
    {
        return $this->tenBoPhan;
    }

    public function setTenBoPhan(string $tenBoPhan): static
    {
        $this->tenBoPhan = $tenBoPhan;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

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

    public function getQuanLyBoPhan(): ?User
    {
        return $this->quanLyBoPhan;
    }

    public function setQuanLyBoPhan(?User $quanLyBoPhan): static
    {
        $this->quanLyBoPhan = $quanLyBoPhan;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'maBoPhan' => $this->maBoPhan,
            'tenBoPhan' => $this->tenBoPhan,
            'status' => $this->status,
            'phanQuyen' => $this->phanQuyen,
            'quanLyBoPhanId' => $this->quanLyBoPhan->getId(),
            'quanLyBoPhan' => $this->quanLyBoPhan->getName(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
