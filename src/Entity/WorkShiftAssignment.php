<?php

namespace App\Entity;

use App\Repository\WorkShiftAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkShiftAssignmentRepository::class)]
class WorkShiftAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'workShiftAssignments')]
    private ?WorkShift $workShift = null;

    #[ORM\ManyToOne(inversedBy: 'workShiftAssignments')]
    private ?User $member = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkShift(): ?WorkShift
    {
        return $this->workShift;
    }

    public function setWorkShift(?WorkShift $workShift): static
    {
        $this->workShift = $workShift;

        return $this;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "workShift" => $this->workShift->getId(),
            "member" => $this->member->getId(),
            "note" => $this->note,
            "date" => $this->date->format("Y-m-d"),
        ];
    }
}
