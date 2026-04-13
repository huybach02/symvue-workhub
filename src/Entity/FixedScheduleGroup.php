<?php

namespace App\Entity;

use App\Repository\FixedScheduleGroupRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FixedScheduleGroupRepository::class)]
class FixedScheduleGroup
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fixedScheduleGroups')]
    private ?User $member = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    /**
     * @var Collection<int, FixedSchedule>
     */
    #[ORM\OneToMany(targetEntity: FixedSchedule::class, mappedBy: 'fixedScheduleGroup')]
    private Collection $fixedSchedules;

    public function __construct()
    {
        $this->fixedSchedules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): static
    {
        $this->endDate = $endDate;

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

    /**
     * @return Collection<int, FixedSchedule>
     */
    public function getFixedSchedules(): Collection
    {
        return $this->fixedSchedules;
    }

    public function addFixedSchedule(FixedSchedule $fixedSchedule): static
    {
        if (!$this->fixedSchedules->contains($fixedSchedule)) {
            $this->fixedSchedules->add($fixedSchedule);
            $fixedSchedule->setFixedScheduleGroup($this);
        }

        return $this;
    }

    public function removeFixedSchedule(FixedSchedule $fixedSchedule): static
    {
        if ($this->fixedSchedules->removeElement($fixedSchedule)) {
            // set the owning side to null (unless already changed)
            if ($fixedSchedule->getFixedScheduleGroup() === $this) {
                $fixedSchedule->setFixedScheduleGroup(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'member' => $this->member->jsonSerialize(),
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
            'type' => $this->type,
        ];
    }
}
