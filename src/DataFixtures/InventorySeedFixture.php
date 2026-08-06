<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
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
    private const LOT_MULTIPLIER = 30; // Tồn mỗi lot gấp 30x nhu cầu tối đa 1 lần sản xuất toàn hệ thống
    private const LOT_COUNT = 3;

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
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->warehouse = $this->resolveMaterialWarehouse();
        $this->systemUser = $this->resolveSystemUser();

        $recipes = $this->manager->getRepository(MerchandiseRecipe::class)->findAll();
        $productionProducts = array_filter(
            $recipes,
            fn (MerchandiseRecipe $recipe): bool => $this->isProductionFinishedProduct($recipe->getFinishedProduct()),
        );

        $demandByIngredient = $this->computeTotalDemandByIngredient($productionProducts);

        $seq = 0;
        foreach ($demandByIngredient as $ingredientId => $demandBase) {
            $ingredient = $this->manager->getRepository(Merchandise::class)->find($ingredientId);
            if (!$ingredient || !$ingredient->getBaseUnit()) {
                continue;
            }

            $perLot = MathHelper::mul($demandBase, (string) self::LOT_MULTIPLIER, 6);
            $unitCostBase = $this->resolveUnitCostBase($ingredient);

            for ($i = 0; $i < self::LOT_COUNT; ++$i) {
                $this->createLotWithBalanceAndMovement(
                    $ingredient,
                    $perLot,
                    $unitCostBase,
                    ++$seq,
                    $i,
                );
            }

            if (($seq % 10) === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
    }

    private function isProductionFinishedProduct(?Merchandise $product): bool
    {
        return $product !== null
            && $product->getType() === 'finished_product'
            && $product->getFinishedProductSource() === 'production';
    }

    /**
     * Tổng nhu cầu base quantity của từng nguyên liệu khi sản xuất TẤT CẢ
     * thành phẩm 1 lần (theo recipe hiện tại, có tính tỷ lệ hao hụt).
     *
     * @param MerchandiseRecipe[] $recipes
     *
     * @return array<int, string> ingredientId => base quantity
     */
    private function computeTotalDemandByIngredient(array $recipes): array
    {
        $demand = [];

        foreach ($recipes as $recipe) {
            foreach ($recipe->getItems() as $recipeItem) {
                $ingredient = $recipeItem->getIngredient();
                $factor = $recipeItem->getFactorToBaseSnapshot();
                $wasteRate = $recipeItem->getWasteRate() ?? '0.00';
                if (!$ingredient || $factor === null) {
                    continue;
                }

                // Nhu cầu base quantity cho 1 lần sản xuất output của recipe:
                // quantity (đơn vị công thức) x hệ số quy đổi về base unit,
                // cộng thêm tỷ lệ hao hụt nếu có.
                $materialBase = MathHelper::mul(
                    MathHelper::mul($recipeItem->getQuantity() ?? '0', $factor, 6),
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

        return $demand;
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
     * Giá vốn đơn vị (base unit) của nguyên liệu = MerchandiseProviderPrice
     * đang áp dụng cho base unit; fallback '0.0000'.
     */
    private function resolveUnitCostBase(Merchandise $ingredient): string
    {
        $baseUnit = $ingredient->getBaseUnit();

        $price = $this->manager->createQuery(
            'SELECT p.price
             FROM App\Entity\MerchandiseProviderPrice p
             JOIN p.merchandiseProvider mp
             WHERE mp.merchandise = :merchandise
               AND p.unit = :unit
             ORDER BY p.effectiveFrom DESC, p.id DESC'
        )
            ->setParameter('merchandise', $ingredient)
            ->setParameter('unit', $baseUnit)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $price['price'] ?? '0.0000';
    }

    private function createLotWithBalanceAndMovement(
        Merchandise $ingredient,
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

        $lot = new InventoryLot();
        $lot->setInternalCode(sprintf('LOT-SEED-%06d', $seq));
        $lot->setMerchandise($ingredient);
        $lot->setManufactureDate($manufactureDate);
        $lot->setExpiryDate($expiry);
        $lot->setReceivedAt($receivedAt);
        $lot->setStatus(InventoryLotStatus::Available->value);
        $lot->setNote('Seed dữ liệu tồn kho để test xuất kho nguyên liệu sản xuất');
        $this->manager->persist($lot);

        $movement = new InventoryMovement();
        $movement->setMovementType(InventoryMovementType::StockIn->value);
        $movement->setSourceRef([
            'seed' => true,
            'ingredientId' => $ingredient->getId(),
            'lotSeq' => $seq,
        ]);
        $movement->setWarehouse($this->warehouse);
        $movement->setMerchandise($ingredient);
        $movement->setLot($lot);
        $movement->setQuantityBaseDelta($quantityBase);
        $movement->setBaseUnit($ingredient->getBaseUnit());
        $movement->setUnitCostBase($unitCostBase);
        $movement->setNote('Seed tồn kho nguyên liệu ' . ($ingredient->getName() ?? $ingredient->getCode()));
        $movement->setPostedAt($receivedAt);
        $movement->setPostedBy($this->systemUser);
        $this->manager->persist($movement);

        $balance = new InventoryBalance();
        $balance->setWarehouse($this->warehouse);
        $balance->setMerchandise($ingredient);
        $balance->setLot($lot);
        $balance->setOnHandBaseQuantity($quantityBase);
        $this->manager->persist($balance);
    }
}
