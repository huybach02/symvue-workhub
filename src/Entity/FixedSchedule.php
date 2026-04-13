<?php

namespace App\Entity;

use App\Repository\FixedScheduleRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FixedScheduleRepository::class)]
class FixedSchedule
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $endTime = null;

    #[ORM\ManyToOne(inversedBy: 'fixedSchedules')]
    private ?FixedScheduleGroup $fixedScheduleGroup = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getStartTime(): ?\DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTime $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTime $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'fixedScheduleGroup' => $this->fixedScheduleGroup->jsonSerialize(),
            'dayOfWeek' => $this->dayOfWeek,
            'startTime' => $this->startTime->format('H:i:s'),
            'endTime' => $this->endTime->format('H:i:s'),
        ];
    }

    public function getFixedScheduleGroup(): ?FixedScheduleGroup
    {
        return $this->fixedScheduleGroup;
    }

    public function setFixedScheduleGroup(?FixedScheduleGroup $fixedScheduleGroup): static
    {
        $this->fixedScheduleGroup = $fixedScheduleGroup;

        return $this;
    }
}
