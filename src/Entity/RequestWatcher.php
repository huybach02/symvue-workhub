<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RequestWatcherRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RequestWatcherRepository::class)]
#[ORM\Table(name: 'request_watcher')]
class RequestWatcher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'watchers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Request $request = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'viewer', 'comment' => 'Loại theo dõi: viewer, cc, observer...'])]
    private ?string $watchType = 'viewer';

    #[ORM\Column(options: ['default' => true, 'comment' => 'Có nhận thông báo khi đề xuất được gửi đi hay không'])]
    private bool $notifyOnSubmit = true;

    #[ORM\Column(options: ['default' => true, 'comment' => 'Có nhận thông báo khi đề xuất bị từ chối hay không'])]
    private bool $notifyOnReject = true;

    #[ORM\Column(options: ['default' => true, 'comment' => 'Có nhận thông báo khi đề xuất được duyệt hay không'])]
    private bool $notifyOnApprove = true;

    #[ORM\Column(options: ['default' => true, 'comment' => 'Có nhận thông báo khi có trao đổi hoặc comment hay không'])]
    private bool $notifyOnComment = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => 'Thời điểm người theo dõi được thêm vào đề xuất'])]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWatchType(): ?string
    {
        return $this->watchType;
    }

    public function setWatchType(?string $watchType): static
    {
        $this->watchType = $watchType;

        return $this;
    }

    public function isNotifyOnSubmit(): bool
    {
        return $this->notifyOnSubmit;
    }

    public function setNotifyOnSubmit(bool $notifyOnSubmit): static
    {
        $this->notifyOnSubmit = $notifyOnSubmit;

        return $this;
    }

    public function isNotifyOnReject(): bool
    {
        return $this->notifyOnReject;
    }

    public function setNotifyOnReject(bool $notifyOnReject): static
    {
        $this->notifyOnReject = $notifyOnReject;

        return $this;
    }

    public function isNotifyOnApprove(): bool
    {
        return $this->notifyOnApprove;
    }

    public function setNotifyOnApprove(bool $notifyOnApprove): static
    {
        $this->notifyOnApprove = $notifyOnApprove;

        return $this;
    }

    public function isNotifyOnComment(): bool
    {
        return $this->notifyOnComment;
    }

    public function setNotifyOnComment(bool $notifyOnComment): static
    {
        $this->notifyOnComment = $notifyOnComment;

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
            'request' => $this->request?->jsonSerialize(),
            'user' => $this->user?->jsonSerialize(),
            'watchType' => $this->watchType,
            'notifyOnSubmit' => $this->notifyOnSubmit,
            'notifyOnReject' => $this->notifyOnReject,
            'notifyOnApprove' => $this->notifyOnApprove,
            'notifyOnComment' => $this->notifyOnComment,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
