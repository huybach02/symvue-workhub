<?php

namespace App\Entity;

use App\Repository\AttendanceRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttendanceRepository::class)]
class Attendance
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "attendances")]
    private ?User $employee = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "check_in/check_out"],
        ),
    ]
    private ?string $attendanceType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $workDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $time = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "on_time,late,early_leave"],
        ),
    ]
    private ?string $status = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "invalid/valid"],
        ),
    ]
    private ?string $validationStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $workType = null;

    #[ORM\Column(nullable: true)]
    private ?array $configSnapshot = null;

    #[ORM\Column(nullable: true)]
    private ?array $workScheduleSnapshot = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPath = null;

    #[ORM\Column(nullable: true)]
    private ?int $lateMinute = null;

    #[ORM\Column(nullable: true)]
    private ?int $earlyLeaveMinute = null;

    /**
     * @var Collection<int, AttendanceLog>
     */
    #[ORM\OneToMany(targetEntity: AttendanceLog::class, mappedBy: "attendance")]
    private Collection $attendanceLogs;

    public function __construct()
    {
        $this->attendanceLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getAttendanceType(): ?string
    {
        return $this->attendanceType;
    }

    public function setAttendanceType(?string $attendanceType): static
    {
        $this->attendanceType = $attendanceType;

        return $this;
    }

    public function getWorkDate(): ?\DateTime
    {
        return $this->workDate;
    }

    public function setWorkDate(?\DateTime $workDate): static
    {
        $this->workDate = $workDate;

        return $this;
    }

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(?\DateTime $time): static
    {
        $this->time = $time;

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

    public function getValidationStatus(): ?string
    {
        return $this->validationStatus;
    }

    public function setValidationStatus(?string $validationStatus): static
    {
        $this->validationStatus = $validationStatus;

        return $this;
    }

    public function getWorkType(): ?string
    {
        return $this->workType;
    }

    public function setWorkType(?string $workType): static
    {
        $this->workType = $workType;

        return $this;
    }

    public function getConfigSnapshot(): ?array
    {
        return $this->configSnapshot;
    }

    public function setConfigSnapshot(?array $configSnapshot): static
    {
        $this->configSnapshot = $configSnapshot;

        return $this;
    }

    public function getWorkScheduleSnapshot(): ?array
    {
        return $this->workScheduleSnapshot;
    }

    public function setWorkScheduleSnapshot(
        ?array $workScheduleSnapshot,
    ): static {
        $this->workScheduleSnapshot = $workScheduleSnapshot;

        return $this;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): static
    {
        $this->photoPath = $photoPath;

        return $this;
    }

    public function getLateMinute(): ?int
    {
        return $this->lateMinute;
    }

    public function setLateMinute(?int $lateMinute): static
    {
        $this->lateMinute = $lateMinute;

        return $this;
    }

    public function getEarlyLeaveMinute(): ?int
    {
        return $this->earlyLeaveMinute;
    }

    public function setEarlyLeaveMinute(?int $earlyLeaveMinute): static
    {
        $this->earlyLeaveMinute = $earlyLeaveMinute;

        return $this;
    }

    /**
     * @return Collection<int, AttendanceLog>
     */
    public function getAttendanceLogs(): Collection
    {
        return $this->attendanceLogs;
    }

    public function addAttendanceLog(AttendanceLog $attendanceLog): static
    {
        if (!$this->attendanceLogs->contains($attendanceLog)) {
            $this->attendanceLogs->add($attendanceLog);
            $attendanceLog->setAttendance($this);
        }

        return $this;
    }

    public function removeAttendanceLog(AttendanceLog $attendanceLog): static
    {
        if ($this->attendanceLogs->removeElement($attendanceLog)) {
            // set the owning side to null (unless already changed)
            if ($attendanceLog->getAttendance() === $this) {
                $attendanceLog->setAttendance(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "employee" => $this->employee,
            "attendanceType" => $this->attendanceType,
            "workDate" => $this->workDate,
            "time" => $this->time,
            "status" => $this->status,
            "validationStatus" => $this->validationStatus,
            "workType" => $this->workType,
            "configSnapshot" => $this->configSnapshot,
            "workScheduleSnapshot" => $this->workScheduleSnapshot,
            "photoPath" => $this->photoPath,
            "lateMinute" => $this->lateMinute,
            "earlyLeaveMinute" => $this->earlyLeaveMinute,
            "attendanceLogs" => $this->attendanceLogs,
        ];
    }
}
