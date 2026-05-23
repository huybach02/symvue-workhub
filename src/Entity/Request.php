<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RequestRepository;
use App\Traits\ModifierTrait;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RequestRepository::class)]
#[ORM\Table(name: 'request')]
class Request
{
    use TimestampableTrait;
    use ModifierTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 50, options: ['comment' => 'Loại đề xuất, ví dụ: leave, purchase, resignation'])]
    private ?string $type = null;

    #[ORM\Column(length: 20, options: ['comment' => 'Nguồn tạo đề xuất: manual hoặc system', 'default' => 'manual'])]
    private string $source = 'manual';

    #[ORM\Column(length: 30, options: ['comment' => 'Trạng thái hiện tại của đề xuất: pending, rejected, approved, cancelled'])]
    private ?string $status = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => 'Người tạo đề xuất'])]
    private ?User $requester = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(options: ['comment' => 'Người duyệt hiện tại của đề xuất'])]
    private ?User $currentApprover = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Snapshot JSON của bộ phận chính của người tạo tại thời điểm tạo đề xuất'])]
    private ?array $requesterDepartmentSnapshot = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Snapshot JSON của chức vụ chính của người tạo tại thời điểm tạo đề xuất'])]
    private ?array $requesterPositionSnapshot = null;

    #[ORM\Column(options: ['comment' => 'Bước duyệt hiện tại, có thể mở rộng duyệt nhiều cấp'])]
    private int $currentStepNo = 1;

    #[ORM\Column(options: ['comment' => 'Số lần gửi lại đề xuất sau khi bị từ chối'])]
    private int $revisionNo = 1;

    #[ORM\Column(length: 255, options: ['comment' => 'Tiêu đề của đề xuất'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Mô tả tóm tắt của đề xuất để hiển thị ở danh sách'])]
    private ?string $summary = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Payload JSON chứa dữ liệu chi tiết riêng theo từng loại đề xuất'])]
    private ?array $payload = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Lý do từ chối gần nhất của đề xuất'])]
    private ?string $lastRejectReason = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => 'Loại bản ghi nguồn đã tự động sinh ra đề xuất'])]
    private ?string $sourceRefType = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'ID bản ghi nguồn đã tự động sinh ra đề xuất'])]
    private ?int $sourceRefId = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => 'Loại dữ liệu đích được tạo hoặc cập nhật sau khi duyệt'])]
    private ?string $targetRefType = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'ID dữ liệu đích được tạo hoặc cập nhật sau khi duyệt'])]
    private ?int $targetRefId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm đề xuất được gửi đi để chờ duyệt'])]
    private ?\DateTimeInterface $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm đề xuất được duyệt'])]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm đề xuất bị từ chối gần nhất'])]
    private ?\DateTimeInterface $rejectedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm đề xuất bị hủy'])]
    private ?\DateTimeInterface $cancelledAt = null;

    /**
     * @var Collection<int, RequestApprovalStep>
     */
    #[ORM\OneToMany(targetEntity: RequestApprovalStep::class, mappedBy: 'request', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $approvalSteps;

    /**
     * @var Collection<int, RequestEvent>
     */
    #[ORM\OneToMany(targetEntity: RequestEvent::class, mappedBy: 'request', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $events;

    /**
     * @var Collection<int, RequestWatcher>
     */
    #[ORM\OneToMany(targetEntity: RequestWatcher::class, mappedBy: 'request', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $watchers;

    public function __construct()
    {
        $this->approvalSteps = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->watchers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(User $requester): static
    {
        $this->requester = $requester;

        return $this;
    }

    public function getCurrentApprover(): ?User
    {
        return $this->currentApprover;
    }

    public function setCurrentApprover(?User $currentApprover): static
    {
        $this->currentApprover = $currentApprover;

        return $this;
    }

    public function getRequesterDepartmentSnapshot(): ?array
    {
        return $this->requesterDepartmentSnapshot;
    }

    public function setRequesterDepartmentSnapshot(?array $requesterDepartmentSnapshot): static
    {
        $this->requesterDepartmentSnapshot = $requesterDepartmentSnapshot;

        return $this;
    }

    public function getRequesterPositionSnapshot(): ?array
    {
        return $this->requesterPositionSnapshot;
    }

    public function setRequesterPositionSnapshot(?array $requesterPositionSnapshot): static
    {
        $this->requesterPositionSnapshot = $requesterPositionSnapshot;

        return $this;
    }

    public function getCurrentStepNo(): int
    {
        return $this->currentStepNo;
    }

    public function setCurrentStepNo(int $currentStepNo): static
    {
        $this->currentStepNo = $currentStepNo;

        return $this;
    }

    public function getRevisionNo(): int
    {
        return $this->revisionNo;
    }

    public function setRevisionNo(int $revisionNo): static
    {
        $this->revisionNo = $revisionNo;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getLastRejectReason(): ?string
    {
        return $this->lastRejectReason;
    }

    public function setLastRejectReason(?string $lastRejectReason): static
    {
        $this->lastRejectReason = $lastRejectReason;

        return $this;
    }

    public function getSourceRefType(): ?string
    {
        return $this->sourceRefType;
    }

    public function setSourceRefType(?string $sourceRefType): static
    {
        $this->sourceRefType = $sourceRefType;

        return $this;
    }

    public function getSourceRefId(): ?int
    {
        return $this->sourceRefId;
    }

    public function setSourceRefId(?int $sourceRefId): static
    {
        $this->sourceRefId = $sourceRefId;

        return $this;
    }

    public function getTargetRefType(): ?string
    {
        return $this->targetRefType;
    }

    public function setTargetRefType(?string $targetRefType): static
    {
        $this->targetRefType = $targetRefType;

        return $this;
    }

    public function getTargetRefId(): ?int
    {
        return $this->targetRefId;
    }

    public function setTargetRefId(?int $targetRefId): static
    {
        $this->targetRefId = $targetRefId;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeInterface $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): static
    {
        $this->approvedAt = $approvedAt;

        return $this;
    }

    public function getRejectedAt(): ?\DateTimeInterface
    {
        return $this->rejectedAt;
    }

    public function setRejectedAt(?\DateTimeInterface $rejectedAt): static
    {
        $this->rejectedAt = $rejectedAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    /**
     * @return Collection<int, RequestApprovalStep>
     */
    public function getApprovalSteps(): Collection
    {
        return $this->approvalSteps;
    }

    public function addApprovalStep(RequestApprovalStep $approvalStep): static
    {
        if (!$this->approvalSteps->contains($approvalStep)) {
            $this->approvalSteps->add($approvalStep);
            $approvalStep->setRequest($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, RequestEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(RequestEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setRequest($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, RequestWatcher>
     */
    public function getWatchers(): Collection
    {
        return $this->watchers;
    }

    public function addWatcher(RequestWatcher $watcher): static
    {
        if (!$this->watchers->contains($watcher)) {
            $this->watchers->add($watcher);
            $watcher->setRequest($this);
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'source' => $this->source,
            'status' => $this->status,
            'title' => $this->title,
            'summary' => $this->summary,
            'payload' => $this->payload ?? [],
            'revisionNo' => $this->revisionNo,
            'currentStepNo' => $this->currentStepNo,
            'lastRejectReason' => $this->lastRejectReason,
            'requester' => $this->requester?->jsonSerialize(),
            'currentApprover' => $this->currentApprover?->jsonSerialize(),
            'submittedAt' => $this->submittedAt?->format('Y-m-d H:i:s'),
            'approvedAt' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'rejectedAt' => $this->rejectedAt?->format('Y-m-d H:i:s'),
            'cancelledAt' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'requesterDepartment' => $this->requesterDepartmentSnapshot ?? [],
            'requesterPosition' => $this->requesterPositionSnapshot ?? [],
            'targetRefType' => $this->targetRefType,
            'targetRefId' => $this->targetRefId,
        ];
    }
}
