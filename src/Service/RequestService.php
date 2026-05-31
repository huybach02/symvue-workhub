<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\Request\RequestConstant;
use App\DTO\RequestActionDTO;
use App\DTO\RequestCreateDTO;
use App\DTO\RequestUpdateDTO;
use App\Entity\Request;
use App\Entity\RequestApprovalStep;
use App\Entity\RequestEvent;
use App\Entity\RequestWatcher;
use App\Entity\User;
use App\Repository\RequestApprovalStepRepository;
use App\Repository\RequestEventRepository;
use App\Repository\RequestRepository;
use App\Repository\UserPositionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class RequestService
{
    private const REQUEST_PERMISSION_PREFIX = 'requests:';

    public function __construct(
        private readonly RequestRepository $requestRepository,
        private readonly RequestApprovalStepRepository $requestApprovalStepRepository,
        private readonly RequestEventRepository $requestEventRepository,
        private readonly UserRepository $userRepository,
        private readonly RequestTypeService $requestTypeService,
        private readonly MercureService $mercureService,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService $userService,
        private readonly UserPositionRepository $userPositionRepository,
    ) {
    }

    public function getTypes(): array
    {
        return $this->requestTypeService->getTypes();
    }

    public function getAll(array $params, User $currentUser): array
    {
        $allowedTypes = [];

        if (!isAdmin($currentUser)) {
            $allowedTypes = $this->getAllowedRequestTypes($currentUser, 'approve');
            $requestedType = trim((string) ($params['type'] ?? ''));

            if ($requestedType !== '' && !in_array($requestedType, $allowedTypes, true)) {
                throw new \Exception(t('request.error.cannot_access_type'));
            }

            if ($allowedTypes === []) {
                throw new \Exception(t('request.error.cannot_access_type'));
            }
        }

        $result = $this->requestRepository->findWithPaginationForUser(
            $params,
            $currentUser,
            $allowedTypes,
        );
        $result['collection'] = array_map(
            fn(Request $request) => $this->serializeRequest($request, $currentUser),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id, User $currentUser): array
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        canViewRequest($request, $currentUser);

        return $this->serializeRequest($request, $currentUser);
    }

    public function getTimeline(int $id, User $currentUser): array
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        canViewRequest($request, $currentUser);

        $events = $this->requestEventRepository->findTimelineByRequest($request);

        return array_map(
            fn(RequestEvent $event) => $event->jsonSerialize(),
            $events
        );
    }

    // Tạo đề xuất thủ công thông qua UI
    public function create(RequestCreateDTO $dto, User $currentUser): array
    {
        $request = $this->createPendingRequest(
            type: $dto->type,
            requester: $currentUser,
            payload: $dto->payload,
            source: $dto->source ?: RequestConstant::SOURCE_MANUAL,
            watcherIds: $dto->watcherIds,
        );

        $this->notifyApprover($request);

        return $this->serializeRequest($request, $currentUser);
    }

    // Tạo đề xuất tự động bởi hệ thống
    public function createSystemRequest(
        string $type,
        User $requester,
        array $payload,
        ?string $sourceRefType = null,
        ?int $sourceRefId = null,
        array $watcherIds = [],
        ?User $actor = null
    ): Request {
        $request = $this->createPendingRequest(
            type: $type,
            requester: $requester,
            payload: $payload,
            source: RequestConstant::SOURCE_SYSTEM,
            watcherIds: $watcherIds,
            sourceRefType: $sourceRefType,
            sourceRefId: $sourceRefId,
        );

        $this->notifyApprover($request);

        return $request;
    }

    public function update(int $id, RequestUpdateDTO $dto, User $currentUser): array
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        if ($request->getRequester()?->getId() !== $currentUser->getId()) {
            throw new \Exception(t('request.error.cannot_update'));
        }

        if ($request->getStatus() !== RequestConstant::STATUS_REJECTED) {
            throw new \Exception(t('request.error.only_rejected_can_update'));
        }

        $requestType = $request->getType() ?? '';
        $payloadDto = $this->requestTypeService->validatePayload($requestType, $dto->payload);
        $payload = $payloadDto->toArray();
        $approver = $this->userService->findDirectManager($currentUser);
        $primaryPosition = $this->userPositionRepository->findLatestPrimaryPositionByUser($currentUser);
        $revisionNo = $request->getRevisionNo() + 1;
        $fromStatus = $request->getStatus();

        $request
            ->setPayload($payload)
            ->setTitle($this->requestTypeService->buildTitle($requestType, $payloadDto))
            ->setSummary($this->requestTypeService->buildSummary($requestType, $payloadDto))
            ->setStatus(RequestConstant::STATUS_PENDING)
            ->setCurrentApprover($approver)
            ->setRequesterDepartmentSnapshot($primaryPosition->getDepartment()?->jsonSerialize() ?? null)
            ->setRequesterPositionSnapshot($primaryPosition->getPosition()?->jsonSerialize() ?? null)
            ->setRevisionNo($revisionNo)
            ->setSubmittedAt(new \DateTime())
            ->setRejectedAt(null)
            ->setLastRejectReason(null);

        $approvalStep = $this->createApprovalStep($request, $approver, 1, $revisionNo);
        $this->entityManager->persist($approvalStep);

        $this->createEvent($request, RequestConstant::EVENT_EDITED, $currentUser, $fromStatus, $fromStatus, null, $payload);
        $this->createEvent($request, RequestConstant::EVENT_RESUBMITTED, $currentUser, $fromStatus, RequestConstant::STATUS_PENDING, null, $payload);

        $this->entityManager->flush();

        $this->notifyApprover($request);

        return $this->serializeRequest($request, $currentUser);
    }

    public function approve(int $id, RequestActionDTO $dto, User $currentUser): array
    {
        $request = $this->getPendingRequestForApprover($id, $currentUser);
        $approvalStep = $this->getCurrentApprovalStep($request);
        $fromStatus = $request->getStatus();
        $now = new \DateTime();

        $approvalStep
            ->setStatus(RequestConstant::STATUS_APPROVED)
            ->setDecisionComment(trim((string) $dto->comment) ?: null)
            ->setActedAt($now);

        $request
            ->setStatus(RequestConstant::STATUS_APPROVED)
            ->setApprovedAt($now)
            ->setUpdatedBy($currentUser->getId());

        $this->createEvent(
            $request,
            RequestConstant::EVENT_APPROVED,
            $currentUser,
            $fromStatus,
            RequestConstant::STATUS_APPROVED,
            trim((string) $dto->comment) ?: null,
            $request->getPayload()
        );

        $this->requestTypeService->handleApproved($request);
        // $this->createEvent(
        //     $request,
        //     RequestConstant::EVENT_EFFECT_APPLIED,
        //     $currentUser,
        //     RequestConstant::STATUS_APPROVED,
        //     RequestConstant::STATUS_APPROVED,
        //     null,
        //     $request->getPayload(),
        //     [
        //         'targetRefType' => $request->getTargetRefType(),
        //         'targetRefId' => $request->getTargetRefId(),
        //     ]
        // );

        $this->entityManager->flush();

        $this->notifyRequesterApproved($request, trim((string) $dto->comment) ?: null);

        return $this->serializeRequest($request, $currentUser);
    }

    public function reject(int $id, RequestActionDTO $dto, User $currentUser): array
    {
        $comment = trim((string) $dto->comment);
        if ($comment === '') {
            throw new \Exception(t('request.error.reject_reason_required'));
        }

        $request = $this->getPendingRequestForApprover($id, $currentUser);
        $approvalStep = $this->getCurrentApprovalStep($request);
        $fromStatus = $request->getStatus();
        $now = new \DateTime();

        $approvalStep
            ->setStatus(RequestConstant::STATUS_REJECTED)
            ->setDecisionComment($comment)
            ->setActedAt($now);

        $request
            ->setStatus(RequestConstant::STATUS_REJECTED)
            ->setRejectedAt($now)
            ->setLastRejectReason($comment)
            ->setUpdatedBy($currentUser->getId());

        $this->createEvent(
            $request,
            RequestConstant::EVENT_REJECTED,
            $currentUser,
            $fromStatus,
            RequestConstant::STATUS_REJECTED,
            $comment,
            $request->getPayload()
        );

        $this->entityManager->flush();

        $this->notifyRequesterRejected($request, $comment);

        return $this->serializeRequest($request, $currentUser);
    }

    public function cancel(int $id, User $currentUser): array
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        if ($request->getRequester()?->getId() !== $currentUser->getId()) {
            throw new \Exception(t('request.error.cannot_cancel'));
        }

        if (!in_array($request->getStatus(), [RequestConstant::STATUS_PENDING, RequestConstant::STATUS_REJECTED], true)) {
            throw new \Exception(t('request.error.only_pending_or_rejected_can_cancel'));
        }

        $fromStatus = $request->getStatus();
        $request
            ->setStatus(RequestConstant::STATUS_CANCELLED)
            ->setCancelledAt(new \DateTime())
            ->setUpdatedBy($currentUser->getId());

        if ($fromStatus === RequestConstant::STATUS_PENDING) {
            $approvalStep = $this->getCurrentApprovalStep($request);
            $approvalStep
                ->setStatus(RequestConstant::STATUS_CANCELLED)
                ->setDecisionComment(t('request.event_comment.requester_cancelled'))
                ->setActedAt(new \DateTime());
        }

        $this->createEvent(
            $request,
            RequestConstant::EVENT_CANCELLED,
            $currentUser,
            $fromStatus,
            RequestConstant::STATUS_CANCELLED,
            null,
            $request->getPayload()
        );

        $this->entityManager->flush();

        return $this->serializeRequest($request, $currentUser);
    }

    public function delete(int $id, User $currentUser): void
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        if ($request->getRequester()?->getId() !== $currentUser->getId()) {
            throw new \Exception(t('request.error.cannot_delete'));
        }

        if (!in_array($request->getStatus(), [RequestConstant::STATUS_REJECTED, RequestConstant::STATUS_CANCELLED], true)) {
            throw new \Exception(t('request.error.only_rejected_or_cancelled_can_delete'));
        }

        $this->createEvent(
            $request,
            RequestConstant::EVENT_DELETED,
            $currentUser,
            $request->getStatus(),
            $request->getStatus(),
            null,
            $request->getPayload()
        );

        $this->entityManager->flush();
        $this->entityManager->remove($request);
        $this->entityManager->flush();
    }

    private function createPendingRequest(
        string $type,
        User $requester,
        array $payload,
        string $source,
        array $watcherIds = [],
        ?string $sourceRefType = null,
        ?int $sourceRefId = null,
    ): Request {
        $payloadDto = $this->requestTypeService->validatePayload($type, $payload);
        $payloadData = $payloadDto->toArray();
        $approver = $this->userService->findDirectManager($requester);
        $primaryPosition = $this->userPositionRepository->findLatestPrimaryPositionByUser($requester);
        $now = new \DateTime();

        $request = new Request();
        $request
            ->setCode(generateCode('REQ'))
            ->setType($type)
            ->setSource($source)
            ->setStatus(RequestConstant::STATUS_PENDING)
            ->setRequester($requester)
            ->setCurrentApprover($approver)
            ->setRequesterDepartmentSnapshot($primaryPosition?->getDepartment()?->jsonSerialize() ?? null)
            ->setRequesterPositionSnapshot($primaryPosition?->getPosition()?->jsonSerialize() ?? null)
            ->setCurrentStepNo(1)
            ->setRevisionNo(1)
            ->setTitle($this->requestTypeService->buildTitle($type, $payloadDto))
            ->setSummary($this->requestTypeService->buildSummary($type, $payloadDto))
            ->setPayload($payloadData)
            ->setSourceRefType($sourceRefType)
            ->setSourceRefId($sourceRefId)
            ->setSubmittedAt($now);

        $this->entityManager->persist($request);

        $approvalStep = $this->createApprovalStep($request, $approver, 1, 1);
        $this->entityManager->persist($approvalStep);

        $this->createEvent(
            $request,
            RequestConstant::EVENT_SUBMITTED,
            $requester,
            null,
            RequestConstant::STATUS_PENDING,
            null,
            $payloadData
        );
        $this->createWatchers($request, $watcherIds);

        $this->entityManager->flush();

        return $request;
    }

    private function createApprovalStep(Request $request, User $approver, int $stepNo, int $revisionNo): RequestApprovalStep
    {
        $approvalStep = new RequestApprovalStep();
        $approvalStep
            ->setRequest($request)
            ->setApprover($approver)
            ->setStepNo($stepNo)
            ->setRevisionNo($revisionNo)
            ->setStatus(RequestConstant::STATUS_PENDING);

        return $approvalStep;
    }

    private function getAllowedRequestTypes(User $currentUser, string $action): array
    {
        if (isAdmin($currentUser)) {
            return RequestConstant::allTypes();
        }

        return getPermissionSuffixesByPrefix(
            $this->userService->getUserPermission($currentUser->getId()),
            self::REQUEST_PERMISSION_PREFIX,
            $action,
        );
    }

    private function createWatchers(Request $request, array $watcherIds): void
    {
        foreach ($watcherIds as $watcherId) {
            $watcherUser = $this->userRepository->find($watcherId);
            if (!$watcherUser instanceof User) {
                continue;
            }

            if ($watcherUser->getId() === $request->getRequester()?->getId()) {
                continue;
            }

            $watcher = new RequestWatcher();
            $watcher
                ->setRequest($request)
                ->setUser($watcherUser)
                ->setCreatedAt(new \DateTime());

            $request->addWatcher($watcher);
            $this->entityManager->persist($watcher);
        }
    }

    private function createEvent(
        Request $request,
        string $eventType,
        ?User $actor,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment,
        ?array $payloadSnapshot,
        ?array $meta = null
    ): void {
        $event = new RequestEvent();
        $event
            ->setRequest($request)
            ->setEventType($eventType)
            ->setActor($actor)
            ->setActorType($actor ? 'user' : 'system')
            ->setStepNo($request->getCurrentStepNo())
            ->setRevisionNo($request->getRevisionNo())
            ->setFromStatus($fromStatus)
            ->setToStatus($toStatus)
            ->setComment($comment)
            ->setPayloadSnapshot($payloadSnapshot)
            ->setMeta($meta)
            ->setCreatedAt(new \DateTime());

        $request->addEvent($event);
        $this->entityManager->persist($event);
    }

    private function getPendingRequestForApprover(int $id, User $currentUser): Request
    {
        $request = $this->requestRepository->find($id);
        if (!$request) {
            throw new \Exception(t('error.not_found'));
        }

        if ($request->getCurrentApprover()?->getId() !== $currentUser->getId()) {
            throw new \Exception(t('request.error.not_current_approver'));
        }

        if ($request->getStatus() !== RequestConstant::STATUS_PENDING) {
            throw new \Exception(t('request.error.request_not_pending'));
        }

        return $request;
    }

    private function getCurrentApprovalStep(Request $request): RequestApprovalStep
    {
        $approvalStep = $this->requestApprovalStepRepository->findCurrentStep($request);

        if (!$approvalStep) {
            throw new \Exception(t('request.error.current_approval_step_not_found'));
        }

        return $approvalStep;
    }

    private function serializeRequest(Request $request, User $currentUser): array
    {
        $data = $request->jsonSerialize();
        $data['permissions'] = buildRequestPermissions($request, $currentUser);

        return $data;
    }

    private function notifyApprover(Request $request): void
    {
        $approver = $request->getCurrentApprover();
        $requester = $request->getRequester();

        if (!$approver || !$requester) {
            return;
        }

        $this->mercureService->thongBaoCaNhan(
            $requester->getId(),
            $approver->getId(),
            t('request.notification.pending'),
            t('request.notification.pending_body', [
                '%requesterName%' => (string) $requester->getName(),
                '%requestTitle%' => (string) $request->getTitle(),
            ]),
            'warning',
            new \DateTime(),
            '/system/requests'
        );
    }

    private function notifyRequesterRejected(Request $request, string $comment): void
    {
        $approver = $request->getCurrentApprover();
        $requester = $request->getRequester();

        if (!$approver || !$requester) {
            return;
        }

        $this->mercureService->thongBaoCaNhan(
            $approver->getId(),
            $requester->getId(),
            t('request.notification.rejected'),
            t('request.notification.rejected_body', [
                '%approverName%' => (string) $approver->getName(),
                '%reason%' => $comment,
            ]),
            'error',
            new \DateTime(),
            '/system/requests'
        );
    }

    private function notifyRequesterApproved(Request $request, ?string $comment): void
    {
        $approver = $request->getCurrentApprover();
        $requester = $request->getRequester();

        if (!$approver || !$requester) {
            return;
        }

        $body = t('request.notification.approved_body', [
            '%approverName%' => (string) $approver->getName(),
        ]);
        if ($comment) {
            $body .= ' ' . t('request.notification.note_suffix', [
                '%comment%' => $comment,
            ]);
        }

        $this->mercureService->thongBaoCaNhan(
            $approver->getId(),
            $requester->getId(),
            t('request.notification.approved'),
            $body,
            'success',
            new \DateTime(),
            '/system/requests'
        );
    }
}
