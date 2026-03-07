<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConversationUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationUserRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_conversation_user', columns: ['conversation_id', 'user_id'])]
class ConversationUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'conversationUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $member = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $unreadCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReadAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOnline = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastOnlineAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isMuted = false;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): static
    {
        $this->member = $member;

        return $this;
    }

    public function getUnreadCount(): int
    {
        return $this->unreadCount;
    }

    public function setUnreadCount(int $unreadCount): static
    {
        $this->unreadCount = $unreadCount;

        return $this;
    }

    public function incrementUnread(): static
    {
        $this->unreadCount++;

        return $this;
    }

    public function resetUnread(): static
    {
        $this->unreadCount = 0;
        $this->lastReadAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLastReadAt(): ?\DateTimeImmutable
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(?\DateTimeImmutable $lastReadAt): static
    {
        $this->lastReadAt = $lastReadAt;

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        if (!$isOnline) {
            $this->lastOnlineAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getLastOnlineAt(): ?\DateTimeImmutable
    {
        return $this->lastOnlineAt;
    }

    public function setLastOnlineAt(?\DateTimeImmutable $lastOnlineAt): static
    {
        $this->lastOnlineAt = $lastOnlineAt;

        return $this;
    }

    public function isMuted(): bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): static
    {
        $this->isMuted = $isMuted;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getLeftAt(): ?\DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function leave(): static
    {
        $this->leftAt = new \DateTimeImmutable();

        return $this;
    }

    public function setLeftAt(?\DateTimeImmutable $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->leftAt === null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation?->getId(),
            'userId' => $this->member?->getId(),
            'unreadCount' => $this->unreadCount,
            'lastReadAt' => $this->lastReadAt?->format('Y-m-d H:i:s'),
            'isOnline' => $this->isOnline,
            'lastOnlineAt' => $this->lastOnlineAt?->format('Y-m-d H:i:s'),
            'isMuted' => $this->isMuted,
            'joinedAt' => $this->joinedAt->format('Y-m-d H:i:s'),
            'leftAt' => $this->leftAt?->format('Y-m-d H:i:s'),
        ];
    }
}
