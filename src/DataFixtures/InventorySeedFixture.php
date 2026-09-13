<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
use App\Entity\BusinessProductVariantRecipe;
use App\Entity\GeneralSetting;
use App\Entity\InventoryBalance;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\Merchandise;
use App\Entity\MerchandiseRecipe;
use App\Entity\User;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InventorySeedFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private const LOT_MULTIPLIER = 50; // Tồn mỗi lot gấp 50x nhu cầu 1 chu kỳ sản xuất / bán hàng
    private const LOT_COUNT = 3;       // 3 lot cách hạn nhau để kiểm thử FEFO

    private ObjectManager $manager;

    private ?Warehouse $warehouse = null;

    private ?User $systemUser = null;

    public static function getGroups(): array
    {
        return ['inventory-seed'];
    }

    public function getDependencies(): array
    {
        return [
            GeneralSettingFixture::class,
            MainBranchAndWarehouseFixture::class,
            UserFixture::class,
            MerchandiseIngredientFixtures::class,
            MerchandiseFinishedProductFixtures::class,
            BusinessProductFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->warehouse = $this->resolveMaterialWarehouse();
        $this->systemUser = $this->resolveSystemUser();

        // 1. Tổng hợp nhu cầu từ công thức sản xuất nội bộ & công thức món ăn POS
        $demandMap = $this->computeTotalDemandMap();

        // 2. Lấy toàn bộ hàng hóa trong hệ thống (cả nguyên liệu và thành phẩm)
        $merchandiseList = $this->manager->getRepository(Merchandise::class)->findBy(['status' => 1]);
        if (empty($merchandiseList)) {
            $merchandiseList = $this->manager->getRepository(Merchandise::class)->findAll();
        }

        $seq = 0;
        foreach ($merchandiseList as $merchandise) {
            if (!$merchandise->getBaseUnit()) {
                continue;
            }

            $demandBase = $demandMap[$merchandise->getId()] ?? null;
            $perLot = $this->computePerLotQuantity($merchandise, $demandBase);
            $unitCostBase = $this->resolveUnitCostBase($merchandise);

            for ($lotIndex = 0; $lotIndex < self::LOT_COUNT; ++$lotIndex) {
                $this->createLotWithBalanceAndMovement(
                    $merchandise,
                    $perLot,
                    $unitCostBase,
                    ++$seq,
                    $lotIndex,
                );
            }

            if (($seq % 15) === 0) {
                $this->manager->flush();
            }
        }

        $this->manager->flush();
    }

    /**
     * Tổng hợp nhu cầu base quantity của từng mặt hàng khi:
     * - Sản xuất tất cả thành phẩm 1 lần (MerchandiseRecipe).
     * - Bán tất cả các món trong menu 1 lần (BusinessProductVariantRecipe).
     *
     * @return array<int, string> merchandiseId => base quantity
     */
    private function computeTotalDemandMap(): array
    {
        $demand = [];

        // 1. Nhu cầu nguyên liệu từ công thức sản xuất nội bộ (MerchandiseRecipe)
        $merchandiseRecipes = $this->manager->getRepository(MerchandiseRecipe::class)->findAll();
        foreach ($merchandiseRecipes as $recipe) {
            foreach ($recipe->getItems() as $recipeItem) {
                $ingredient = $recipeItem->getIngredient();
                if (!$ingredient || !$ingredient->getId()) {
                    continue;
                }

                $factor = (string) ($recipeItem->getFactorToBaseSnapshot() ?? '1');
                $wasteRate = (string) ($recipeItem->getWasteRate() ?? '0.00');
                $qty = (string) ($recipeItem->getQuantity() ?? '0');

                $materialBase = MathHelper::mul(
                    MathHelper::mul($qty, $factor, 6),
                    MathHelper::add('1', MathHelper::div($wasteRate, '100', 6), 8),
                    6,
                );

                $demand[$ingredient->getId()] = MathHelper::add(
                    $demand[$ingredient->getId()] ?? '0',
                    $materialBase,
                    6,
                );
            }
        }

        // 2. Nhu cầu từ công thức món ăn bán tại quầy / POS (BusinessProductVariantRecipe)
        $variantRecipes = $this->manager->getRepository(BusinessProductVariantRecipe::class)->findAll();
        foreach ($variantRecipes as $recipe) {
            foreach ($recipe->getItems() as $recipeItem) {
                $itemMerchandise = $recipeItem->getFinishedProduct(); // trỏ tới Merchandise
                if (!$itemMerchandise || !$itemMerchandise->getId()) {
                    continue;
                }

                $factor = (string) ($recipeItem->getFactorToBaseSnapshot() ?? '1');
                $wasteRate = (string) ($recipeItem->getWasteRate() ?? '0.00');
                $qty = (string) ($recipeItem->getQuantity() ?? '0');

                $neededBase = MathHelper::mul(
                    MathHelper::mul($qty, $factor, 6),
                    MathHelper::add('1', MathHelper::div($wasteRate, '100', 6), 8),
                    6,
                );

                $demand[$itemMerchandise->getId()] = MathHelper::add(
                    $demand[$itemMerchandise->getId()] ?? '0',
                    $neededBase,
                    6,
                );
            }
        }

        return $demand;
    }

    /**
     * Xác định số lượng tồn kho dồi dào cho mỗi Lot theo đơn vị tính chuẩn
     */
    private function computePerLotQuantity(Merchandise $merchandise, ?string $demandBase): string
    {
        $unitCode = strtoupper($merchandise->getBaseUnit()?->getCode() ?? '');

        // Định mức số lượng tồn kho tiêu chuẩn cho từng loại đơn vị
        $defaultQuantity = match ($unitCode) {
            'G' => '100000.000000',    // 100 kg / lot => 3 lots = 300 kg
            'ML' => '100000.000000',   // 100 Lít / lot => 3 lots = 300 Lít
            'KG' => '1000.000000',     // 1,000 kg / lot => 3 lots = 3 tấn
            'L' => '1000.000000',      // 1,000 Lít / lot => 3 lots = 3,000 Lít
            'GOI' => '1000.000000',    // 1,000 gói / lot => 3 lots = 3,000 gói
            'VIEN' => '5000.000000',   // 5,000 viên / lot => 3 lots = 15,000 viên
            'PHAN' => '1000.000000',   // 1,000 phần / lot => 3 lots = 3,000 phần
            default => '1000.000000',  // 1,000 đơn vị / lot => 3 lots = 3,000 đơn vị
        };

        if ($demandBase !== null && MathHelper::comp($demandBase, '0') > 0) {
            $calculatedFromDemand = MathHelper::mul($demandBase, (string) self::LOT_MULTIPLIER, 6);
            if (MathHelper::comp($calculatedFromDemand, $defaultQuantity) > 0) {
                return $calculatedFromDemand;
            }
        }

        return $defaultQuantity;
    }

    /**
     * Kho seed: ưu tiên PRODUCTION_MATERIAL_WAREHOUSE_ID trong GeneralSetting,
     * fallback MAIN_WAREHOUSE, cuối cùng là warehouse bất kỳ đầu tiên.
     */
    private function resolveMaterialWarehouse(): Warehouse
    {
        $setting = $this->manager->getRepository(GeneralSetting::class)->findOneBy([
            'tenCauHinh' => 'PRODUCTION_MATERIAL_WAREHOUSE_ID',
        ]);
        $warehouseId = $setting?->getGiaTri();

        if ($warehouseId) {
            $warehouse = $this->manager->getRepository(Warehouse::class)->find((int) $warehouseId);
            if ($warehouse) {
                return $warehouse;
            }
        }

        // Fallback: kho MAIN_WAREHOUSE (do MainBranchAndWarehouseFixture tạo)
        $warehouse = $this->manager->getRepository(Warehouse::class)->findOneBy(['code' => 'MAIN_WAREHOUSE']);
        if ($warehouse) {
            return $warehouse;
        }

        // Fallback cuối: warehouse bất kỳ đầu tiên
        $warehouse = $this->manager->getRepository(Warehouse::class)->findOneBy([]);
        if ($warehouse) {
            return $warehouse;
        }

        throw new \RuntimeException(
            'Không tìm thấy warehouse nào để seed tồn kho. '
                . 'Chạy group main-branch-and-warehouse trước.',
        );
    }

    private function resolveSystemUser(): User
    {
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => 'huybach2002ct@gmail.com'])
            ?? $this->manager->getRepository(User::class)->findOneBy([]);

        if (!$user) {
            throw new \RuntimeException('Không tìm thấy user để gán postedBy cho movement seed.');
        }

        return $user;
    }

    /**
     * Giá vốn đơn vị (base unit) của hàng hóa = MerchandiseProviderPrice
     * đang áp dụng cho base unit; fallback giá NCC bất kỳ hoặc 10,000.0000.
     */
    private function resolveUnitCostBase(Merchandise $merchandise): string
    {
        $baseUnit = $merchandise->getBaseUnit();

        $price = $this->manager->createQuery(
            'SELECT p.price
             FROM App\Entity\MerchandiseProviderPrice p
             JOIN p.merchandiseProvider mp
             WHERE mp.merchandise = :merchandise
               AND p.unit = :unit
             ORDER BY p.effectiveFrom DESC, p.id DESC'
        )
            ->setParameter('merchandise', $merchandise)
            ->setParameter('unit', $baseUnit)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if (!empty($price['price'])) {
            return (string) $price['price'];
        }

        $anyPrice = $this->manager->createQuery(
            'SELECT p.price
             FROM App\Entity\MerchandiseProviderPrice p
             JOIN p.merchandiseProvider mp
             WHERE mp.merchandise = :merchandise
             ORDER BY p.effectiveFrom DESC, p.id DESC'
        )
            ->setParameter('merchandise', $merchandise)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if (!empty($anyPrice['price'])) {
            return (string) $anyPrice['price'];
        }

        return '10000.0000';
    }

    private function createLotWithBalanceAndMovement(
        Merchandise $merchandise,
        string $quantityBase,
        string $unitCostBase,
        int $seq,
        int $lotIndex,
    ): void {
        $today = new \DateTime('today');
        // Hạn cách nhau: +30, +60, +90 ngày — lot 0 gần hạn nhất (FEFO trừ trước)
        $expiry = (clone $today)->modify(sprintf('+%d days', 30 * ($lotIndex + 1)));
        $manufactureDate = (clone $today)->modify(sprintf('-%d days', 15 * ($lotIndex + 1)));
        $receivedAt = (clone $today)->modify(sprintf('-%d days', 10 * ($lotIndex + 1)));

        $internalCode = sprintf(
            'LOT-%s-%02d',
            $merchandise->getCode() ?? (string) $merchandise->getId(),
            $lotIndex + 1,
        );

        $lot = $this->manager->getRepository(InventoryLot::class)->findOneBy(['internalCode' => $internalCode]);
        if (!$lot) {
            $lot = new InventoryLot();
            $lot->setInternalCode($internalCode);
            $this->manager->persist($lot);
        }

        $originType = $merchandise->getFinishedProductSource() === 'production' ? 'PRODUCTION' : 'PURCHASE';
        $lot->setOriginType($originType);
        $lot->setMerchandise($merchandise);
        $lot->setManufactureDate($manufactureDate);
        $lot->setExpiryDate($expiry);
        $lot->setReceivedAt($receivedAt);
        $lot->setStatus(InventoryLotStatus::Available->value);
        $lot->setNote('Seed dữ liệu tồn kho dồi dào để test');

        $movement = $this->manager->getRepository(InventoryMovement::class)->findOneBy([
            'warehouse' => $this->warehouse,
            'merchandise' => $merchandise,
            'lot' => $lot,
            'movementType' => InventoryMovementType::StockIn->value,
        ]);
        if (!$movement) {
            $movement = new InventoryMovement();
            $movement->setMovementType(InventoryMovementType::StockIn->value);
            $movement->setSourceRef([
                'seed' => true,
                'merchandiseId' => $merchandise->getId(),
                'lotSeq' => $seq,
            ]);
            $movement->setWarehouse($this->warehouse);
            $movement->setMerchandise($merchandise);
            $movement->setLot($lot);
            $this->manager->persist($movement);
        }

        $movement->setQuantityBaseDelta($quantityBase);
        $movement->setBaseUnit($merchandise->getBaseUnit());
        $movement->setUnitCostBase($unitCostBase);
        $movement->setNote('Seed tồn kho ' . ($merchandise->getName() ?? $merchandise->getCode()));
        $movement->setPostedAt($receivedAt);
        $movement->setPostedBy($this->systemUser);

        $balance = $this->manager->getRepository(InventoryBalance::class)->findOneBy([
            'warehouse' => $this->warehouse,
            'merchandise' => $merchandise,
            'lot' => $lot,
        ]);
        if (!$balance) {
            $balance = new InventoryBalance();
            $balance->setWarehouse($this->warehouse);
            $balance->setMerchandise($merchandise);
            $balance->setLot($lot);
            $this->manager->persist($balance);
        }

        $balance->setOnHandBaseQuantity($quantityBase);
        $balance->setReservedBaseQuantity('0.000000');
        $balance->setBlockedBaseQuantity('0.000000');
    }
}
