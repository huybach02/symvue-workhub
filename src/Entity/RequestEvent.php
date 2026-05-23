<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RequestEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RequestEventRepository::class)]
#[ORM\Table(name: 'request_event')]
class RequestEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Request $request = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Loại sự kiện timeline: created, submitted, approved, rejected...'])]
    private ?string $eventType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(options: ['comment' => 'Người thực hiện hành động tạo ra sự kiện timeline này'])]
    private ?User $actor = null;

    #[ORM\Column(length: 20, options: ['default' => 'user', 'comment' => 'Loại actor tạo ra sự kiện: user, system, job'])]
    private string $actorType = 'user';

    #[ORM\Column(nullable: true, options: ['comment' => 'Bước duyệt liên quan đến sự kiện này'])]
    private ?int $stepNo = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Lần gửi đề xuất liên quan đến sự kiện này'])]
    private ?int $revisionNo = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái trước khi sự kiện xảy ra'])]
    private ?string $fromStatus = null;

    #[ORM\Column(length: 30, nullable: true, options: ['comment' => 'Trạng thái sau khi sự kiện xảy ra'])]
    private ?string $toStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Nội dung ghi chú đi kèm sự kiện timeline'])]
    private ?string $comment = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Snapshot payload của đề xuất tại thời điểm sự kiện diễn ra'])]
    private ?array $payloadSnapshot = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Metadata mở rộng của sự kiện timeline'])]
    private ?array $meta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm sự kiện timeline được ghi nhận'])]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function setActorType(string $actorType): static
    {
        $this->actorType = $actorType;

        return $this;
    }

    public function getStepNo(): ?int
    {
        return $this->stepNo;
    }

    public function setStepNo(?int $stepNo): static
    {
        $this->stepNo = $stepNo;

        return $this;
    }

    public function getRevisionNo(): ?int
    {
        return $this->revisionNo;
    }

    public function setRevisionNo(?int $revisionNo): static
    {
        $this->revisionNo = $revisionNo;

        return $this;
    }

    public function getFromStatus(): ?string
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?string $fromStatus): static
    {
        $this->fromStatus = $fromStatus;

        return $this;
    }

    public function getToStatus(): ?string
    {
        return $this->toStatus;
    }

    public function setToStatus(?string $toStatus): static
    {
        $this->toStatus = $toStatus;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPayloadSnapshot(): ?array
    {
        return $this->payloadSnapshot;
    }

    public function setPayloadSnapshot(?array $payloadSnapshot): static
    {
        $this->payloadSnapshot = $payloadSnapshot;

        return $this;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function setMeta(?array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'eventType' => $this->eventType,
            'actor' => $this->actor?->jsonSerialize(),
            'actorType' => $this->actorType,
            'stepNo' => $this->stepNo,
            'revisionNo' => $this->revisionNo,
            'fromStatus' => $this->fromStatus,
            'toStatus' => $this->toStatus,
            'comment' => $this->comment,
            'payloadSnapshot' => $this->payloadSnapshot ?? [],
            'meta' => $this->meta ?? [],
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
