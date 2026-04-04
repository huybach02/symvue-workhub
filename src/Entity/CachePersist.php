<?php

namespace App\Entity;

use App\Repository\CachePersistRepository;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CachePersistRepository::class)]
class CachePersist
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $key = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $value = null;

    #[ORM\Column]
    private ?int $expireAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value ?? '';
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getJsonValue(): array
    {
        if ($this->value === null || $this->value === '') {
            return [];
        }

        $decoded = json_decode($this->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setJsonValue(array $value): static
    {
        $this->value = json_encode($value, JSON_UNESCAPED_UNICODE);

        return $this;
    }

    public function getExpireAt(): ?int
    {
        return $this->expireAt;
    }

    public function setExpireAt(int $expireAt): static
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'expireAt' => $this->expireAt,
        ];
    }
}
