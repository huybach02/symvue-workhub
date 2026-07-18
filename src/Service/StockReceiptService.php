<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\StockReceiptStatus;
use App\Class\StockReceiptProviderStatus;
use App\Class\FulfillmentStatus;
use App\Class\StockReceiptEventType;
use App\Class\ActorType;
use App\Class\InventoryLotStatus;
use App\Class\Request\RequestConstant;
use App\Class\ShortageResolution;
use App\Class\Warehouse\Warehoue as WarehouseHelper;
use App\DTO\StockReceiptDTO;
use App\DTO\StockReceiptInspectDTO;
use App\Entity\StockReceipt;
use App\Entity\Request;
use App\Entity\Warehouse;
use App\Entity\Provider;
use App\Entity\Merchandise;
use App\Entity\Unit;
use App\Entity\StockReceiptProvider;
use App\Entity\StockReceiptItem;
use App\Entity\StockReceiptEvent;
use App\Entity\StockReceiptItemLot;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\InventoryBalance;
use App\Class\InventoryMovementType;
use App\Entity\MerchandiseProvider;
use App\Entity\MerchandiseProviderUnit;
use App\Entity\MerchandiseUnit;
use App\Entity\User;
use App\Repository\StockReceiptRepository;
use App\Repository\MerchandiseRepository;
use App\Repository\ProviderRepository;
use App\Repository\UnitRepository;
use App\Repository\MerchandiseProviderRepository;
use App\Repository\MerchandiseProviderUnitRepository;
use App\Repository\MerchandiseUnitRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockReceiptService
{
    private const QUANTITY_SCALE = 6;
    private const FACTOR_SCALE = 8;

    public function __construct(
        private readonly StockReceiptRepository $stockReceiptRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseHelper $warehouseHelper,
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly ProviderRepository $providerRepository,
        private readonly UnitRepository $unitRepository,
        private readonly MerchandiseProviderRepository $merchandiseProviderRepository,
        private readonly MerchandiseProviderUnitRepository $merchandiseProviderUnitRepository,
        private readonly MerchandiseUnitRepository $merchandiseUnitRepository,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->stockReceiptRepository->createQueryBuilder("e");

        $result = FilterWithPagination::findWithPagination($qb, $params, "e");

        $result["collection"] = array_map(
            fn(StockReceipt $item) => $item->jsonSerialize(),
            $result["collection"],
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->stockReceiptRepository
            ->createQueryBuilder("e")
            ->andWhere("e.status = 1");
        $result = FilterWithPagination::findWithPagination($qb, $params, "e");

        return array_map(
            fn(StockReceipt $item) => [
                "label" => $item->getCode(),
                "value" => $item->getId(),
            ],
            $result["collection"],
        );
    }

    public function findById(int $id): array
    {
        $item = $this->stockReceiptRepository->find($id);
        if (!$item) {
            throw new \Exception(t("error.not_found"));
        }

        return $item->jsonSerialize();
    }

    public function create(StockReceiptDTO $dto, User $currentUser): array
    {
        $this->entityManager->beginTransaction();
        try {
            $request = $this->entityManager
                ->getRepository(Request::class)
                ->find(
                    $dto->requestId,
                    \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
                );

            if (
                !$request ||
                $request->getType() !== RequestConstant::TYPE_STOCK_IN ||
                $request->getStatus() !== RequestConstant::STATUS_APPROVED
            ) {
                throw new \Exception(
                    "Đề xuất không hợp lệ hoặc chưa được duyệt",
                );
            }
            if ($request->getRequester()?->getId() !== $currentUser->getId()) {
                throw new \Exception("Bạn không phải là người tạo đề xuất này");
            }
            if (
                $this->stockReceiptRepository->findOneBy([
                    "request" => $request,
                    "supplementNo" => 0,
                ])
            ) {
                throw new \Exception(
                    "Đề xuất này đã được tạo phiếu nhập kho gốc",
                );
            }

            $warehouse = $this->warehouseHelper->getReceivingWarehouse();
            $receiptCode = generateSequentialCode(
                $this->entityManager,
                "PNK",
                "stock_receipt",
            );

            $stockReceipt = new StockReceipt();
            $stockReceipt->setCode($receiptCode);
            $stockReceipt->setRequest($request);
            $stockReceipt->setWarehouse($warehouse);
            $stockReceipt->setStatus(StockReceiptStatus::Created->value);
            $stockReceipt->setFulfillmentStatus(
                FulfillmentStatus::Pending->value,
            );
            $stockReceipt->setTitle("Đề xuất nhập kho ngày " . date("d/m/Y"));
            $stockReceipt->setWarehouseSnapshot([
                "id" => $warehouse->getId(),
                "code" => $warehouse->getCode(),
                "name" => $warehouse->getName(),
                "branch" => $warehouse->getBranch()
                    ? [
                        "id" => $warehouse->getBranch()->getId(),
                        "name" => $warehouse->getBranch()->getName(),
                        "code" => $warehouse->getBranch()->getCode(),
                    ]
                    : null,
            ]);
            $this->entityManager->persist($stockReceipt);

            $payload = $request->getPayload() ?? [];
            $sortOrder = 0;

            foreach ($payload["providers"] ?? [] as $providerGroup) {
                $providerId = $providerGroup["providerId"] ?? null;
                $provider = $providerId
                    ? $this->providerRepository->find($providerId)
                    : null;
                if (!$provider) {
                    throw new \Exception("Nhà cung cấp không tồn tại");
                }

                $receiptProvider = new StockReceiptProvider();
                $receiptProvider->setReceipt($stockReceipt);
                $receiptProvider->setProvider($provider);
                $receiptProvider->setStatus(
                    StockReceiptProviderStatus::Created->value,
                );
                $receiptProvider->setFulfillmentStatus(
                    FulfillmentStatus::Pending->value,
                );
                $receiptProvider->setProviderSnapshot([
                    "id" => $provider->getId(),
                    "code" => $provider->getCode(),
                    "name" => $provider->getName(),
                    "phone" => $provider->getPhone(),
                    "address" => $provider->getAddress(),
                    "taxNumber" => $provider->getTaxNumber(),
                ]);
                $this->entityManager->persist($receiptProvider);

                foreach ($providerGroup["items"] ?? [] as $itemData) {
                    $merchandise = $this->merchandiseRepository->find(
                        $itemData["merchandiseId"] ?? 0,
                    );
                    $expectedUnit = $this->unitRepository->find(
                        $itemData["unitId"] ?? 0,
                    );
                    if (!$merchandise || !$expectedUnit) {
                        throw new \Exception(
                            "Nguyên liệu hoặc đơn vị tính không tồn tại",
                        );
                    }

                    $factorToBase = "1.00000000";
                    $expectedUnitLabel = $expectedUnit->getName();
                    $mProvider = $this->merchandiseProviderRepository->findOneBy(
                        [
                            "merchandise" => $merchandise,
                            "provider" => $provider,
                        ],
                    );

                    if ($mProvider) {
                        $providerUnit = $this->merchandiseProviderUnitRepository->findOneBy(
                            [
                                "merchandiseProvider" => $mProvider,
                                "unit" => $expectedUnit,
                            ],
                        );
                        if ($providerUnit) {
                            $factorToBase =
                                $providerUnit->getFactorToBase() ??
                                "1.00000000";
                            $expectedUnitLabel =
                                $providerUnit->getLabel() ?: $expectedUnitLabel;
                        } else {
                            $mUnit = $this->merchandiseUnitRepository->findOneBy(
                                [
                                    "merchandise" => $merchandise,
                                    "unit" => $expectedUnit,
                                ],
                            );
                            if ($mUnit) {
                                $factorToBase =
                                    $mUnit->getFactorToBase() ?? "1.00000000";
                                $expectedUnitLabel =
                                    $mUnit->getLabel() ?: $expectedUnitLabel;
                            }
                        }
                    }

                    $expectedQuantity = (string) ($itemData["quantity"] ?? "0");
                    $unitPrice = (string) ($itemData["price"] ?? "0");
                    $expectedBaseQuantity = bcmul(
                        $expectedQuantity,
                        $factorToBase,
                        6,
                    );
                    $baseUnitCost =
                        bccomp($factorToBase, "0", 8) === 0
                            ? "0.0000"
                            : bcdiv($unitPrice, $factorToBase, 4);

                    $baseUnit = $merchandise->getBaseUnit();
                    if (!$baseUnit) {
                        throw new \Exception(
                            "Nguyên liệu chưa cấu hình đơn vị cơ bản",
                        );
                    }

                    $receiptItem = new StockReceiptItem();
                    $receiptItem->setReceiptProvider($receiptProvider);
                    $receiptItem->setSourceRequestLineId(
                        (string) $itemData["lineId"],
                    );
                    $receiptItem->setMerchandise($merchandise);
                    $receiptItem->setMerchandiseCodeSnapshot(
                        $merchandise->getCode(),
                    );
                    $receiptItem->setMerchandiseNameSnapshot(
                        $merchandise->getName(),
                    );
                    $receiptItem->setExpectedQuantity($expectedQuantity);
                    $receiptItem->setExpectedUnit($expectedUnit);
                    $receiptItem->setExpectedUnitLabelSnapshot(
                        $expectedUnitLabel,
                    );
                    $receiptItem->setExpectedFactorToBase($factorToBase);
                    $receiptItem->setExpectedBaseQuantity(
                        $expectedBaseQuantity,
                    );
                    $receiptItem->setBaseUnit($baseUnit);
                    $receiptItem->setBaseUnitLabelSnapshot(
                        $baseUnit->getName(),
                    );
                    $receiptItem->setUnitPriceSnapshot($unitPrice);
                    $receiptItem->setBaseUnitCostSnapshot($baseUnitCost);
                    $receiptItem->setCurrency(
                        trim((string) ($itemData["currency"] ?? "VND")) ?:
                        "VND",
                    );
                    $receiptItem->setSortOrder($sortOrder++);
                    $receiptItem->setNote(
                        trim((string) ($itemData["note"] ?? "")),
                    );
                    $this->entityManager->persist($receiptItem);
                }
            }

            $receiptEvent = new StockReceiptEvent();
            $receiptEvent->setReceipt($stockReceipt);
            $receiptEvent->setEventType(StockReceiptEventType::Created->value);
            $receiptEvent->setToStatus(StockReceiptStatus::Created->value);
            $receiptEvent->setActor($currentUser);
            $receiptEvent->setActorType(ActorType::User->value);
            $receiptEvent->setComment(
                "Tạo phiếu nhập kho từ đề xuất #" . $request->getCode(),
            );
            $this->entityManager->persist($receiptEvent);

            $request->setTargetRefType("stock_receipt");
            $this->entityManager->flush();

            $request->setTargetRefId($stockReceipt->getId());
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $stockReceipt->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    public function update(int $id, StockReceiptDTO $dto): array
    {
        $item = $this->stockReceiptRepository->find($id);
        if (!$item) {
            throw new \Exception(t("error.not_found"));
        }

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->stockReceiptRepository->find($id);
        if (!$item) {
            throw new \Exception(t("error.not_found"));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function updateProviderStatus(
        int $providerId,
        string $status,
        User $currentUser,
    ): array {
        $this->entityManager->beginTransaction();
        try {
            $provider = $this->entityManager
                ->getRepository(StockReceiptProvider::class)
                ->find($providerId);
            if (!$provider) {
                throw new \Exception(
                    "Nhà cung cấp trong phiếu nhập kho không tồn tại",
                );
            }

            $statusOrder = [
                StockReceiptProviderStatus::Created->value,
                StockReceiptProviderStatus::AwaitingShipment->value,
                StockReceiptProviderStatus::InTransit->value,
                StockReceiptProviderStatus::Arrived->value,
                StockReceiptProviderStatus::Inspecting->value,
            ];

            $currentStatus = $provider->getStatus();
            $currentIndex = array_search($currentStatus, $statusOrder);
            $targetIndex = array_search($status, $statusOrder);

            if (
                $currentIndex === false ||
                $targetIndex === false ||
                $targetIndex !== $currentIndex + 1
            ) {
                throw new \Exception(
                    "Chuyển trạng thái không đúng trình tự hợp lệ",
                );
            }

            $receipt = $provider->getReceipt();
            $fromStatus = $provider->getStatus();
            $provider->setStatus($status);

            if ($status === StockReceiptProviderStatus::InTransit->value) {
                $provider->setShippedAt(new \DateTime());
            } elseif ($status === StockReceiptProviderStatus::Arrived->value) {
                $provider->setArrivedAt(new \DateTime());
            } elseif (
                $status === StockReceiptProviderStatus::Inspecting->value
            ) {
                $provider->setInspectionStartedAt(new \DateTime());
                $provider->setInspectedBy($currentUser);
            }

            $providers = $receipt->getProviders();
            $allCompleted = true;
            $anyInProgress = false;

            foreach ($providers as $p) {
                if (
                    $p->getStatus() !==
                    StockReceiptProviderStatus::Completed->value
                ) {
                    $allCompleted = false;
                }
                if (
                    $p->getStatus() !==
                        StockReceiptProviderStatus::Created->value &&
                    $p->getStatus() !==
                        StockReceiptProviderStatus::Cancelled->value
                ) {
                    $anyInProgress = true;
                }
            }

            if ($allCompleted) {
                $receipt->setStatus(StockReceiptStatus::Completed->value);
                $receipt->setFulfillmentStatus(FulfillmentStatus::Full->value);
                $receipt->setCompletedAt(new \DateTime());
            } elseif ($anyInProgress) {
                $receipt->setStatus(StockReceiptStatus::InProgress->value);
            }

            $receiptEvent = new StockReceiptEvent();
            $receiptEvent->setReceipt($receipt);
            $receiptEvent->setReceiptProvider($provider);
            $receiptEvent->setEventType(
                StockReceiptEventType::StatusChanged->value,
            );
            $receiptEvent->setFromStatus($fromStatus);
            $receiptEvent->setToStatus($status);
            $receiptEvent->setActor($currentUser);
            $receiptEvent->setActorType(ActorType::User->value);
            $receiptEvent->setComment(
                sprintf(
                    "Cập nhật trạng thái nhà cung cấp %s từ %s sang %s",
                    $provider->getProviderSnapshot()["name"] ?? "",
                    $fromStatus,
                    $status,
                ),
            );
            $this->entityManager->persist($receiptEvent);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $provider->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }
}
