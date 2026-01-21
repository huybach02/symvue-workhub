<?php

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeleteableTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    // Helper để check xem đã xóa chưa
    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    // Helper để khôi phục (nếu cần xử lý logic thủ công)
    public function recover(): static
    {
        $this->deletedAt = null;
        return $this;
    }
}
