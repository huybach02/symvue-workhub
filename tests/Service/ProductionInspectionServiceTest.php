<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Class\ProductionOrderStatus;
use App\DTO\ProductionItemInspectLotDTO;
use App\Entity\InventoryMovement;
use App\Entity\Merchandise;
use App\Entity\ProductionGoodsReceipt;
use App\Entity\ProductionItemInspection;
use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderMaterial;
use App\Entity\ProductionOrderItem;
use App\Entity\Unit;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Repository\ProductionItemInspectionRepository;
use App\Repository\ProductionOrderItemRepository;
use App\Service\ProductionInspectionService;
use App\Service\ProductionOrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProductionInspectionServiceTest extends TestCase
{
    public function testItemWithSupplementStaysInspectingUntilProductionTargetIsReached(): void
    {
        $item = $this->createItem('5.000000', '5.000000', '5.800000');

        $metrics = $this->invokePrivate('metrics', [$item]);
        $resolution = $this->invokePrivate('resolveCurrentItem', [
            $item,
            null,
            $metrics,
            new \DateTime('2026-08-31 12:00:00'),
        ]);

        self::assertSame('PARTIAL_TARGET', $resolution);
        self::assertSame(ProductionOrderStatus::Inspecting->value, $item->getStatus());
        self::assertNull($item->getCompletedAt());
        self::assertSame('0.800000', $metrics['productionTargetRemaining']);
    }

    public function testExternalFulfillmentDoesNotCompleteSourceProductionTarget(): void
    {
        $item = $this->createItem('5.000000', '5.000000', '5.800000');
        $item->setShortageData(['externalFulfilledBaseQuantity' => '0.800000']);

        $metrics = $this->invokePrivate('metrics', [$item]);
        $resolution = $this->invokePrivate('resolveCurrentItem', [
            $item,
            null,
            $metrics,
            new \DateTime('2026-08-31 12:00:00'),
        ]);

        self::assertSame('5.800000', $metrics['effective']);
        self::assertSame('PARTIAL_TARGET', $resolution);
        self::assertSame(ProductionOrderStatus::Inspecting->value, $item->getStatus());
    }

    public function testItemWithSupplementCompletesAfterProductionTargetIsReached(): void
    {
        $item = $this->createItem('5.000000', '5.800000', '5.800000');
        $postedAt = new \DateTime('2026-08-31 12:00:00');

        $metrics = $this->invokePrivate('metrics', [$item]);
        $resolution = $this->invokePrivate('resolveCurrentItem', [
            $item,
            null,
            $metrics,
            $postedAt,
        ]);

        self::assertSame('FULL', $resolution);
        self::assertSame(ProductionOrderStatus::Completed->value, $item->getStatus());
        self::assertSame($postedAt, $item->getCompletedAt());
        self::assertSame('0.000000', $metrics['productionTargetRemaining']);
    }

    public function testItemWithoutSupplementStillCompletesAtOwnRequirement(): void
    {
        $item = $this->createItem('5.000000', '5.000000');

        $metrics = $this->invokePrivate('metrics', [$item]);
        $resolution = $this->invokePrivate('resolveCurrentItem', [
            $item,
            null,
            $metrics,
            new \DateTime('2026-08-31 12:00:00'),
        ]);

        self::assertSame('FULL', $resolution);
        self::assertSame(ProductionOrderStatus::Completed->value, $item->getStatus());
    }

    public function testFullModeStillHasRemainingWhenOnlyMinimumIsReached(): void
    {
        $item = $this->createItem('10.000000', '9.200000');
        $item->setShortageData(['externalFulfilledBaseQuantity' => '0.300000']);

        $minimumRemaining = $this->invokePrivate('remainingForMode', [$item, 'MINIMUM']);
        $fullRemaining = $this->invokePrivate('remainingForMode', [$item, 'FULL']);

        self::assertSame('0.000000', $minimumRemaining);
        self::assertSame('0.500000', $fullRemaining);
    }

    public function testMaterialCostingUsesProductionTargetForEveryReceipt(): void
    {
        $item = $this->createItem('5.000000', '0.000000', '5.800000');
        $item->addMaterial($this->createMaterial('30.0000'));
        $item->addMaterial($this->createMaterial('28.0000'));

        $costing = $this->invokePrivate('calculateMaterialCosting', [
            $item,
            '5.800000',
            '2.900000',
        ]);

        self::assertSame('58.0000', $costing['actualMaterialCost']);
        self::assertSame('10.0000', $costing['unitCostBase']);
        self::assertSame('29.0000', $costing['receiptTotalCost']);
    }

    public function testMaterialCostingRejectsMissingActualCost(): void
    {
        $item = $this->createItem('5.000000', '0.000000');
        $item->addMaterial(new ProductionOrderMaterial());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('chưa có giá vốn thực tế hợp lệ');

        $this->invokePrivate('calculateMaterialCosting', [
            $item,
            '5.000000',
            '1.000000',
        ]);
    }

    public function testMaterialCostingRejectsInvalidProductionTarget(): void
    {
        $item = $this->createItem('5.000000', '0.000000');
        $item->addMaterial($this->createMaterial('50.0000'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mục tiêu sản xuất phải lớn hơn 0');

        $this->invokePrivate('calculateMaterialCosting', [
            $item,
            '0.000000',
            '1.000000',
        ]);
    }

    public function testProductionStockInMovementReceivesCalculatedUnitCost(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistedEntities = [];
        $entityManager->expects(self::exactly(3))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persistedEntities): void {
                $persistedEntities[] = $entity;
            });
        $service = new ProductionInspectionService(
            $entityManager,
            $this->createStub(ProductionOrderItemRepository::class),
            $this->createStub(ProductionItemInspectionRepository::class),
            $this->createStub(ProductionOrderService::class),
        );

        $order = (new ProductionOrder())->setFinishedGoodsWarehouse(new Warehouse());
        $item = $this->createItem('5.000000', '0.000000');
        $item->setProductionOrder($order);
        $item->setFinishedProduct(new Merchandise());
        $item->setBaseUnit(new Unit());
        $receipt = (new ProductionGoodsReceipt())->setCode('PNKSX-TEST');

        $this->invokePrivateOn($service, 'postAcceptedLotsToInventory', [
            $item,
            new ProductionItemInspection(),
            $receipt,
            [[
                'clientLineUuid' => 'line-1',
                'acceptedQuantity' => '2.900000',
                'manufactureDate' => '2026-08-31',
                'expiryDate' => '2026-09-30',
                'productionLotCode' => null,
                'note' => null,
            ]],
            '10.0000',
            new \DateTime('2026-08-31 12:00:00'),
            new User(),
        ]);

        $movements = array_values(array_filter(
            $persistedEntities,
            static fn (object $entity): bool => $entity instanceof InventoryMovement,
        ));
        self::assertCount(1, $movements);
        self::assertSame('10.0000', $movements[0]->getUnitCostBase());
        self::assertSame('2.900000', $movements[0]->getQuantityBaseDelta());
    }

    public function testNormalizeLotsRejectsDuplicateManufactureAndExpiryDates(): void
    {
        $lots = [
            new ProductionItemInspectLotDTO(
                clientLineUuid: '8af359f4-e8e1-4f9d-9af3-d7f88fbf9279',
                receivedQuantity: '1.000000',
                acceptedQuantity: '1.000000',
                manufactureDate: '2026-08-09',
                expiryDate: '2026-09-09',
            ),
            new ProductionItemInspectLotDTO(
                clientLineUuid: 'c6398639-abfa-4f5a-b29a-35af0d4160e9',
                receivedQuantity: '1.000000',
                acceptedQuantity: '1.000000',
                manufactureDate: '2026-08-09',
                expiryDate: '2026-09-09',
            ),
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ngày sản xuất và hạn sử dụng của lô bị trùng');

        $this->invokePrivate('normalizeLots', [$lots]);
    }

    private function createItem(
        string $planned,
        string $accepted,
        ?string $productionTarget = null,
    ): ProductionOrderItem
    {
        $item = new ProductionOrderItem();
        $item->setPlannedBaseQuantity($planned);
        $item->setAcceptedBaseQuantity($accepted);
        $item->setExpectedWastePercent('5.00');
        $item->setStatus(ProductionOrderStatus::Inspecting->value);

        if ($productionTarget !== null) {
            $item->setSupplementData([
                'productionTargetBaseQuantity' => $productionTarget,
                'plans' => [[
                    'productionOrderItemId' => 1,
                    'mode' => 'FULL',
                    'plannedBaseQuantity' => '0.800000',
                ]],
            ]);
        }

        return $item;
    }

    private function createMaterial(string $actualCost): ProductionOrderMaterial
    {
        $material = new ProductionOrderMaterial();
        $material->setActualCost($actualCost);

        return $material;
    }

    private function invokePrivate(string $method, array $arguments): mixed
    {
        $service = (new \ReflectionClass(ProductionInspectionService::class))
            ->newInstanceWithoutConstructor();

        return $this->invokePrivateOn($service, $method, $arguments);
    }

    private function invokePrivateOn(
        ProductionInspectionService $service,
        string $method,
        array $arguments,
    ): mixed
    {
        return (new \ReflectionMethod($service, $method))->invoke($service, ...$arguments);
    }
}
