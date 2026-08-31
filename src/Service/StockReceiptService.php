<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\MathHelper;
use App\Class\StockReceiptStatus;
use App\Class\StockReceiptProviderStatus;
use App\Class\FulfillmentStatus;
use App\Class\BackorderResolutionStatus;
use App\Class\StockReceiptEventType;
use App\Class\ActorType;
use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementType;
use App\Class\ShortageResolution;
use App\Class\Request\RequestConstant;
use App\Class\Warehouse\Warehoue as WarehouseHelper;
use App\DTO\StockReceiptDTO;
use App\DTO\StockReceiptInspectingDTO;
use App\DTO\StockReceiptInspectingLotDTO;
use App\Entity\StockReceipt;
use App\Entity\Request;
use App\Entity\StockReceiptProvider;
use App\Entity\StockReceiptItem;
use App\Entity\StockReceiptItemLot;
use App\Entity\StockReceiptEvent;
use App\Entity\InventoryBalance;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\Merchandise;
use App\Entity\Provider;
use App\Entity\Unit;
use App\Entity\User;
use App\Repository\StockReceiptProviderRepository;
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
        private readonly StockReceiptProviderRepository $stockReceiptProviderRepository,
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

        if (!empty($params["f"]) && is_array($params["f"])) {
            // Lấy tất cả phiếu (gốc hoặc bổ sung) thỏa mãn điều kiện filter
            $filterQb = $this->stockReceiptRepository->createQueryBuilder("e");
            FilterWithPagination::findWithPagination(
                $filterQb,
                ["f" => $params["f"], "limit" => -1],
                "e"
            );
            /** @var StockReceipt[] $matchingReceipts */
            $matchingReceipts = $filterQb->getQuery()->getResult();

            if (empty($matchingReceipts)) {
                return [
                    "collection" => [],
                    "total" => 0,
                    "total_current" => 0,
                    "current_page" => 1,
                    "last_page" => 1,
                    "from" => 0,
                    "to" => 0,
                ];
            }

            // Tìm ra tập hợp ID của các phiếu Root tương ứng
            $rootIds = [];
            foreach ($matchingReceipts as $receipt) {
                $curr = $receipt;
                while ($curr->getParentReceipt() !== null) {
                    $curr = $curr->getParentReceipt();
                }
                $rootIds[$curr->getId()] = $curr->getId();
            }

            $qb->andWhere("e.parentReceipt IS NULL")
                ->andWhere("e.id IN (:rootIds)")
                ->setParameter("rootIds", array_values($rootIds));

            $paramsWithoutF = $params;
            unset($paramsWithoutF["f"]);

            $result = FilterWithPagination::findWithPagination($qb, $paramsWithoutF, "e");
        } else {
            $qb->andWhere("e.parentReceipt IS NULL");
            $result = FilterWithPagination::findWithPagination($qb, $params, "e");
        }

        $result["collection"] = array_map(
            function (StockReceipt $item) {
                $data = $item->jsonSerialize();
                $childCount = $this->stockReceiptRepository->countChildren($item->getId());
                $data["childCount"] = $childCount;
                $data["hasChildren"] = $childCount > 0;
                return $data;
            },
            $result["collection"],
        );

        return $result;
    }

    public function findChildren(int $parentId): array
    {
        $children = $this->stockReceiptRepository->findChildren($parentId);

        return array_map(
            function (StockReceipt $item) {
                $data = $item->jsonSerialize();
                $childCount = $this->stockReceiptRepository->countChildren($item->getId());
                $data["childCount"] = $childCount;
                $data["hasChildren"] = $childCount > 0;
                return $data;
            },
            $children,
        );
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

            $this->assertRequestReadyForReceipt($request, $currentUser);

            $stockReceipt = $this->buildStockReceipt($request);
            $this->addProvidersAndItemsFromPayload(
                $stockReceipt,
                $request->getPayload() ?? [],
            );

            $this->recordReceiptEvent(
                receipt: $stockReceipt,
                eventType: StockReceiptEventType::Created->value,
                actor: $currentUser,
                comment: "Tạo phiếu nhập kho từ đề xuất #" .
                    $request->getCode(),
                toStatus: StockReceiptStatus::Created->value,
            );

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
            $provider = $this->stockReceiptProviderRepository->find(
                $providerId,
            );
            if (!$provider) {
                throw new \Exception(
                    "Nhà cung cấp trong phiếu nhập kho không tồn tại",
                );
            }

            $fromStatus = $provider->getStatus();
            $this->assertNextProviderStatus($fromStatus, $status);

            $provider->setStatus($status);
            $this->applyProviderStatusTimestamps(
                $provider,
                $status,
                $currentUser,
            );
            $this->syncReceiptStatusFromProviders($provider->getReceipt());

            $this->recordReceiptEvent(
                receipt: $provider->getReceipt(),
                eventType: StockReceiptEventType::StatusChanged->value,
                actor: $currentUser,
                comment: sprintf(
                    "Cập nhật trạng thái nhà cung cấp %s từ %s sang %s",
                    $provider->getProviderSnapshot()["name"] ?? "",
                    $fromStatus,
                    $status,
                ),
                receiptProvider: $provider,
                fromStatus: $fromStatus,
                toStatus: $status,
            );

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $provider->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    public function inspecting(
        StockReceiptInspectingDTO $stockReceiptInspectingDTO,
        User $currentUser,
    ): StockReceipt {
        $this->entityManager->beginTransaction();
        try {
            $receiptProvider = $this->loadProviderForInspecting(
                $stockReceiptInspectingDTO->providerId,
            );
            $receipt = $receiptProvider->getReceipt();
            $this->entityManager->lock(
                $receipt,
                \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
            );

            $postedAt = \DateTime::createFromInterface(
                new \DateTimeImmutable(),
            );
            $providerItems = $this->indexProviderItems($receiptProvider);

            // Tạo lot + post số lượng chấp nhận vào kho
            $inspectionResult = $this->processInspectionItems(
                $stockReceiptInspectingDTO,
                $receipt,
                $receiptProvider,
                $providerItems,
                $postedAt,
                $currentUser,
            );

            // So sánh số lượng chấp nhận với số lượng yêu cầu để tìm thiếu hàng
            [$hasShortage, $shortageItems] = $this->detectShortageItems(
                $providerItems,
                $inspectionResult["acceptedBaseByItem"],
            );
            $this->assertShortageResolution(
                $hasShortage,
                $stockReceiptInspectingDTO->shortageResolution,
            );

            $fromStatus = $receiptProvider->getStatus();
            $this->completeInspectedProvider(
                $receiptProvider,
                $hasShortage,
                $stockReceiptInspectingDTO->shortageResolution,
                $postedAt,
                $currentUser,
            );

            $supplementCode = $this->createBackorderIfNeeded(
                $hasShortage,
                $stockReceiptInspectingDTO->shortageResolution,
                $receipt,
                $receiptProvider,
                $shortageItems,
                $currentUser,
            );

            $this->recordInspectionEvents(
                $receipt,
                $receiptProvider,
                $fromStatus,
                $currentUser,
                $supplementCode,
                $inspectionResult["postedLotCount"],
            );

            $this->aggregateReceiptStatus($receipt, $postedAt);

            $this->propagateSupplementResultToAncestors(
                $receipt,
                $postedAt,
                $currentUser,
            );

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $receipt;
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    /**
     * Kiểm tra đề xuất đủ điều kiện để tạo phiếu nhập kho gốc.
     */
    private function assertRequestReadyForReceipt(
        ?Request $request,
        User $currentUser,
    ): void {
        if (
            !$request ||
            $request->getType() !== RequestConstant::TYPE_STOCK_IN ||
            $request->getStatus() !== RequestConstant::STATUS_APPROVED
        ) {
            throw new \Exception("Đề xuất không hợp lệ hoặc chưa được duyệt");
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
            throw new \Exception("Đề xuất này đã được tạo phiếu nhập kho gốc");
        }
    }

    /**
     * Tạo entity phiếu nhập kho từ đề xuất đã duyệt.
     */
    private function buildStockReceipt(Request $request): StockReceipt
    {
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
        $stockReceipt->setFulfillmentStatus(FulfillmentStatus::Pending->value);
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

        return $stockReceipt;
    }

    /**
     * Sinh provider + dòng hàng từ payload đề xuất.
     */
    private function addProvidersAndItemsFromPayload(
        StockReceipt $stockReceipt,
        array $payload,
    ): void {
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
                $this->addReceiptItemFromPayload(
                    $receiptProvider,
                    $provider,
                    $itemData,
                    $sortOrder++,
                );
            }
        }
    }

    /**
     * Tạo một dòng hàng trên phiếu từ dữ liệu payload.
     */
    private function addReceiptItemFromPayload(
        StockReceiptProvider $receiptProvider,
        Provider $provider,
        array $itemData,
        int $sortOrder,
    ): void {
        $merchandise = $this->merchandiseRepository->find(
            $itemData["merchandiseId"] ?? 0,
        );
        $expectedUnit = $this->unitRepository->find($itemData["unitId"] ?? 0);
        if (!$merchandise || !$expectedUnit) {
            throw new \Exception("Nguyên liệu hoặc đơn vị tính không tồn tại");
        }

        [$factorToBase, $expectedUnitLabel] = $this->resolveExpectedUnitMeta(
            $merchandise,
            $provider,
            $expectedUnit,
        );

        $expectedQuantity = (string) ($itemData["quantity"] ?? "0");
        $unitPrice = (string) ($itemData["price"] ?? "0");
        $expectedBaseQuantity = MathHelper::mul(
            $expectedQuantity,
            $factorToBase,
            6,
        );
        $baseUnitCost =
            MathHelper::comp($factorToBase, "0", 8) === 0
                ? "0.0000"
                : MathHelper::div($unitPrice, $factorToBase, 4);

        $baseUnit = $merchandise->getBaseUnit();
        if (!$baseUnit) {
            throw new \Exception("Nguyên liệu chưa cấu hình đơn vị cơ bản");
        }

        $receiptItem = new StockReceiptItem();
        $receiptItem->setReceiptProvider($receiptProvider);
        $receiptItem->setSourceRequestLineId((string) $itemData["lineId"]);
        $receiptItem->setMerchandise($merchandise);
        $receiptItem->setMerchandiseCodeSnapshot($merchandise->getCode());
        $receiptItem->setMerchandiseNameSnapshot($merchandise->getName());
        $receiptItem->setExpectedQuantity($expectedQuantity);
        $receiptItem->setExpectedUnit($expectedUnit);
        $receiptItem->setExpectedUnitLabelSnapshot($expectedUnitLabel);
        $receiptItem->setExpectedFactorToBase($factorToBase);
        $receiptItem->setExpectedBaseQuantity($expectedBaseQuantity);
        $receiptItem->setBaseUnit($baseUnit);
        $receiptItem->setBaseUnitLabelSnapshot($baseUnit->getName());
        $receiptItem->setUnitPriceSnapshot($unitPrice);
        $receiptItem->setBaseUnitCostSnapshot($baseUnitCost);
        $receiptItem->setCurrency(
            trim((string) ($itemData["currency"] ?? "VND")) ?: "VND",
        );
        $receiptItem->setSortOrder($sortOrder);
        $receiptItem->setNote(trim((string) ($itemData["note"] ?? "")));
        $this->entityManager->persist($receiptItem);
    }

    /**
     * Lấy hệ số quy đổi + nhãn đơn vị khi tạo phiếu (bắt buộc cấu hình nếu không phải đơn vị cơ sở).
     */
    private function resolveExpectedUnitMeta(
        Merchandise $merchandise,
        Provider $provider,
        Unit $expectedUnit,
    ): array {
        $baseUnit = $merchandise->getBaseUnit();
        if ($baseUnit && $baseUnit->getId() === $expectedUnit->getId()) {
            return ["1.00000000", $expectedUnit->getName()];
        }

        $expectedUnitLabel = $expectedUnit->getName();

        $mProvider = $this->merchandiseProviderRepository->findOneBy([
            "merchandise" => $merchandise,
            "provider" => $provider,
        ]);
        if ($mProvider) {
            $providerUnit = $this->merchandiseProviderUnitRepository->findOneBy(
                [
                    "merchandiseProvider" => $mProvider,
                    "unit" => $expectedUnit,
                ],
            );
            if ($providerUnit) {
                $factor = $providerUnit->getFactorToBase();
                if (
                    $factor !== null &&
                    MathHelper::comp($factor, "0", self::FACTOR_SCALE) > 0
                ) {
                    return [
                        $factor,
                        $providerUnit->getLabel() ?: $expectedUnitLabel,
                    ];
                }
            }
        }

        $mUnit = $this->merchandiseUnitRepository->findOneBy([
            "merchandise" => $merchandise,
            "unit" => $expectedUnit,
        ]);
        if ($mUnit) {
            $factor = $mUnit->getFactorToBase();
            if (
                $factor !== null &&
                MathHelper::comp($factor, "0", self::FACTOR_SCALE) > 0
            ) {
                return [$factor, $mUnit->getLabel() ?: $expectedUnitLabel];
            }
        }

        throw new \Exception(
            sprintf(
                "Chưa cấu hình hệ số quy đổi cho đơn vị '%s' của hàng hóa '%s'",
                $expectedUnit->getName(),
                $merchandise->getName(),
            ),
        );
    }

    /**
     * Chỉ cho phép chuyển đúng 1 bước theo chuỗi trạng thái provider.
     */
    private function assertNextProviderStatus(
        string $currentStatus,
        string $targetStatus,
    ): void {
        $statusOrder = [
            StockReceiptProviderStatus::Created->value,
            StockReceiptProviderStatus::AwaitingShipment->value,
            StockReceiptProviderStatus::InTransit->value,
            StockReceiptProviderStatus::Arrived->value,
            StockReceiptProviderStatus::Inspecting->value,
        ];

        $currentIndex = array_search($currentStatus, $statusOrder, true);
        $targetIndex = array_search($targetStatus, $statusOrder, true);

        if (
            $currentIndex === false ||
            $targetIndex === false ||
            $targetIndex !== $currentIndex + 1
        ) {
            throw new \Exception(
                "Chuyển trạng thái không đúng trình tự hợp lệ",
            );
        }
    }

    /**
     * Ghi timestamp/người phụ trách theo trạng thái vừa chuyển.
     */
    private function applyProviderStatusTimestamps(
        StockReceiptProvider $provider,
        string $status,
        User $currentUser,
    ): void {
        if ($status === StockReceiptProviderStatus::InTransit->value) {
            $provider->setShippedAt(new \DateTime());
            return;
        }
        if ($status === StockReceiptProviderStatus::Arrived->value) {
            $provider->setArrivedAt(new \DateTime());
            return;
        }
        if ($status === StockReceiptProviderStatus::Inspecting->value) {
            $provider->setInspectionStartedAt(new \DateTime());
            $provider->setInspectedBy($currentUser);
        }
    }

    /**
     * Đồng bộ trạng thái phiếu cha dựa trên trạng thái các provider con.
     */
    private function syncReceiptStatusFromProviders(StockReceipt $receipt): void
    {
        $allCompleted = true;
        $anyInProgress = false;

        foreach ($receipt->getProviders() as $p) {
            if (
                $p->getStatus() !== StockReceiptProviderStatus::Completed->value
            ) {
                $allCompleted = false;
            }
            if (
                $p->getStatus() !==
                    StockReceiptProviderStatus::Created->value &&
                $p->getStatus() !== StockReceiptProviderStatus::Cancelled->value
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
    }

    /**
     * Tải provider đang kiểm hàng (kèm khóa ghi chống race condition).
     */
    private function loadProviderForInspecting(
        int $providerId,
    ): StockReceiptProvider {
        $receiptProvider = $this->stockReceiptProviderRepository->find(
            $providerId,
            \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
        );
        if (!$receiptProvider) {
            throw new \Exception(
                "Nhà cung cấp trong phiếu nhập kho không tồn tại",
            );
        }
        if (
            $receiptProvider->getStatus() !==
            StockReceiptProviderStatus::Inspecting->value
        ) {
            throw new \Exception(
                "Nhà cung cấp chưa ở trạng thái đang kiểm hàng",
            );
        }

        return $receiptProvider;
    }

    /**
     * Map dòng hàng của provider theo id để đối chiếu nhanh với DTO.
     */
    private function indexProviderItems(
        StockReceiptProvider $receiptProvider,
    ): array {
        $providerItems = [];
        foreach ($receiptProvider->getItems() as $item) {
            $providerItems[$item->getId()] = $item;
        }

        return $providerItems;
    }

    /**
     * Xử lý toàn bộ dòng hàng + lô kiểm nhận, post vào kho nếu chấp nhận > 0.
     */
    private function processInspectionItems(
        StockReceiptInspectingDTO $dto,
        StockReceipt $receipt,
        StockReceiptProvider $receiptProvider,
        array $providerItems,
        \DateTimeInterface $postedAt,
        User $currentUser,
    ): array {
        $providerEntity = $receiptProvider->getProvider();
        if (!$providerEntity) {
            throw new \Exception("Nhà cung cấp không tồn tại");
        }

        $expectedIds = array_keys($providerItems);
        $submittedIds = array_map(
            fn($itemDto) => $itemDto->receiptItemId,
            $dto->items,
        );
        sort($expectedIds);
        sort($submittedIds);

        if ($expectedIds !== $submittedIds) {
            throw new \Exception(
                "Dữ liệu kiểm hàng phải bao gồm đầy đủ các dòng hàng của nhà cung cấp",
            );
        }

        $acceptedBaseByItem = [];
        $processedItemIds = [];
        $processedClientUuids = [];
        $postedLotCount = 0;

        foreach ($dto->items as $itemDto) {
            $receiptItem = $providerItems[$itemDto->receiptItemId] ?? null;
            if (!$receiptItem) {
                throw new \Exception("Dòng hàng không thuộc nhà cung cấp này");
            }
            if (isset($processedItemIds[$receiptItem->getId()])) {
                throw new \Exception("Dòng hàng kiểm nhận bị trùng");
            }
            $processedItemIds[$receiptItem->getId()] = true;

            $itemAcceptedBase = MathHelper::zero(self::QUANTITY_SCALE);
            $processedDatePairs = [];

            foreach ($itemDto->lots as $lotDto) {
                if (isset($processedClientUuids[$lotDto->clientLineUuid])) {
                    throw new \Exception("Mã định danh lô kiểm nhận bị trùng");
                }
                $processedClientUuids[$lotDto->clientLineUuid] = true;

                $datePair = sprintf(
                    "%s|%s",
                    $lotDto->manufactureDate,
                    $lotDto->expiryDate,
                );
                if (isset($processedDatePairs[$datePair])) {
                    throw new \Exception(
                        "Ngày sản xuất và hạn sử dụng của lô bị trùng",
                    );
                }
                $processedDatePairs[$datePair] = true;

                $acceptedBase = $this->createAndPostInspectionLot(
                    $lotDto,
                    $receipt,
                    $receiptProvider,
                    $receiptItem,
                    $providerEntity,
                    $postedAt,
                    $currentUser,
                );

                $itemAcceptedBase = MathHelper::add(
                    $itemAcceptedBase,
                    $acceptedBase,
                    self::QUANTITY_SCALE,
                );

                $expectedBase =
                    $receiptItem->getExpectedBaseQuantity() ?? "0";
                if (
                    MathHelper::comp(
                        $itemAcceptedBase,
                        $expectedBase,
                        self::QUANTITY_SCALE,
                    ) > 0
                ) {
                    throw new \Exception(
                        "Số lượng chấp nhận không được vượt quá số lượng đề xuất",
                    );
                }

                if (
                    MathHelper::comp($acceptedBase, "0", self::QUANTITY_SCALE) >
                    0
                ) {
                    $postedLotCount++;
                }
            }

            $acceptedBaseByItem[$receiptItem->getId()] = $itemAcceptedBase;
        }

        return [
            "acceptedBaseByItem" => $acceptedBaseByItem,
            "postedLotCount" => $postedLotCount,
        ];
    }

    /**
     * Validate + tạo 1 lô kiểm nhận; post vào kho nếu có số lượng chấp nhận.
     * Trả về số lượng chấp nhận theo base unit.
     */
    private function createAndPostInspectionLot(
        StockReceiptInspectingLotDTO $lotDto,
        StockReceipt $receipt,
        StockReceiptProvider $receiptProvider,
        StockReceiptItem $receiptItem,
        Provider $providerEntity,
        \DateTimeInterface $postedAt,
        User $currentUser,
    ): string {
        $receivedUnit = $this->unitRepository->find($lotDto->receivedUnitId);
        if (!$receivedUnit) {
            throw new \Exception("Đơn vị nhận không tồn tại");
        }

        $merchandise = $receiptItem->getMerchandise();
        if (!$merchandise) {
            throw new \Exception("Dòng hàng thiếu thông tin nguyên liệu");
        }

        [$factor, $unitLabel] = $this->resolveUnitFactor(
            $merchandise,
            $providerEntity,
            $receivedUnit,
        );

        $receivedQty = MathHelper::add(
            (string) $lotDto->receivedQuantity,
            "0",
            self::QUANTITY_SCALE,
        );
        $acceptedQty = MathHelper::add(
            (string) $lotDto->acceptedQuantity,
            "0",
            self::QUANTITY_SCALE,
        );
        if (MathHelper::comp($receivedQty, "0", self::QUANTITY_SCALE) <= 0) {
            throw new \Exception("Số lượng nhận phải lớn hơn 0");
        }

        $rejectedQty = MathHelper::sub(
            $receivedQty,
            $acceptedQty,
            self::QUANTITY_SCALE,
        );
        if (
            MathHelper::comp($acceptedQty, $receivedQty, self::QUANTITY_SCALE) >
            0
        ) {
            throw new \Exception(
                "Số lượng chấp nhận không được lớn hơn số lượng nhận",
            );
        }
        if (
            MathHelper::comp($rejectedQty, "0", self::QUANTITY_SCALE) > 0 &&
            trim((string) $lotDto->rejectionReason) === ""
        ) {
            throw new \Exception(
                "Lý do từ chối là bắt buộc khi có hàng bị từ chối",
            );
        }

        $receivedBase = MathHelper::mul(
            $receivedQty,
            $factor,
            self::QUANTITY_SCALE,
        );
        $acceptedBase = MathHelper::mul(
            $acceptedQty,
            $factor,
            self::QUANTITY_SCALE,
        );
        $rejectedBase = MathHelper::mul(
            $rejectedQty,
            $factor,
            self::QUANTITY_SCALE,
        );

        $manufactureDate = \DateTime::createFromInterface(
            new \DateTimeImmutable((string) $lotDto->manufactureDate),
        );
        $expiryDate = \DateTime::createFromInterface(
            new \DateTimeImmutable((string) $lotDto->expiryDate),
        );
        if ($expiryDate <= $manufactureDate) {
            throw new \Exception("Hạn sử dụng phải sau ngày sản xuất");
        }

        $receiptLot = new StockReceiptItemLot();
        $receiptLot->setReceiptItem($receiptItem);
        $receiptLot->setClientLineUuid($lotDto->clientLineUuid);
        $receiptLot->setReceivedQuantity($receivedQty);
        $receiptLot->setReceivedUnit($receivedUnit);
        $receiptLot->setReceivedUnitLabelSnapshot($unitLabel);
        $receiptLot->setReceivedFactorToBase($factor);
        $receiptLot->setReceivedBaseQuantity($receivedBase);
        $receiptLot->setAcceptedQuantity($acceptedQty);
        $receiptLot->setAcceptedBaseQuantity($acceptedBase);
        $receiptLot->setRejectedQuantity($rejectedQty);
        $receiptLot->setRejectedBaseQuantity($rejectedBase);
        $receiptLot->setRejectionReason($lotDto->rejectionReason);
        $receiptLot->setManufactureDate($manufactureDate);
        $receiptLot->setExpiryDate($expiryDate);
        $receiptLot->setSupplierLotCode($lotDto->supplierLotCode);
        $receiptLot->setNote($lotDto->note);
        $this->entityManager->persist($receiptLot);

        if (MathHelper::comp($acceptedBase, "0", self::QUANTITY_SCALE) > 0) {
            $this->postAcceptedLotToInventory(
                $receipt,
                $receiptProvider,
                $receiptItem,
                $receiptLot,
                $acceptedBase,
                $postedAt,
                $currentUser,
            );
        }

        return $acceptedBase;
    }

    /**
     * So sánh expected vs accepted (base unit) để tìm các dòng thiếu hàng.
     */
    private function detectShortageItems(
        array $providerItems,
        array $acceptedBaseByItem,
    ): array {
        $shortageItems = [];
        $hasShortage = false;

        foreach ($providerItems as $itemId => $item) {
            $acceptedBase = $acceptedBaseByItem[$itemId] ?? "0";
            $expectedBase = $item->getExpectedBaseQuantity() ?? "0";
            $shortageBase = MathHelper::sub(
                $expectedBase,
                $acceptedBase,
                self::QUANTITY_SCALE,
            );
            if (
                MathHelper::comp($shortageBase, "0", self::QUANTITY_SCALE) > 0
            ) {
                $hasShortage = true;
                $shortageItems[] = [$item, $shortageBase];
            }
        }

        return [$hasShortage, $shortageItems];
    }

    /**
     * Khi thiếu hàng bắt buộc chọn cách xử lý (chấp nhận thiếu hoặc tạo backorder).
     */
    private function assertShortageResolution(
        bool $hasShortage,
        ?string $shortageResolution,
    ): void {
        if (
            $hasShortage &&
            !in_array(
                $shortageResolution,
                [
                    ShortageResolution::AcceptShortage->value,
                    ShortageResolution::CreateBackorder->value,
                ],
                true,
            )
        ) {
            throw new \Exception(
                "Vui lòng chọn cách xử lý phần hàng còn thiếu",
            );
        }
    }

    /**
     * Đánh dấu provider hoàn tất kiểm hàng + gán fulfillment.
     */
    private function completeInspectedProvider(
        StockReceiptProvider $receiptProvider,
        bool $hasShortage,
        ?string $shortageResolution,
        \DateTimeInterface $postedAt,
        User $currentUser,
    ): void {
        if (!$hasShortage) {
            $providerFulfillment = FulfillmentStatus::Full->value;
            $backorderResolution = BackorderResolutionStatus::None->value;
        } elseif (
            $shortageResolution === ShortageResolution::CreateBackorder->value
        ) {
            $providerFulfillment = FulfillmentStatus::BackorderCreated->value;
            $backorderResolution = BackorderResolutionStatus::Open->value;
        } else {
            $providerFulfillment = FulfillmentStatus::PartialClosed->value;
            $backorderResolution = BackorderResolutionStatus::None->value;
        }

        $receiptProvider->setStatus(
            StockReceiptProviderStatus::Completed->value,
        );
        $receiptProvider->setInspectedAt($postedAt);
        $receiptProvider->setPostedAt($postedAt);
        $receiptProvider->setPostedBy($currentUser);
        $receiptProvider->setFulfillmentStatus($providerFulfillment);
        $receiptProvider->setBackorderResolutionStatus($backorderResolution);
        if ($hasShortage) {
            $receiptProvider->setShortageResolution($shortageResolution);
        }
    }

    /**
     * Tạo phiếu bổ sung nếu thiếu hàng và chọn CREATE_BACKORDER.
     */
    private function createBackorderIfNeeded(
        bool $hasShortage,
        ?string $shortageResolution,
        StockReceipt $receipt,
        StockReceiptProvider $receiptProvider,
        array $shortageItems,
        User $currentUser,
    ): ?string {
        if (
            !$hasShortage ||
            $shortageResolution !== ShortageResolution::CreateBackorder->value
        ) {
            return null;
        }

        $supplement = $this->createBackorderSupplement(
            $receipt,
            $receiptProvider,
            $shortageItems,
            $currentUser,
        );

        return $supplement->getCode();
    }

    /**
     * Ghi các event sau khi kiểm hàng: đổi trạng thái, backorder (nếu có), nhập kho.
     */
    private function recordInspectionEvents(
        StockReceipt $receipt,
        StockReceiptProvider $receiptProvider,
        string $fromStatus,
        User $currentUser,
        ?string $supplementCode,
        int $postedLotCount,
    ): void {
        $this->recordReceiptEvent(
            receipt: $receipt,
            eventType: StockReceiptEventType::StatusChanged->value,
            actor: $currentUser,
            comment: sprintf(
                "Hoàn tất kiểm hàng nhà cung cấp %s",
                $receiptProvider->getProviderSnapshot()["name"] ?? "",
            ),
            receiptProvider: $receiptProvider,
            fromStatus: $fromStatus,
            toStatus: StockReceiptProviderStatus::Completed->value,
        );

        if ($supplementCode !== null) {
            $this->recordReceiptEvent(
                receipt: $receipt,
                eventType: StockReceiptEventType::BackorderCreated->value,
                actor: $currentUser,
                comment: "Tạo phiếu bổ sung do thiếu hàng",
                receiptProvider: $receiptProvider,
                meta: ["supplementReceiptCode" => $supplementCode],
            );
        }

        $this->recordReceiptEvent(
            receipt: $receipt,
            eventType: StockReceiptEventType::InventoryPosted->value,
            actor: $currentUser,
            comment: "Hoàn tất nhập hàng vào kho",
            receiptProvider: $receiptProvider,
            meta: [
                "postedLotCount" => $postedLotCount,
            ],
        );
    }

    /**
     * Ghi event phiếu nhập kho (dùng chung cho create / đổi trạng thái / kiểm hàng).
     */
    private function recordReceiptEvent(
        StockReceipt $receipt,
        string $eventType,
        User $actor,
        string $comment,
        ?StockReceiptProvider $receiptProvider = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?array $meta = null,
    ): void {
        $event = new StockReceiptEvent();
        $event->setReceipt($receipt);
        $event->setEventType($eventType);
        $event->setActor($actor);
        $event->setActorType(ActorType::User->value);
        $event->setComment($comment);

        if ($receiptProvider !== null) {
            $event->setReceiptProvider($receiptProvider);
        }
        if ($fromStatus !== null) {
            $event->setFromStatus($fromStatus);
        }
        if ($toStatus !== null) {
            $event->setToStatus($toStatus);
        }
        if ($meta !== null) {
            $event->setMeta($meta);
        }

        $this->entityManager->persist($event);
    }

    private function postAcceptedLotToInventory(
        StockReceipt $receipt,
        StockReceiptProvider $provider,
        StockReceiptItem $receiptItem,
        StockReceiptItemLot $receiptLot,
        string $acceptedBase,
        \DateTimeInterface $postedAt,
        User $currentUser,
    ): void {
        $merchandise = $receiptItem->getMerchandise();
        $baseUnit = $receiptItem->getBaseUnit();
        $warehouse = $receipt->getWarehouse();
        $providerEntity = $provider->getProvider();

        if (!$merchandise || !$baseUnit || !$warehouse || !$providerEntity) {
            throw new \Exception(
                "Dữ liệu hàng hóa hoặc kho nhận không hợp lệ để nhập kho",
            );
        }

        $inventoryLot = new InventoryLot();
        $inventoryLot->setInternalCode(
            sprintf(
                "LOT-%s-%s",
                $receipt->getCode(),
                strtoupper((string) $receiptLot->getClientLineUuid()),
            ),
        );
        $inventoryLot->setSourceReceiptLotLine($receiptLot);
        $inventoryLot->setMerchandise($merchandise);
        $inventoryLot->setProvider($providerEntity);
        $inventoryLot->setSupplierLotCode($receiptLot->getSupplierLotCode());
        $inventoryLot->setManufactureDate($receiptLot->getManufactureDate());
        $inventoryLot->setExpiryDate($receiptLot->getExpiryDate());
        $inventoryLot->setReceivedAt($postedAt);
        $inventoryLot->setStatus(InventoryLotStatus::Available->value);
        $inventoryLot->setNote($receiptLot->getNote());
        $this->entityManager->persist($inventoryLot);

        $movement = new InventoryMovement();
        $movement->setMovementType(InventoryMovementType::StockIn->value);
        $movement->setWarehouse($warehouse);
        $movement->setMerchandise($merchandise);
        $movement->setLot($inventoryLot);
        $movement->setReceiptProvider($provider);
        $movement->setReceiptLotLine($receiptLot);
        $movement->setQuantityBaseDelta($acceptedBase);
        $movement->setBaseUnit($baseUnit);
        $movement->setUnitCostBase($receiptItem->getBaseUnitCostSnapshot());
        $movement->setNote("Nhập kho từ phiếu " . $receipt->getCode());
        $movement->setPostedAt($postedAt);
        $movement->setPostedBy($currentUser);
        $this->entityManager->persist($movement);

        $balance = new InventoryBalance();
        $balance->setWarehouse($warehouse);
        $balance->setMerchandise($merchandise);
        $balance->setLot($inventoryLot);
        $balance->setOnHandBaseQuantity($acceptedBase);
        $this->entityManager->persist($balance);
    }

    private function aggregateReceiptStatus(
        StockReceipt $receipt,
        \DateTimeInterface $completedAt,
    ): void {
        $allFinished = true;
        $allFull = true;
        $allCancelled = true;
        $anyBackorder = false;
        $anyPartial = false;
        $hasCompletedProvider = false;

        foreach ($receipt->getProviders() as $provider) {
            if (
                $provider->getStatus() ===
                StockReceiptProviderStatus::Completed->value
            ) {
                $hasCompletedProvider = true;
                $allCancelled = false;
            } elseif (
                $provider->getStatus() !==
                StockReceiptProviderStatus::Cancelled->value
            ) {
                $allFinished = false;
                $allCancelled = false;
            }

            $fulfillment = $provider->getFulfillmentStatus();
            $resolution = $provider->getBackorderResolutionStatus();

            $isEffectiveFull =
                $provider->getStatus() ===
                    StockReceiptProviderStatus::Completed->value &&
                ($fulfillment === FulfillmentStatus::Full->value ||
                    $resolution ===
                        BackorderResolutionStatus::ResolvedFull->value);

            if (!$isEffectiveFull) {
                $allFull = false;
            }

            if (
                $resolution === BackorderResolutionStatus::Open->value ||
                ($fulfillment === FulfillmentStatus::BackorderCreated->value &&
                    $resolution === null)
            ) {
                $anyBackorder = true;
            }

            if (
                $fulfillment === FulfillmentStatus::PartialClosed->value ||
                $resolution ===
                    BackorderResolutionStatus::ResolvedPartial->value
            ) {
                $anyPartial = true;
            }
        }

        if ($allCancelled && count($receipt->getProviders()) > 0) {
            $receipt->setStatus(StockReceiptStatus::Cancelled->value);
            $receipt->setFulfillmentStatus(FulfillmentStatus::Pending->value);
        } elseif ($anyBackorder) {
            $receipt->setStatus(StockReceiptStatus::InProgress->value);
            $receipt->setFulfillmentStatus(
                FulfillmentStatus::BackorderOpen->value,
            );
        } elseif ($allFinished) {
            $receipt->setStatus(
                $allFull
                    ? StockReceiptStatus::Completed->value
                    : StockReceiptStatus::PartiallyCompleted->value,
            );
            if ($anyPartial || !$allFull) {
                $receipt->setFulfillmentStatus(
                    FulfillmentStatus::PartialClosed->value,
                );
            } else {
                $receipt->setFulfillmentStatus(FulfillmentStatus::Full->value);
            }
        } else {
            $receipt->setStatus(StockReceiptStatus::InProgress->value);
            $receipt->setFulfillmentStatus(FulfillmentStatus::Pending->value);
        }

        $isTerminal = $allFinished && !$anyBackorder;
        $receipt->setCompletedAt($isTerminal ? $completedAt : null);
    }

    private function propagateSupplementResultToAncestors(
        StockReceipt $receipt,
        \DateTimeInterface $completedAt,
        User $currentUser,
    ): void {
        $currentReceipt = $receipt;

        while (
            ($parentReceipt = $currentReceipt->getParentReceipt()) !== null
        ) {
            $this->entityManager->lock(
                $parentReceipt,
                \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
            );

            foreach ($currentReceipt->getProviders() as $childProvider) {
                if (
                    $childProvider->getStatus() !==
                    StockReceiptProviderStatus::Completed->value
                ) {
                    continue;
                }

                $sourceProvider = null;
                if ($childProvider->getSourceReceiptProvider() !== null) {
                    $sourceProvider = $childProvider->getSourceReceiptProvider();
                } else {
                    throw new \Exception(
                        "Phiếu bổ sung không thể xác định provider nguồn",
                    );
                }

                $this->entityManager->lock(
                    $sourceProvider,
                    \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
                );

                $childResolution = $childProvider->getBackorderResolutionStatus();
                $childFulfillment = $childProvider->getFulfillmentStatus();

                $effectiveChildStatus = match (true) {
                    $childFulfillment === FulfillmentStatus::Full->value ||
                        $childResolution ===
                            BackorderResolutionStatus::ResolvedFull->value
                        => BackorderResolutionStatus::ResolvedFull->value,

                    $childFulfillment ===
                        FulfillmentStatus::PartialClosed->value ||
                        $childResolution ===
                            BackorderResolutionStatus::ResolvedPartial->value
                        => BackorderResolutionStatus::ResolvedPartial->value,

                    $childFulfillment ===
                        FulfillmentStatus::BackorderCreated->value ||
                        $childResolution ===
                            BackorderResolutionStatus::Open->value
                        => BackorderResolutionStatus::Open->value,

                    default => $sourceProvider->getBackorderResolutionStatus(),
                };

                $sourceProvider->setBackorderResolutionStatus(
                    $effectiveChildStatus,
                );
            }

            $this->aggregateReceiptStatus($parentReceipt, $completedAt);

            $this->recordReceiptEvent(
                receipt: $parentReceipt,
                eventType: StockReceiptEventType::StatusChanged->value,
                actor: $currentUser,
                comment: sprintf(
                    "Cập nhật trạng thái từ phiếu bổ sung %s",
                    $currentReceipt->getCode(),
                ),
                meta: [
                    "supplementReceiptId" => $currentReceipt->getId(),
                    "supplementReceiptCode" => $currentReceipt->getCode(),
                ],
            );

            $currentReceipt = $parentReceipt;
        }
    }

    /**
     * Giải hệ số quy đổi và nhãn đơn vị nhận theo merchandise + provider.
     * Trả về [factor, label], ưu tiên cấu hình provider rồi tới merchandise.
     */
    private function resolveUnitFactor(
        Merchandise $merchandise,
        Provider $provider,
        Unit $unit,
    ): array {
        $mProvider = $this->merchandiseProviderRepository->findOneBy([
            "merchandise" => $merchandise,
            "provider" => $provider,
        ]);

        if ($mProvider) {
            $providerUnit = $this->merchandiseProviderUnitRepository->findOneBy(
                [
                    "merchandiseProvider" => $mProvider,
                    "unit" => $unit,
                ],
            );
            if ($providerUnit) {
                $factor = $providerUnit->getFactorToBase();
                if (
                    $factor === null ||
                    MathHelper::comp($factor, "0", self::FACTOR_SCALE) <= 0
                ) {
                    throw new \Exception(
                        "Hệ số quy đổi đơn vị theo nhà cung cấp không hợp lệ",
                    );
                }

                return [$factor, $providerUnit->getLabel() ?: $unit->getName()];
            }
        }

        $merchandiseUnit = $this->merchandiseUnitRepository->findOneBy([
            "merchandise" => $merchandise,
            "unit" => $unit,
        ]);
        if ($merchandiseUnit) {
            $factor = $merchandiseUnit->getFactorToBase();
            if (
                $factor === null ||
                MathHelper::comp($factor, "0", self::FACTOR_SCALE) <= 0
            ) {
                throw new \Exception(
                    "Hệ số quy đổi đơn vị hàng hóa không hợp lệ",
                );
            }

            return [$factor, $merchandiseUnit->getLabel() ?: $unit->getName()];
        }

        if ($merchandise->getBaseUnit()?->getId() === $unit->getId()) {
            return ["1.00000000", $unit->getName()];
        }

        throw new \Exception("Đơn vị nhận không thuộc hàng hóa này");
    }

    /**
     * Tạo phiếu nhập kho bổ sung cho các dòng thiếu hàng khi chọn CREATE_BACKORDER.
     */
    private function createBackorderSupplement(
        StockReceipt $receipt,
        StockReceiptProvider $sourceProvider,
        array $shortageItems,
        User $currentUser,
    ): StockReceipt {
        $supplement = new StockReceipt();
        $supplement->setCode(
            generateSequentialCode(
                $this->entityManager,
                "PNK",
                "stock_receipt",
            ),
        );
        $supplement->setRequest($receipt->getRequest());
        $supplement->setWarehouse($receipt->getWarehouse());
        $nextSupplementNo = $this->stockReceiptRepository->getNextSupplementNo(
            $receipt,
        );
        $supplement->setParentReceipt($receipt);
        $supplement->setSupplementNo($nextSupplementNo);
        $supplement->setStatus(StockReceiptStatus::Created->value);
        $supplement->setFulfillmentStatus(FulfillmentStatus::Pending->value);
        $supplement->setTitle(
            "Phiếu nhập kho bổ sung cho " . $receipt->getCode(),
        );
        $supplement->setWarehouseSnapshot($receipt->getWarehouseSnapshot());
        $this->entityManager->persist($supplement);

        $supplementProvider = new StockReceiptProvider();
        $supplementProvider->setReceipt($supplement);
        $supplementProvider->setSourceReceiptProvider($sourceProvider);
        $supplementProvider->setProvider($sourceProvider->getProvider());
        $supplementProvider->setStatus(
            StockReceiptProviderStatus::Created->value,
        );
        $supplementProvider->setFulfillmentStatus(
            FulfillmentStatus::Pending->value,
        );
        $supplementProvider->setProviderSnapshot(
            $sourceProvider->getProviderSnapshot(),
        );
        $this->entityManager->persist($supplementProvider);

        $sortOrder = 0;
        foreach ($shortageItems as [$sourceItem, $shortageBase]) {
            $factor = $sourceItem->getExpectedFactorToBase() ?? "1.00000000";
            // Quy đổi số thiếu từ base unit về đơn vị yêu cầu gốc
            $shortageInExpected =
                MathHelper::comp($factor, "0", self::FACTOR_SCALE) === 0
                    ? MathHelper::zero(self::QUANTITY_SCALE)
                    : MathHelper::div(
                        $shortageBase,
                        $factor,
                        self::QUANTITY_SCALE,
                    );

            $supplementItem = new StockReceiptItem();
            $supplementItem->setReceiptProvider($supplementProvider);
            $supplementItem->setSourceRequestLineId(
                $sourceItem->getSourceRequestLineId() ?? "",
            );
            $supplementItem->setSourceReceiptItem($sourceItem);
            $supplementItem->setMerchandise($sourceItem->getMerchandise());
            $supplementItem->setMerchandiseCodeSnapshot(
                $sourceItem->getMerchandiseCodeSnapshot() ?? "",
            );
            $supplementItem->setMerchandiseNameSnapshot(
                $sourceItem->getMerchandiseNameSnapshot() ?? "",
            );
            $supplementItem->setExpectedQuantity($shortageInExpected);
            $supplementItem->setExpectedUnit($sourceItem->getExpectedUnit());
            $supplementItem->setExpectedUnitLabelSnapshot(
                $sourceItem->getExpectedUnitLabelSnapshot() ?? "",
            );
            $supplementItem->setExpectedFactorToBase($factor);
            $supplementItem->setExpectedBaseQuantity($shortageBase);
            $supplementItem->setBaseUnit($sourceItem->getBaseUnit());
            $supplementItem->setBaseUnitLabelSnapshot(
                $sourceItem->getBaseUnitLabelSnapshot() ?? "",
            );
            $supplementItem->setUnitPriceSnapshot(
                $sourceItem->getUnitPriceSnapshot() ?? "0",
            );
            $supplementItem->setBaseUnitCostSnapshot(
                $sourceItem->getBaseUnitCostSnapshot() ?? "0",
            );
            $supplementItem->setCurrency($sourceItem->getCurrency() ?? "VND");
            $supplementItem->setSortOrder($sortOrder++);
            $this->entityManager->persist($supplementItem);
        }

        $supplementEvent = new StockReceiptEvent();
        $supplementEvent->setReceipt($supplement);
        $supplementEvent->setEventType(StockReceiptEventType::Created->value);
        $supplementEvent->setToStatus(StockReceiptStatus::Created->value);
        $supplementEvent->setActor($currentUser);
        $supplementEvent->setActorType(ActorType::User->value);
        $supplementEvent->setComment(
            "Tạo phiếu bổ sung từ kiểm hàng phiếu " . $receipt->getCode(),
        );
        $this->entityManager->persist($supplementEvent);

        return $supplement;
    }
}
