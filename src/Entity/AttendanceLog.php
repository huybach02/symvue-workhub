<?php

namespace App\Entity;

use App\Repository\AttendanceLogRepository;
use App\Traits\ModifierTrait;
use App\Traits\SoftDeleteableTrait;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttendanceLogRepository::class)]
class AttendanceLog
{
    use TimestampableTrait;
    use ModifierTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "attendanceLogs")]
    private ?Attendance $attendance = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "invalid/valid"],
        ),
    ]
    private ?string $validationStatus = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "lưu code của reason"],
        ),
    ]
    private ?string $validationReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true, options: ["comment" => "vĩ độ"])]
    private ?string $latitude = null;

    #[
        ORM\Column(
            length: 255,
            nullable: true,
            options: ["comment" => "kinh độ"],
        ),
    ]
    private ?string $longtitude = null;

    #[ORM\Column(nullable: true, options: ["comment" => "khoảng cách"])]
    private ?int $gpsAccuracyMeter = null;

    #[ORM\Column(nullable: true)]
    private ?int $deviceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrToken = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttendance(): ?Attendance
    {
        return $this->attendance;
    }

    public function setAttendance(?Attendance $attendance): static
    {
        $this->attendance = $attendance;

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

    public function getValidationReason(): ?string
    {
        return $this->validationReason;
    }

    public function setValidationReason(?string $validationReason): static
    {
        $this->validationReason = $validationReason;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongtitude(): ?string
    {
        return $this->longtitude;
    }

    public function setLongtitude(?string $longtitude): static
    {
        $this->longtitude = $longtitude;

        return $this;
    }

    public function getGpsAccuracyMeter(): ?int
    {
        return $this->gpsAccuracyMeter;
    }

    public function setGpsAccuracyMeter(?int $gpsAccuracyMeter): static
    {
        $this->gpsAccuracyMeter = $gpsAccuracyMeter;

        return $this;
    }

    public function getDeviceId(): ?int
    {
        return $this->deviceId;
    }

    public function setDeviceId(?int $deviceId): static
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getQrToken(): ?string
    {
        return $this->qrToken;
    }

    public function setQrToken(?string $qrToken): static
    {
        $this->qrToken = $qrToken;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "attendance" => $this->attendance,
            "validationStatus" => $this->validationStatus,
            "validationReason" => $this->validationReason,
            "ipAddress" => $this->ipAddress,
            "latitude" => $this->latitude,
            "longtitude" => $this->longtitude,
            "gpsAccuracyMeter" => $this->gpsAccuracyMeter,
            "deviceId" => $this->deviceId,
            "qrToken" => $this->qrToken,
        ];
    }
}
