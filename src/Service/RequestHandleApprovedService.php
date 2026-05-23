<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\Request\RequestConstant;
use App\Entity\LeaveSchedule;
use App\Entity\Request;
use App\Repository\LeaveScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;

class RequestHandleApprovedService
{
    public function __construct(
        private readonly LeaveScheduleRepository $leaveScheduleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handleLeaveRequestApproved(Request $request): void
    {
        $payload = $request->getPayload() ?? [];
        $start = \DateTime::createFromFormat('Y-m-d H:i:s', $payload['startDate'] . ' 00:00:00');
        $end = \DateTime::createFromFormat('Y-m-d H:i:s', $payload['endDate'] . ' 23:59:59');

        if (!$start || !$end) {
            throw new \Exception(t('request.error.invalid_leave_payload_for_apply'));
        }

        $leaveSchedule = null;
        if ($request->getTargetRefType() === 'leave_schedule' && $request->getTargetRefId()) {
            $leaveSchedule = $this->leaveScheduleRepository->find($request->getTargetRefId());
        }

        if (!$leaveSchedule) {
            $leaveSchedule = new LeaveSchedule();
            $leaveSchedule->setMember($request->getRequester());
            $leaveSchedule->setCreatedBy($request->getRequester()?->getId());
            $this->entityManager->persist($leaveSchedule);
        }

        $leaveSchedule->setMember($request->getRequester());
        $leaveSchedule->setType((string) ($payload['leaveType'] ?? t('request.payload.leave_type_default')));
        $leaveSchedule->setStartDatetime($start);
        $leaveSchedule->setEndDatetime($end);
        $leaveSchedule->setReason((string) ($payload['reason'] ?? ''));
        $leaveSchedule->setStatus(RequestConstant::STATUS_APPROVED);
        $leaveSchedule->setUpdatedBy($request->getCurrentApprover()?->getId());

        $this->entityManager->flush();

        $request->setTargetRefType('leave_schedule');
        $request->setTargetRefId($leaveSchedule->getId());
    }
}
