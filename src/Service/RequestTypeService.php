<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\Request\RequestConstant;
use App\DTO\LeaveRequestPayloadDTO;
use App\DTO\ProductionRequestPayloadDTO;
use App\DTO\StockInRequestPayloadDTO;
use App\Entity\LeaveSchedule;
use App\Entity\Request;
use App\Entity\User;
use App\Entity\MerchandiseProvider;
use App\Entity\MerchandiseProviderUnit;
use App\Entity\MerchandiseUnit;
use App\Repository\LeaveScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\UserPositionRepository;
use App\Repository\ProviderRepository;
use App\Repository\MerchandiseRepository;
use App\Repository\UnitRepository;
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
        private readonly ProviderRepository $providerRepository,
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly UnitRepository $unitRepository,
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
            RequestConstant::TYPE_STOCK_IN => $this->mapAndValidatePayloadDto(
                $payload,
                StockInRequestPayloadDTO::class,
                t('request.error.invalid_stock_in_payload_format')
            ),
            RequestConstant::TYPE_PRODUCTION => $this->mapAndValidatePayloadDto(
                $payload,
                ProductionRequestPayloadDTO::class,
                t('request.error.invalid_production_payload_format')
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
            RequestConstant::TYPE_STOCK_IN => t('request.title.stock_in_default'),
            RequestConstant::TYPE_PRODUCTION => t('request.title.production_default'),
            default => t('request.title.default'),
        };
    }

    public function buildSummary(string $type, object $payload): ?string
    {
        return match ($type) {
            RequestConstant::TYPE_LEAVE => trim(sprintf(
                '%s | %s -> %s',
                t('request.payload.leave_type_default'),
                $payload->startDate,
                $payload->endDate
            )),
            RequestConstant::TYPE_STOCK_IN => trim(sprintf(
                '%s | %d %s',
                t('request.payload.stock_in_default'),
                count($payload->providers ?? []),
                t('request.payload.stock_in_provider_count')
            )),
            RequestConstant::TYPE_PRODUCTION => trim(sprintf(
                '%s | %d %s',
                t('request.payload.production_default'),
                count($payload->items ?? []),
                t('request.payload.production_item_count')
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

    // Snapshots một số data để lưu trữ và hiển thị trong detail
    public function enrichPayload(string $type, array $payload): array
    {
        return match ($type) {
            RequestConstant::TYPE_STOCK_IN => $this->enrichStockInPayloadNames($payload),
            RequestConstant::TYPE_PRODUCTION => $this->enrichProductionPayloadNames($payload),
            default => $payload,
        };
    }

    private function enrichStockInPayloadNames(array $payload): array
    {
        if (empty($payload['providers']) || !is_array($payload['providers'])) {
            return $payload;
        }

        $merchandiseProviderRepo = $this->entityManager->getRepository(MerchandiseProvider::class);
        $merchandiseProviderUnitRepo = $this->entityManager->getRepository(MerchandiseProviderUnit::class);
        $merchandiseUnitRepo = $this->entityManager->getRepository(MerchandiseUnit::class);

        foreach ($payload['providers'] as &$providerGroup) {
            $providerId = $providerGroup['providerId'] ?? null;
            if ($providerId) {
                $provider = $this->providerRepository->find($providerId);
                $providerGroup['providerName'] = $provider?->getName() ?? '';
                if ($provider) {
                    $providerGroup['provider'] = [
                        'id' => $provider->getId(),
                        'code' => $provider->getCode(),
                        'name' => $provider->getName(),
                        'phone' => $provider->getPhone(),
                        'email' => $provider->getEmail(),
                        'address' => $provider->getAddress(),
                        'taxNumber' => $provider->getTaxNumber(),
                    ];
                }
            }

            if (!empty($providerGroup['items']) && is_array($providerGroup['items'])) {
                foreach ($providerGroup['items'] as &$item) {
                    $merchandiseId = $item['merchandiseId'] ?? null;
                    if ($merchandiseId) {
                        $merchandise = $this->merchandiseRepository->find($merchandiseId);
                        $item['merchandiseName'] = $merchandise ? sprintf('[%s] %s', $merchandise->getCode(), $merchandise->getName()) : '';
                        if ($merchandise) {
                            $item['merchandise'] = [
                                'id' => $merchandise->getId(),
                                'code' => $merchandise->getCode(),
                                'name' => $merchandise->getName(),
                                'type' => $merchandise->getType(),
                                'isSingleUnit' => $merchandise->isSingleUnit(),
                                'baseUnitId' => $merchandise->getBaseUnit()?->getId(),
                                'baseUnitName' => $merchandise->getBaseUnit()?->getName() ?? '',
                            ];
                        }
                    }

                    $unitId = $item['unitId'] ?? null;
                    if ($unitId) {
                        $unit = $this->unitRepository->find($unitId);
                        $unitName = $unit?->getName() ?? '';

                        if ($merchandiseId) {
                            if ($providerId) {
                                $mProvider = $merchandiseProviderRepo->findOneBy([
                                    'merchandise' => $merchandiseId,
                                    'provider' => $providerId,
                                ]);
                                if ($mProvider) {
                                    $providerUnit = $merchandiseProviderUnitRepo->findOneBy([
                                        'merchandiseProvider' => $mProvider->getId(),
                                        'unit' => $unitId,
                                    ]);
                                    if ($providerUnit && $providerUnit->getLabel()) {
                                        $unitName = $providerUnit->getLabel();
                                    }
                                }
                            }

                            if ($unitName === ($unit?->getName() ?? '')) {
                                $mUnit = $merchandiseUnitRepo->findOneBy([
                                    'merchandise' => $merchandiseId,
                                    'unit' => $unitId,
                                ]);
                                if ($mUnit && $mUnit->getLabel()) {
                                    $unitName = $mUnit->getLabel();
                                }
                            }
                        }

                        $item['unitName'] = $unitName;
                    }

                    if ($merchandiseId && $unitId && $providerId) {
                        $mProvider = $merchandiseProviderRepo->findOneBy([
                            'merchandise' => $merchandiseId,
                            'provider' => $providerId,
                        ]);

                        $factorToBase = '1.0000';
                        if ($mProvider) {
                            $providerUnit = $merchandiseProviderUnitRepo->findOneBy([
                                'merchandiseProvider' => $mProvider->getId(),
                                'unit' => $unitId,
                            ]);
                            if ($providerUnit) {
                                $factorToBase = $providerUnit->getFactorToBase();
                            } else {
                                $mUnit = $merchandiseUnitRepo->findOneBy([
                                    'merchandise' => $merchandiseId,
                                    'unit' => $unitId,
                                ]);
                                if ($mUnit) {
                                    $factorToBase = $mUnit->getFactorToBase();
                                }
                            }
                        }
                        $item['factorToBase'] = (float)$factorToBase;
                    }
                }
            }
        }

        return $payload;
    }

    private function enrichProductionPayloadNames(array $payload): array
    {
        if (empty($payload['items']) || !is_array($payload['items'])) {
            return $payload;
        }

        foreach ($payload['items'] as &$item) {
            $finishedProductId = $item['finishedProductId'] ?? null;
            if ($finishedProductId) {
                $product = $this->merchandiseRepository->find($finishedProductId);
                if ($product) {
                    $item['finishedProductName'] = sprintf('[%s] %s', $product->getCode(), $product->getName());
                }
            }

            $outputUnitId = $item['outputUnitId'] ?? null;
            if ($outputUnitId) {
                $unit = $this->unitRepository->find($outputUnitId);
                if ($unit) {
                    $item['outputUnitName'] = $unit->getName();
                }
            }

            if (!empty($item['materials']) && is_array($item['materials'])) {
                foreach ($item['materials'] as &$material) {
                    $ingredientId = $material['ingredientId'] ?? null;
                    if ($ingredientId) {
                        $ingredient = $this->merchandiseRepository->find($ingredientId);
                        if ($ingredient) {
                            $material['ingredientName'] = sprintf('[%s] %s', $ingredient->getCode(), $ingredient->getName());
                        }
                    }

                    $unitId = $material['unitId'] ?? null;
                    if ($unitId) {
                        $mUnit = $this->unitRepository->find($unitId);
                        if ($mUnit) {
                            $material['unitName'] = $mUnit->getName();
                        }
                    }
                }
            }
        }

        return $payload;
    }
}
