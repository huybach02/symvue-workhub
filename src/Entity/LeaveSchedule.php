<?php

namespace App\Entity;

use App\Repository\LeaveScheduleRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeaveScheduleRepository::class)]
class LeaveSchedule
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'leaveSchedules')]
    private ?User $member = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $startDatetime = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $endDatetime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStartDatetime(): ?\DateTime
    {
        return $this->startDatetime;
    }

    public function setStartDatetime(?\DateTime $startDatetime): static
    {
        $this->startDatetime = $startDatetime;

        return $this;
    }

    public function getEndDatetime(): ?\DateTime
    {
        return $this->endDatetime;
    }

    public function setEndDatetime(?\DateTime $endDatetime): static
    {
        $this->endDatetime = $endDatetime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
