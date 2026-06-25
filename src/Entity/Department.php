<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\Table(name: 'department')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_DEPARTMENT_MA_BO_PHAN',
    fields: ['maBoPhan'],
    options: ['where' => 'deleted_at IS NULL']
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Department
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ghiChu = null;

    #[ORM\Column(nullable: true)]
    private ?array $phanQuyen = null;

    #[ORM\Column(nullable: true)]
    private ?array $quanLyBoPhan = null;

    #[ORM\OneToOne(mappedBy: 'boPhan', cascade: ['persist', 'remove'])]
    private ?Conversation $conversation = null;

    /**
     * @var Collection<int, Position>
     */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'department')]
    private Collection $positions;

    /**
     * @var Collection<int, UserPosition>
     */
    #[ORM\OneToMany(targetEntity: UserPosition::class, mappedBy: 'department')]
    private Collection $userPositions;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->userPositions = new ArrayCollection();
    }

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

    public function getGhiChu(): ?string
    {
        return $this->ghiChu;
    }

    public function setGhiChu(?string $ghiChu): static
    {
        $this->ghiChu = $ghiChu;

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
            'positionCount' => $this->positions->count(),
            'status' => $this->status,
            'ghiChu' => $this->ghiChu,
            'phanQuyen' => $this->phanQuyen,
            "positionManager" => $this->getPositionManager()?->jsonSerialize(),
            'quanLyBoPhan' => array_map(
                fn(User $user) => $user->getName(),
                $this->getQuanLyBoPhan()
            ),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        // unset the owning side of the relation if necessary
        if ($conversation === null && $this->conversation !== null) {
            $this->conversation->setBoPhan(null);
        }

        // set the owning side of the relation if necessary
        if ($conversation !== null && $conversation->getBoPhan() !== $this) {
            $conversation->setBoPhan($this);
        }

        $this->conversation = $conversation;

        return $this;
    }

    /**
     * @return Collection<int, Position>
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): static
    {
        if (!$this->positions->contains($position)) {
            $this->positions->add($position);
            $position->setDepartment($this);
        }

        return $this;
    }

    public function removePosition(Position $position): static
    {
        if ($this->positions->removeElement($position)) {
            // set the owning side to null (unless already changed)
            if ($position->getDepartment() === $this) {
                $position->setDepartment(null);
            }
        }

        return $this;
    }

    public function getPositionManager(): ?Position
    {
        foreach ($this->positions as $position) {
            if ($position->getIsManager()) {
                return $position;
            }
        }
        return null;
    }

    /**
     * @return Collection<int, UserPosition>
     */
    public function getUserPositions(): Collection
    {
        return $this->userPositions;
    }

    public function addUserPosition(UserPosition $userPosition): static
    {
        if (!$this->userPositions->contains($userPosition)) {
            $this->userPositions->add($userPosition);
            $userPosition->setDepartment($this);
        }

        return $this;
    }

    public function removeUserPosition(UserPosition $userPosition): static
    {
        if ($this->userPositions->removeElement($userPosition)) {
            // set the owning side to null (unless already changed)
            if ($userPosition->getDepartment() === $this) {
                $userPosition->setDepartment(null);
            }
        }

        return $this;
    }

    public function getQuanLyBoPhan(): array
    {
        $managers = [];

        foreach ($this->positions as $position) {
            if ((int) $position->getIsManager() !== 1) {
                continue;
            }

            foreach ($position->getUserPositions() as $userPosition) {
                if ((int) $userPosition->getIsPrimary() !== 1) {
                    continue;
                }

                $member = $userPosition->getMember();

                if (!$member) {
                    continue;
                }

                $managers[$member->getId()] = $member;
            }
        }

        return array_values($managers);
    }
}
