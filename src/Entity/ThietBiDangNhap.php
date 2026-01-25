<?php

namespace App\Entity;

use App\Repository\ThietBiDangNhapRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThietBiDangNhapRepository::class)]
class ThietBiDangNhap
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $device_key = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceKey(): ?string
    {
        return $this->device_key;
    }

    public function setDeviceKey(string $device_key): static
    {
        $this->device_key = $device_key;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}
