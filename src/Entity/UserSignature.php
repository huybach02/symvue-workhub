<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSignatureRepository;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSignatureRepository::class)]
#[ORM\Table(name: 'user_signature')]
class UserSignature implements \JsonSerializable
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true, nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'signature_png_path', length: 255)]
    private ?string $signaturePngPath = null;

    #[ORM\Column(name: 'signature_svg_path', length: 255)]
    private ?string $signatureSvgPath = null;

    #[ORM\Column(name: 'signature_strokes', type: Types::JSON, nullable: true)]
    private ?array $signatureStrokes = null;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column(name: 'file_size', nullable: true)]
    private ?int $fileSize = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSignaturePngPath(): ?string
    {
        return $this->signaturePngPath;
    }

    public function setSignaturePngPath(string $signaturePngPath): static
    {
        $this->signaturePngPath = $signaturePngPath;

        return $this;
    }

    public function getSignatureSvgPath(): ?string
    {
        return $this->signatureSvgPath;
    }

    public function setSignatureSvgPath(string $signatureSvgPath): static
    {
        $this->signatureSvgPath = $signatureSvgPath;

        return $this;
    }

    public function getSignatureStrokes(): ?array
    {
        return $this->signatureStrokes;
    }

    public function setSignatureStrokes(?array $signatureStrokes): static
    {
        $this->signatureStrokes = $signatureStrokes;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user?->getId(),
            'signaturePngUrl' => $this->signaturePngPath,
            'signatureSvgUrl' => $this->signatureSvgPath,
            'signatureStrokes' => $this->signatureStrokes,
            'width' => $this->width,
            'height' => $this->height,
            'fileSize' => $this->fileSize,
            'createdAt' => $this->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
