<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\Request\RequestConstant;
use App\DTO\LeaveRequestPayloadDTO;
use App\Entity\LeaveSchedule;
use App\Entity\Request;
use App\Entity\User;
use App\Repository\LeaveScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\UserPositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestTypeService
{
    public function __construct(
        private readonly UserPositionRepository $userPositionRepository,
        private readonly UserRepository $userRepository,
        private readonly LeaveScheduleRepository $leaveScheduleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DenormalizerInterface $denormalizer,
        private readonly ValidatorInterface $validator,
        private readonly RequestHandleApprovedService $requestHandleApprovedService,
    ) {
    }

    public function getTypes(): array
    {
        return RequestConstant::typeOptions();
    }

    public function validatePayload(string $type, array $payload): object
    {
        return match ($type) {
            RequestConstant::TYPE_LEAVE => $this->mapAndValidatePayloadDto(
                $payload,
                LeaveRequestPayloadDTO::class,
                t('request.error.invalid_leave_payload_format')
            ),
            default => throw new \Exception(t('request.error.unsupported_type')),
        };
    }

    public function buildTitle(string $type, object $payload): string
    {
        return match ($type) {
            RequestConstant::TYPE_LEAVE => t(
                'request.title.leave_range',
                [
                    '%startDate%' => (string) $payload->startDate,
                    '%endDate%' => (string) $payload->endDate,
                ]
            ),
            default => t('request.title.default'),
        };
    }

    public function buildSummary(string $type, object $payload): ?string
    {
        return match ($type) {
            RequestConstant::TYPE_LEAVE => trim(sprintf(
                '%s | %s -> %s',
                $payload->leaveType ?? t('request.payload.leave_type_default'),
                $payload->startDate,
                $payload->endDate
            )),
            default => null,
        };
    }

    public function handleApproved(Request $request): void
    {
        match ($request->getType()) {
            RequestConstant::TYPE_LEAVE => $this->requestHandleApprovedService->handleLeaveRequestApproved($request),
            default => null,
        };
    }

    private function mapAndValidatePayloadDto(
        array $payload,
        string $dtoClass,
        string $invalidFormatMessage
    ): object {
        try {
            $dto = $this->denormalizer->denormalize($payload, $dtoClass);
        } catch (\Throwable $exception) {
            throw new \Exception($invalidFormatMessage);
        }

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw new \Exception(implode(', ', array_unique($errors)));
        }

        return $dto;
    }
}
