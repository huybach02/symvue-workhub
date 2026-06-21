<?php

namespace App\Entity;

use App\Repository\UserPositionRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: UserPositionRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class UserPosition
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "userPositions")]
    private ?Department $department = null;

    #[ORM\ManyToOne(inversedBy: "userPositions")]
    private ?Position $position = null;

    #[ORM\ManyToOne(inversedBy: "userPositions")]
    private ?User $member = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $salary = null;

    #[ORM\Column(nullable: true)]
    private ?array $allowances = null;

    #[ORM\Column(nullable: true)]
    private ?array $allowancesTotal = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $effective_from = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $effective_to = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $probation_from = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $probation_to = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $salary_net = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $salaryGross = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $insurance_salary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $insurance_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(nullable: true)]
    private ?array $positionSnapshot = null;

    #[
        ORM\Column(
            nullable: true,
            options: ["comment" => "1: active, 0: inactive", "default" => 1],
        ),
    ]
    private int $status = 1;

    #[
        ORM\Column(
            nullable: true,
            options: ["comment" => "1: yes, 0: no", "default" => 0],
        ),
    ]
    private int $isPrimary = 0;

    #[ORM\Column(nullable: true)]
    private ?array $contracts = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $startTemp = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $endTemp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

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

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): static
    {
        $this->salary = $salary;

        return $this;
    }

    public function getAllowances(): ?array
    {
        return $this->allowances;
    }

    public function setAllowances(?array $allowances): static
    {
        $this->allowances = $allowances;

        return $this;
    }

    public function getEffectiveFrom(): ?\DateTime
    {
        return $this->effective_from;
    }

    public function setEffectiveFrom(?\DateTime $effective_from): static
    {
        $this->effective_from = $effective_from;

        return $this;
    }

    public function getEffectiveTo(): ?\DateTime
    {
        return $this->effective_to;
    }

    public function setEffectiveTo(?\DateTime $effective_to): static
    {
        $this->effective_to = $effective_to;

        return $this;
    }

    public function getProbationFrom(): ?\DateTime
    {
        return $this->probation_from;
    }

    public function setProbationFrom(\DateTime $probation_from): static
    {
        $this->probation_from = $probation_from;

        return $this;
    }

    public function getProbationTo(): ?\DateTime
    {
        return $this->probation_to;
    }

    public function setProbationTo(?\DateTime $probation_to): static
    {
        $this->probation_to = $probation_to;

        return $this;
    }

    public function getSalaryNet(): ?string
    {
        return $this->salary_net;
    }

    public function setSalaryNet(?string $salary_net): static
    {
        $this->salary_net = $salary_net;

        return $this;
    }

    public function getSalaryGross(): ?string
    {
        return $this->salaryGross;
    }

    public function setSalaryGross(?string $salaryGross): static
    {
        $this->salaryGross = $salaryGross;

        return $this;
    }

    public function getInsuranceSalary(): ?string
    {
        return $this->insurance_salary;
    }

    public function setInsuranceSalary(?string $insurance_salary): static
    {
        $this->insurance_salary = $insurance_salary;

        return $this;
    }

    public function getInsuranceCode(): ?string
    {
        return $this->insurance_code;
    }

    public function setInsuranceCode(?string $insurance_code): static
    {
        $this->insurance_code = $insurance_code;

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

    public function getPositionSnapshot(): ?array
    {
        return $this->positionSnapshot;
    }

    public function setPositionSnapshot(?array $positionSnapshot): static
    {
        $this->positionSnapshot = $positionSnapshot;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsPrimary(): ?int
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(?int $isPrimary): static
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getContracts(): ?array
    {
        return $this->contracts;
    }

    public function setContracts(?array $contracts): static
    {
        $this->contracts = $contracts;

        return $this;
    }

    public function getAllowancesTotal(): ?array
    {
        return $this->allowancesTotal;
    }

    public function setAllowancesTotal(?array $allowancesTotal): static
    {
        $this->allowancesTotal = $allowancesTotal;

        return $this;
    }

    public function getStartTemp(): ?int
    {
        return $this->startTemp;
    }

    public function setStartTemp(?int $startTemp): static
    {
        $this->startTemp = $startTemp;

        return $this;
    }

    public function getEndTemp(): ?int
    {
        return $this->endTemp;
    }

    public function setEndTemp(?int $endTemp): static
    {
        $this->endTemp = $endTemp;

        return $this;
    }

    public function getIsExpired(): ?int
    {
        $currentTimestamp = time();
        $isExpired = 0;
        if ($this->endTemp !== null) {
            $isExpired = $currentTimestamp > $this->endTemp ? 1 : 0;
        }
        return $isExpired;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "departmentId" => $this->department?->getId(),
            "department" => $this->department?->jsonSerialize(),
            "positionId" => $this->position?->getId(),
            "position" => $this->position?->jsonSerialize(),
            "memberId" => $this->member?->getId(),
            "salary" => $this->salary === null ? null : (float) $this->salary,
            "allowances" => $this->allowances,
            "allowancesTotal" => $this->allowancesTotal,
            "effectiveFrom" => $this->effective_from?->format("Y-m-d"),
            "effectiveTo" => $this->effective_to?->format("Y-m-d"),
            "probationFrom" => $this->probation_from?->format("Y-m-d"),
            "probationTo" => $this->probation_to?->format("Y-m-d"),
            "salaryNet" =>
            $this->salary_net === null ? null : (float) $this->salary_net,
            "salaryGross" =>
            $this->salaryGross === null ? null : (float) $this->salaryGross,
            "insuranceSalary" =>
            $this->insurance_salary === null
                ? null
                : (float) $this->insurance_salary,
            "insuranceCode" => $this->insurance_code,
            "note" => $this->note,
            "status" => $this->status,
            "isPrimary" => $this->isPrimary,
            "positionSnapshot" => $this->positionSnapshot,
            "contracts" => $this->contracts,
            "startTemp" =>  $this->startTemp === null ? null : (new DateTime())->setTimestamp($this->startTemp)->format("Y-m-d H:i:s"),
            "endTemp" =>  $this->endTemp === null ? null : (new DateTime())->setTimestamp($this->endTemp)->format("Y-m-d H:i:s"),
            "isExpired" => $this->getIsExpired(),
            "createdAt" => $this->createdAt?->format("Y-m-d H:i:s"),
            "updatedAt" => $this->updatedAt?->format("Y-m-d H:i:s"),
        ];
    }
}
