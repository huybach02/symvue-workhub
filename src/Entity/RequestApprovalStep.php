<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RequestApprovalStepRepository;
use App\Traits\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RequestApprovalStepRepository::class)]
#[ORM\Table(name: 'request_approval_step')]
class RequestApprovalStep
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'approvalSteps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Request $request = null;

    #[ORM\Column(options: ['comment' => 'Số thứ tự bước duyệt'])]
    private int $stepNo = 1;

    #[ORM\Column(options: ['comment' => 'Lần gửi đề xuất mà bước duyệt này tương ứng'])]
    private int $revisionNo = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => 'Người chịu trách nhiệm duyệt ở bước này'])]
    private ?User $approver = null;

    #[ORM\Column(length: 30, options: ['comment' => 'Trạng thái của bước duyệt: pending, approved, rejected, cancelled'])]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Ghi chú hoặc lý do xử lý của người duyệt'])]
    private ?string $decisionComment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Thời điểm người duyệt thực hiện hành động ở bước này'])]
    private ?\DateTimeInterface $actedAt = null;

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

    public function getStepNo(): int
    {
        return $this->stepNo;
    }

    public function setStepNo(int $stepNo): static
    {
        $this->stepNo = $stepNo;

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

    public function getApprover(): ?User
    {
        return $this->approver;
    }

    public function setApprover(User $approver): static
    {
        $this->approver = $approver;

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

    public function getDecisionComment(): ?string
    {
        return $this->decisionComment;
    }

    public function setDecisionComment(?string $decisionComment): static
    {
        $this->decisionComment = $decisionComment;

        return $this;
    }

    public function getActedAt(): ?\DateTimeInterface
    {
        return $this->actedAt;
    }

    public function setActedAt(?\DateTimeInterface $actedAt): static
    {
        $this->actedAt = $actedAt;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'=> $this->getId(),
            'request_id'=> $this->getRequest()->getId(),
            'step_no'=> $this->getStepNo(),
            'revision_no'=> $this->getRevisionNo(),
            'approver_id'=> $this->getApprover()->getId(),
            'status'=> $this->getStatus(),
            'decision_comment'=> $this->getDecisionComment(),
            'acted_at'=> $this->getActedAt(),
        ];
    }
}
