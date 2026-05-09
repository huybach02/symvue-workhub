<?php

namespace App\Entity;

use App\Repository\WorkShiftRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkShiftRepository::class)]
#[ORM\Table(name: 'work_shift')]
class WorkShift
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'caLamViecs')]
    private ?WorkingTime $thoiGianLamViec = null;

    #[ORM\Column(length: 255)]
    private ?string $gioBatDau = null;

    #[ORM\Column(length: 255)]
    private ?string $gioKetThuc = null;

    #[ORM\Column(length: 255)]
    private ?string $ghiChu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    /**
     * @var Collection<int, WorkShiftAssignment>
     */
    #[ORM\OneToMany(targetEntity: WorkShiftAssignment::class, mappedBy: 'workShift')]
    private Collection $workShiftAssignments;

    public function __construct()
    {
        $this->workShiftAssignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getThoiGianLamViec(): ?WorkingTime
    {
        return $this->thoiGianLamViec;
    }

    public function setThoiGianLamViec(?WorkingTime $thoiGianLamViec): static
    {
        $this->thoiGianLamViec = $thoiGianLamViec;

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

    public function setGhiChu(string $ghiChu): static
    {
        $this->ghiChu = $ghiChu;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'thoiGianLamViec' => $this->thoiGianLamViec?->jsonSerialize(),
            'gioBatDau' => $this->gioBatDau,
            'gioKetThuc' => $this->gioKetThuc,
            'ghiChu' => $this->ghiChu,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, WorkShiftAssignment>
     */
    public function getWorkShiftAssignments(): Collection
    {
        return $this->workShiftAssignments;
    }

    public function addWorkShiftAssignment(WorkShiftAssignment $workShiftAssignment): static
    {
        if (!$this->workShiftAssignments->contains($workShiftAssignment)) {
            $this->workShiftAssignments->add($workShiftAssignment);
            $workShiftAssignment->setWorkShift($this);
        }

        return $this;
    }

    public function removeWorkShiftAssignment(WorkShiftAssignment $workShiftAssignment): static
    {
        if ($this->workShiftAssignments->removeElement($workShiftAssignment)) {
            // set the owning side to null (unless already changed)
            if ($workShiftAssignment->getWorkShift() === $this) {
                $workShiftAssignment->setWorkShift(null);
            }
        }

        return $this;
    }
}
