<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => 'private, group'])]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastMessage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isDraft = true;

    /**
     * @var Collection<int, ConversationUser>
     */
    #[ORM\OneToMany(targetEntity: ConversationUser::class, mappedBy: 'conversation')]
    private Collection $conversationUsers;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation')]
    private Collection $messages;

    #[ORM\OneToOne(inversedBy: 'conversation', cascade: ['persist', 'remove'])]
    private ?Department $boPhan = null;

    public function __construct()
    {
        $this->conversationUsers = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getLastMessage(): ?string
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?string $lastMessage): static
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'lastMessage' => $this->lastMessage,
            'lastMessageAt' => $this->lastMessageAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return Collection<int, ConversationUser>
     */
    public function getConversationUsers(): Collection
    {
        return $this->conversationUsers;
    }

    public function addConversationUser(ConversationUser $conversationUser): static
    {
        if (!$this->conversationUsers->contains($conversationUser)) {
            $this->conversationUsers->add($conversationUser);
            $conversationUser->setConversation($this);
        }

        return $this;
    }

    public function removeConversationUser(ConversationUser $conversationUser): static
    {
        if ($this->conversationUsers->removeElement($conversationUser)) {
            // set the owning side to null (unless already changed)
            if ($conversationUser->getConversation() === $this) {
                $conversationUser->setConversation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function getBoPhan(): ?Department
    {
        return $this->boPhan;
    }

    public function setBoPhan(?Department $boPhan): static
    {
        $this->boPhan = $boPhan;

        return $this;
    }
}
