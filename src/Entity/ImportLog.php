<?php

namespace App\Entity;

use App\Repository\ImportLogRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportLogRepository::class)]
class ImportLog
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $entityType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalRows = null;

    #[ORM\Column(nullable: true)]
    private ?int $successRows = null;

    #[ORM\Column(nullable: true)]
    private ?int $errorRows = null;

    #[ORM\Column(nullable: true)]
    private ?array $errorDetails = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

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

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function setTotalRows(?int $totalRows): static
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getSuccessRows(): ?int
    {
        return $this->successRows;
    }

    public function setSuccessRows(?int $successRows): static
    {
        $this->successRows = $successRows;

        return $this;
    }

    public function getErrorRows(): ?int
    {
        return $this->errorRows;
    }

    public function setErrorRows(?int $errorRows): static
    {
        $this->errorRows = $errorRows;

        return $this;
    }

    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(?array $errorDetails): static
    {
        $this->errorDetails = $errorDetails;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'entityType' => $this->entityType,
            'fileName' => $this->fileName,
            'filePath' => $this->filePath,
            'status' => $this->status,
            'totalRows' => $this->totalRows,
            'successRows' => $this->successRows,
            'errorRows' => $this->errorRows,
            'errorDetails' => $this->errorDetails,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
