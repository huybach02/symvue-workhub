<?php

namespace App\Entity;

use App\Repository\PositionRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Position
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'positions')]
    private ?Department $department = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'Hinh thuc lam viec (FULL_TIME, PART_TIME...)', 'default' => 'FULL_TIME'])]
    private ?string $employmentType = 'FULL_TIME';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $minSalary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $maxSalary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'So thang thu viec tieu chuan', 'default' => 2])]
    private ?int $probationMonths = 2;

    #[ORM\Column(nullable: true, options: ['comment' => 'Ty le huong luong thu viec (%)', 'default' => 85])]
    private ?int $probationSalaryRate = 85;

    #[ORM\Column(nullable: true, options: ['comment' => 'So ngay nghi phep nam tieu chuan', 'default' => 12])]
    private ?int $annualLeaveDays = 12;

    #[ORM\Column(nullable: true, options: ['comment' => 'So thang danh gia luong nam tieu chuan', 'default' => 12])]
    private ?int $reviewCycleMonths = 12;

    #[ORM\Column(nullable: true, options: ['comment' => 'So ngay thong bao truoc khi nghi viec', 'default' => 12])]
    private ?int $noticePeriodDays = 12;

    #[ORM\Column(nullable: true, options: ['comment' => 'Danh sach phu cap (JSON)'])]
    private ?array $allowances = null;

    #[ORM\Column(nullable: true, options: ['comment' => '1: active, 0: inactive', 'default' => 1])]
    private int $status = 1;

    #[ORM\Column(nullable: true, options: ['comment' => '1: yes, 0: no', 'default' => 0])]
    private int $isManager = 0;

    /**
     * @var Collection<int, UserPosition>
     */
    #[ORM\OneToMany(targetEntity: UserPosition::class, mappedBy: 'position')]
    private Collection $userPositions;

    public function __construct()
    {
        $this->userPositions = new ArrayCollection();
    }

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEmploymentType(): ?string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(?string $employmentType): static
    {
        $this->employmentType = $employmentType;

        return $this;
    }

    public function getMinSalary(): ?string
    {
        return $this->minSalary;
    }

    public function setMinSalary(?string $minSalary): static
    {
        $this->minSalary = $minSalary;

        return $this;
    }

    public function getMaxSalary(): ?string
    {
        return $this->maxSalary;
    }

    public function setMaxSalary(?string $maxSalary): static
    {
        $this->maxSalary = $maxSalary;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getProbationMonths(): ?int
    {
        return $this->probationMonths;
    }

    public function setProbationMonths(?int $probationMonths): static
    {
        $this->probationMonths = $probationMonths;

        return $this;
    }

    public function getProbationSalaryRate(): ?int
    {
        return $this->probationSalaryRate;
    }

    public function setProbationSalaryRate(?int $probationSalaryRate): static
    {
        $this->probationSalaryRate = $probationSalaryRate;

        return $this;
    }

    public function getAnnualLeaveDays(): ?int
    {
        return $this->annualLeaveDays;
    }

    public function setAnnualLeaveDays(?int $annualLeaveDays): static
    {
        $this->annualLeaveDays = $annualLeaveDays;

        return $this;
    }

    public function getReviewCycleMonths(): ?int
    {
        return $this->reviewCycleMonths;
    }

    public function setReviewCycleMonths(?int $reviewCycleMonths): static
    {
        $this->reviewCycleMonths = $reviewCycleMonths;

        return $this;
    }

    public function getNoticePeriodDays(): ?int
    {
        return $this->noticePeriodDays;
    }

    public function setNoticePeriodDays(?int $noticePeriodDays): static
    {
        $this->noticePeriodDays = $noticePeriodDays;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsManager(): ?int
    {
        return $this->isManager;
    }

    public function setIsManager(?int $isManager): static
    {
        $this->isManager = $isManager ?? 0;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'departmentId' => $this->department?->getId(),
            'department' => $this->department?->getTenBoPhan(),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'employmentType' => $this->employmentType,
            'minSalary' => (int)$this->minSalary,
            'maxSalary' => (int)$this->maxSalary,
            'currency' => $this->currency,
            'probationMonths' => $this->probationMonths,
            'probationSalaryRate' => $this->probationSalaryRate,
            'annualLeaveDays' => $this->annualLeaveDays,
            'reviewCycleMonths' => $this->reviewCycleMonths,
            'noticePeriodDays' => $this->noticePeriodDays,
            'allowances' => $this->allowances,
            'status' => $this->status,
            'isManager' => $this->isManager,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
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
            $userPosition->setPosition($this);
        }

        return $this;
    }

    public function removeUserPosition(UserPosition $userPosition): static
    {
        if ($this->userPositions->removeElement($userPosition)) {
            // set the owning side to null (unless already changed)
            if ($userPosition->getPosition() === $this) {
                $userPosition->setPosition(null);
            }
        }

        return $this;
    }
}
