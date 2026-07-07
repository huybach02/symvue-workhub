<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\MerchandiseDTO;
use App\DTO\RecipeDTO;
use App\DTO\RecipeItemDTO;
use App\Entity\Category;
use App\Entity\Merchandise;
use App\Entity\MerchandiseProvider;
use App\Entity\MerchandiseProviderPrice;
use App\Entity\MerchandiseProviderUnit;
use App\Entity\MerchandiseUnit;
use App\Entity\MerchandiseUnitConversion;
use App\Entity\Provider;
use App\Entity\Unit;
use App\Repository\CategoryRepository;
use App\Repository\MerchandiseProviderPriceRepository;
use App\Repository\MerchandiseProviderRepository;
use App\Repository\MerchandiseProviderUnitRepository;
use App\Repository\MerchandiseRepository;
use App\Repository\MerchandiseUnitConversionRepository;
use App\Repository\MerchandiseUnitRepository;
use App\Repository\ProviderRepository;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\MerchandiseRecipe;
use App\Entity\MerchandiseRecipeItem;
use App\Repository\MerchandiseRecipeRepository;
use App\Repository\MerchandiseRecipeItemRepository;

class MerchandiseService
{
    public function __construct(
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly UnitRepository $unitRepository,
        private readonly MerchandiseUnitConversionRepository $merchandiseUnitConversionRepository,
        private readonly MerchandiseUnitRepository $merchandiseUnitRepository,
        private readonly MerchandiseProviderRepository $merchandiseProviderRepository,
        private readonly MerchandiseProviderUnitRepository $merchandiseProviderUnitRepository,
        private readonly MerchandiseProviderPriceRepository $merchandiseProviderPriceRepository,
        private readonly ProviderRepository $providerRepository,
        private readonly MerchandiseRecipeRepository $merchandiseRecipeRepository,
        private readonly MerchandiseRecipeItemRepository $merchandiseRecipeItemRepository,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->merchandiseRepository->createQueryBuilder('e');

        $relationFields = [
            'category' => [
                'joinField' => 'e.category',
                'alias' => 'cat',
                'targetField' => 'name',
            ],
        ];

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'e',
            $relationFields,
        );

        // Map collection to JSON
        $result['collection'] = array_map(
            function (Merchandise $item) {
                $data = $item->jsonSerialize();
                $data['conversions'] = $this->getConversionsData($item->getId());
                return $data;
            },
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->merchandiseRepository->createQueryBuilder('e')
            ->andWhere('e.status = 1');

        $relationFields = [
            'category' => [
                'joinField' => 'e.category',
                'alias' => 'cat',
                'targetField' => 'name',
            ],
        ];

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'e',
            $relationFields,
        );

        return array_map(function (Merchandise $item) {
            return [
                'id' => $item->getId(),
                'code' => $item->getCode(),
                'name' => $item->getName(),
                'baseUnitId' => $item->getBaseUnit()?->getId(),
                'isSingleUnit' => $item->isSingleUnit(),
                'conversions' => $this->getConversionsData($item->getId()),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->merchandiseRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $data = $item->jsonSerialize();
        $data['conversions'] = $this->getConversionsData($id);
        $data['providers'] = $this->getProvidersData($id);
        $data['recipe'] = $this->getRecipeData($id);

        return $data;
    }

    public function create(MerchandiseDTO $dto): array
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $item = new Merchandise();

            $item->setCode($dto->code);
            $item->setName($dto->name);
            $item->setType($dto->type);
            $item->setProfit($dto->profit);
            $item->setDescription($dto->description);
            $item->setNotes($dto->notes);
            $item->setStockAlertQuantity($dto->stockAlertQuantity);
            $item->setStatus($dto->status);
            $item->setIsSingleUnit((bool)$dto->isSingleUnit);

            if ($dto->categoryId) {
                $item->setCategory($this->categoryRepository->find($dto->categoryId));
            }

            if ($dto->baseUnitId) {
                $item->setBaseUnit($this->unitRepository->find($dto->baseUnitId));
            }

            $this->entityManager->persist($item);

            if ($dto->baseUnitId || !empty($dto->conversions)) {
                $this->saveConversionsAndUnits($item, $dto->conversions, $dto->baseUnitId);
            }

            if ($dto->type === 'finished_product') {
                $item->setFinishedProductSource($dto->finishedProductSource ?? 'supplier');
            } else {
                $item->setFinishedProductSource(null);
            }

            if ($item->getType() === 'ingredient') {
                if (!empty($dto->providers)) {
                    $this->saveProviders($item, $dto->providers);
                }
                $this->deleteRecipe($item);
            } else {
                if (!empty($dto->providers)) {
                    $this->saveProviders($item, $dto->providers);
                }
                if ($dto->finishedProductSource === 'production' && $dto->recipe !== null && $dto->recipe->outputUnitId !== null && !empty($dto->recipe->items)) {
                    $this->saveRecipe($item, $dto->recipe);
                }
            }

            $this->entityManager->flush();

            $conn->commit();

            $data = $item->jsonSerialize();
            $data['conversions'] = $this->getConversionsData($item->getId());
            $data['providers'] = $this->getProvidersData($item->getId());
            $data['recipe'] = $this->getRecipeData($item->getId());

            return $data;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function update(int $id, MerchandiseDTO $dto): array
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $item = $this->merchandiseRepository->find($id);

            if (!$item) {
                throw new \Exception(t('error.not_found'));
            }

            $item->setCode($dto->code);
            $item->setName($dto->name);
            if ($dto->type) {
                $item->setType($dto->type);
            }
            $item->setProfit($dto->profit);
            $item->setDescription($dto->description);
            $item->setNotes($dto->notes);
            $item->setStockAlertQuantity($dto->stockAlertQuantity);
            $item->setStatus($dto->status);
            $item->setIsSingleUnit((bool)$dto->isSingleUnit);

            $item->setCategory($dto->categoryId ? $this->categoryRepository->find($dto->categoryId) : null);
            $item->setBaseUnit($dto->baseUnitId ? $this->unitRepository->find($dto->baseUnitId) : null);

            $this->saveConversionsAndUnits($item, $dto->conversions, $dto->baseUnitId);

            if ($item->getType() === 'finished_product') {
                $item->setFinishedProductSource($dto->finishedProductSource ?? 'supplier');
            } else {
                $item->setFinishedProductSource(null);
            }

            if ($item->getType() === 'ingredient') {
                $this->saveProviders($item, $dto->providers);
                $this->deleteRecipe($item);
            } else {
                if (!empty($dto->providers)) {
                    $this->saveProviders($item, $dto->providers);
                }
                if ($dto->finishedProductSource === 'production' && $dto->recipe !== null && $dto->recipe->outputUnitId !== null && !empty($dto->recipe->items)) {
                    $this->saveRecipe($item, $dto->recipe);
                } elseif ($dto->finishedProductSource === 'supplier') {
                    $this->deleteRecipe($item);
                }
            }

            $this->entityManager->flush();

            $conn->commit();

            $data = $item->jsonSerialize();
            $data['conversions'] = $this->getConversionsData($item->getId());
            $data['providers'] = $this->getProvidersData($item->getId());
            $data['recipe'] = $this->getRecipeData($item->getId());

            return $data;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $item = $this->merchandiseRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function getConversionsData(int $merchandiseId): array
    {
        $conversions = $this->merchandiseUnitConversionRepository
            ->findBy(['merchandise' => $merchandiseId], ['sortOrder' => 'ASC']);

        return array_map(fn(MerchandiseUnitConversion $c) => [
            'id' => $c->getId(),
            'fromUnitId' => $c->getFromUnit()?->getId(),
            'fromUnit' => $c->getFromUnit()?->jsonSerialize(),
            'fromValue' => formatDecimal($c->getFromValue()),
            'toUnitId' => $c->getToUnit()?->getId(),
            'toUnit' => $c->getToUnit()?->jsonSerialize(),
            'toValue' => formatDecimal($c->getToValue()),
            'sortOrder' => $c->getSortOrder(),
        ], $conversions);
    }

    public function saveConversionsAndUnits(Merchandise $merchandise, ?array $conversionsData, ?int $baseUnitId): void
    {
        // 1. Xóa các MerchandiseUnitConversion cũ
        $conversionRepo = $this->merchandiseUnitConversionRepository;
        $oldConversions = $conversionRepo->findBy(['merchandise' => $merchandise]);
        foreach ($oldConversions as $oldC) {
            $this->entityManager->remove($oldC);
        }

        // 2. Xóa các MerchandiseUnit cũ
        $merchandiseUnitRepo = $this->merchandiseUnitRepository;
        $oldUnits = $merchandiseUnitRepo->findBy(['merchandise' => $merchandise]);
        foreach ($oldUnits as $oldU) {
            $this->entityManager->remove($oldU);
        }

        if (empty($conversionsData)) {
            if ($baseUnitId) {
                $baseUnitEntity = $this->unitRepository->find($baseUnitId);
                if ($baseUnitEntity) {
                    $mUnit = new MerchandiseUnit();
                    $mUnit->setMerchandise($merchandise);
                    $mUnit->setUnit($baseUnitEntity);
                    $mUnit->setFactorToBase('1.0000');
                    $mUnit->setLevel(0);
                    $mUnit->setIsBase(true);
                    $mUnit->setLabel($baseUnitEntity->getName());
                    $this->entityManager->persist($mUnit);
                }
            }
            return;
        }

        $unitRepo = $this->unitRepository;

        // 3. Lưu các conversions mới
        $savedConversions = [];
        foreach ($conversionsData as $index => $cData) {
            if (empty($cData['fromUnitId']) || empty($cData['toUnitId'])) {
                continue;
            }
            $fromUnit = $unitRepo->find($cData['fromUnitId']);
            $toUnit = $unitRepo->find($cData['toUnitId']);

            if (!$fromUnit || !$toUnit) {
                continue;
            }

            $conversion = new MerchandiseUnitConversion();
            $conversion->setMerchandise($merchandise);
            $conversion->setFromUnit($fromUnit);
            $conversion->setFromValue((string)($cData['fromValue'] ?? 1));
            $conversion->setToUnit($toUnit);
            $conversion->setToValue((string)($cData['toValue'] ?? 1));
            $conversion->setSortOrder((int)($cData['sortOrder'] ?? $index));

            $this->entityManager->persist($conversion);
            $savedConversions[] = $conversion;
        }

        if (!$baseUnitId) {
            return;
        }

        // 4. Xây dựng đồ thị kề từ các conversions đã lưu để tính factorToBase
        $adj = [];
        $allUnitIds = [];
        foreach ($savedConversions as $c) {
            $fromId = $c->getFromUnit()->getId();
            $toId = $c->getToUnit()->getId();
            $fromVal = (float)$c->getFromValue();
            $toVal = (float)$c->getToValue();

            if ($fromVal <= 0 || $toVal <= 0) {
                continue;
            }

            $ratio = $toVal / $fromVal;

            $adj[$fromId][] = ['node' => $toId, 'ratio' => $ratio, 'direction' => 'forward'];
            $adj[$toId][] = ['node' => $fromId, 'ratio' => $ratio, 'direction' => 'backward'];

            $allUnitIds[$fromId] = $c->getFromUnit();
            $allUnitIds[$toId] = $c->getToUnit();
        }

        // Đảm bảo base unit có trong allUnitIds
        if (!isset($allUnitIds[$baseUnitId])) {
            $baseUnitEntity = $unitRepo->find($baseUnitId);
            if ($baseUnitEntity) {
                $allUnitIds[$baseUnitId] = $baseUnitEntity;
            }
        }

        // BFS loang từ baseUnitId
        $factors = [$baseUnitId => 1.0];
        $queue = [$baseUnitId];
        $visited = [$baseUnitId => true];

        while (!empty($queue)) {
            $u = array_shift($queue);
            $uFactor = $factors[$u];

            if (isset($adj[$u])) {
                foreach ($adj[$u] as $edge) {
                    $v = $edge['node'];
                    if (!isset($visited[$v])) {
                        $visited[$v] = true;
                        if ($edge['direction'] === 'forward') {
                            $factors[$v] = $uFactor / $edge['ratio'];
                        } else {
                            $factors[$v] = $edge['ratio'] * $uFactor;
                        }
                        $queue[] = $v;
                    }
                }
            }
        }

        // Sắp xếp các đơn vị theo factor để tính level
        $unitFactors = [];
        foreach ($allUnitIds as $uId => $unitObj) {
            $factor = $factors[$uId] ?? 1.0;
            $unitFactors[] = [
                'id' => $uId,
                'unit' => $unitObj,
                'factor' => $factor
            ];
        }

        // Sắp xếp base unit lên đầu tiên
        usort($unitFactors, function ($a, $b) use ($baseUnitId) {
            if ($a['id'] === $baseUnitId) {
                return -1;
            }
            if ($b['id'] === $baseUnitId) {
                return 1;
            }
            return $a['factor'] <=> $b['factor'];
        });

        $levels = [];
        foreach ($unitFactors as $level => $uf) {
            $levels[$uf['id']] = $level;
        }

        // 5. Lưu các MerchandiseUnit mới
        foreach ($unitFactors as $uf) {
            $uId = $uf['id'];
            $unitObj = $uf['unit'];
            $factor = $uf['factor'];
            $level = $levels[$uId];

            $mUnit = new MerchandiseUnit();
            $mUnit->setMerchandise($merchandise);
            $mUnit->setUnit($unitObj);
            $mUnit->setFactorToBase(sprintf('%.4f', $factor));
            $mUnit->setLevel($level);
            $mUnit->setIsBase($uId === $baseUnitId);

            // Tìm label phù hợp dựa trên conversion
            $label = $unitObj->getName();
            foreach ($savedConversions as $c) {
                if ($c->getFromUnit()->getId() === $uId) {
                    $toValFloat = (float)$c->getToValue();
                    $label = sprintf('%s %g %s', $unitObj->getName(), $toValFloat, $c->getToUnit()->getName());
                    break;
                }
            }
            $mUnit->setLabel($label);

            $this->entityManager->persist($mUnit);
        }
    }

    private function getProvidersData(int $merchandiseId): array
    {
        $mProviders = $this->merchandiseProviderRepository->findBy(['merchandise' => $merchandiseId]);

        // Lấy conversions gốc của Merchandise
        $baseConversions = $this->merchandiseUnitConversionRepository->findBy(['merchandise' => $merchandiseId], ['sortOrder' => 'ASC']);

        $result = [];
        foreach ($mProviders as $mp) {
            $mpId = $mp->getId();

            // Lấy các units đã lưu cho provider này
            $providerUnits = $this->merchandiseProviderUnitRepository->findBy(['merchandiseProvider' => $mpId]);
            $providerUnitMap = [];
            foreach ($providerUnits as $pu) {
                $providerUnitMap[$pu->getUnit()->getId()] = (float)$pu->getFactorToBase();
            }

            // Tái cấu trúc conversions cho provider
            $conversions = [];
            foreach ($baseConversions as $bc) {
                $fromUnitId = $bc->getFromUnit()->getId();
                $toUnitId = $bc->getToUnit()->getId();

                $fromVal = (float)$bc->getFromValue();
                $toVal = (float)$bc->getToValue();

                // Nếu provider dùng custom config và có lưu factor riêng
                if ($mp->getUnitConfigMode() === 'custom' && isset($providerUnitMap[$fromUnitId]) && isset($providerUnitMap[$toUnitId])) {
                    $fromFactor = $providerUnitMap[$fromUnitId];
                    $toFactor = $providerUnitMap[$toUnitId];

                    if ($fromFactor > 0 && $toFactor > 0) {
                        $fromVal = (float)$bc->getFromValue();
                        $toVal = $fromVal * ($fromFactor / $toFactor);
                    }
                }

                $conversions[] = [
                    'fromUnitId' => $fromUnitId,
                    'fromUnit' => $bc->getFromUnit()?->jsonSerialize(),
                    'fromValue' => formatDecimal((string)$fromVal),
                    'toUnitId' => $toUnitId,
                    'toUnit' => $bc->getToUnit()?->jsonSerialize(),
                    'toValue' => formatDecimal((string)$toVal),
                    'sortOrder' => $bc->getSortOrder(),
                ];
            }

            // Lấy danh sách giá mặc định theo từng đơn vị của provider
            $prices = [];
            $providerPrices = $this->merchandiseProviderPriceRepository->findBy(['merchandiseProvider' => $mpId]);
            foreach ($providerPrices as $pp) {
                $prices[] = [
                    'unitId' => $pp->getUnit()->getId(),
                    'isDefault' => $pp->isDefault(),
                    'price' => formatDecimal($pp->getPrice()),
                    'discountRate' => formatDecimal($pp->getDiscountRate()),
                    'discountAmount' => formatDecimal($pp->getDiscountAmount()),
                    'priceAfterDiscount' => formatDecimal($pp->getPriceAfterDiscount()),
                    'effectiveFrom' => $pp->getEffectiveFrom()?->format('Y-m-d'),
                    'effectiveTo' => $pp->getEffectiveTo()?->format('Y-m-d'),
                ];
            }

            $result[] = [
                'id' => $mpId,
                'providerId' => $mp->getProvider()?->getId(),
                'provider' => $mp->getProvider()?->jsonSerialize(),
                'unitConfigMode' => $mp->getUnitConfigMode(),
                'isDefault' => $mp->isDefault(),
                'conversions' => $conversions,
                'prices' => $prices,
            ];
        }

        return $result;
    }

    public function saveProviders(Merchandise $merchandise, ?array $providersData): void
    {
        // 1. Xóa các MerchandiseProvider cũ
        $oldProviders = $this->merchandiseProviderRepository->findBy(['merchandise' => $merchandise]);
        foreach ($oldProviders as $oldP) {
            $this->entityManager->remove($oldP);
        }
        $this->entityManager->flush();

        if (empty($providersData)) {
            return;
        }

        $baseUnit = $merchandise->getBaseUnit();
        if (!$baseUnit) {
            return;
        }
        $baseUnitId = $baseUnit->getId();

        // 2. Lưu từng MerchandiseProvider mới
        $hasDefault = false;
        foreach ($providersData as $pData) {
            if (empty($pData['providerId'])) {
                continue;
            }

            $providerObj = $this->providerRepository->find($pData['providerId']);
            if (!$providerObj) {
                continue;
            }

            $mProvider = new MerchandiseProvider();
            $mProvider->setMerchandise($merchandise);
            $mProvider->setProvider($providerObj);
            $mProvider->setUnitConfigMode($pData['unitConfigMode'] ?? 'custom');

            $isDefault = (bool)($pData['isDefault'] ?? false);
            if ($isDefault && !$hasDefault) {
                $mProvider->setIsDefault(true);
                $hasDefault = true;
            } else {
                $mProvider->setIsDefault(false);
            }

            $this->entityManager->persist($mProvider);

            // Xử lý conversions riêng của nhà cung cấp để tạo MerchandiseProviderUnit
            $savedProviderUnits = [];
            $factors = [$baseUnitId => 1.0];
            $allUnits = [$baseUnitId => $baseUnit];

            if (!empty($pData['conversions'])) {
                // BFS tính toán factorToBase cho các đơn vị của provider này
                $adj = [];
                $validConversions = [];

                foreach ($pData['conversions'] as $c) {
                    if (empty($c['fromUnitId']) || empty($c['toUnitId'])) {
                        continue;
                    }

                    $fromUnit = $this->unitRepository->find($c['fromUnitId']);
                    $toUnit = $this->unitRepository->find($c['toUnitId']);
                    if (!$fromUnit || !$toUnit) {
                        continue;
                    }

                    $fromVal = (float)$c['fromValue'];
                    $toVal = (float)$c['toValue'];
                    if ($fromVal <= 0 || $toVal <= 0) {
                        continue;
                    }

                    $ratio = $toVal / $fromVal;
                    $fromId = (int)$c['fromUnitId'];
                    $toId = (int)$c['toUnitId'];

                    $adj[$fromId][] = ['node' => $toId, 'ratio' => $ratio, 'direction' => 'forward'];
                    $adj[$toId][] = ['node' => $fromId, 'ratio' => $ratio, 'direction' => 'backward'];

                    $allUnits[$fromId] = $fromUnit;
                    $allUnits[$toId] = $toUnit;
                    $validConversions[] = [
                        'fromUnit' => $fromUnit,
                        'toUnit' => $toUnit,
                        'toValue' => $toVal,
                    ];
                }

                $queue = [$baseUnitId];
                $visited = [$baseUnitId => true];

                while (!empty($queue)) {
                    $u = array_shift($queue);
                    $uFactor = $factors[$u];

                    if (isset($adj[$u])) {
                        foreach ($adj[$u] as $edge) {
                            $v = $edge['node'];
                            if (!isset($visited[$v])) {
                                $visited[$v] = true;
                                if ($edge['direction'] === 'forward') {
                                    $factors[$v] = $uFactor / $edge['ratio'];
                                } else {
                                    $factors[$v] = $edge['ratio'] * $uFactor;
                                }
                                $queue[] = $v;
                            }
                        }
                    }
                }

                // Sắp xếp theo factor để tính level cho provider units
                $unitFactors = [];
                foreach ($allUnits as $uId => $unitObj) {
                    $factor = $factors[$uId] ?? 1.0;
                    $unitFactors[] = [
                        'id' => $uId,
                        'unit' => $unitObj,
                        'factor' => $factor
                    ];
                }

                usort($unitFactors, function ($a, $b) use ($baseUnitId) {
                    if ($a['id'] === $baseUnitId) {
                        return -1;
                    }
                    if ($b['id'] === $baseUnitId) {
                        return 1;
                    }
                    return $a['factor'] <=> $b['factor'];
                });

                $levels = [];
                foreach ($unitFactors as $level => $uf) {
                    $levels[$uf['id']] = $level;
                }

                // Tạo các MerchandiseProviderUnit
                foreach ($unitFactors as $uf) {
                    $uId = $uf['id'];
                    $unitObj = $uf['unit'];
                    $factor = $uf['factor'];
                    $level = $levels[$uId];

                    $mpUnit = new MerchandiseProviderUnit();
                    $mpUnit->setMerchandiseProvider($mProvider);
                    $mpUnit->setUnit($unitObj);
                    $mpUnit->setFactorToBase(sprintf('%.4f', $factor));
                    $mpUnit->setLevel($level);
                    $mpUnit->setIsBase($uId === $baseUnitId);

                    // Build label
                    $label = $unitObj->getName();
                    foreach ($validConversions as $vc) {
                        if ($vc['fromUnit']->getId() === $uId) {
                            $label = sprintf('%s %g %s', $unitObj->getName(), $vc['toValue'], $vc['toUnit']->getName());
                            break;
                        }
                    }
                    $mpUnit->setLabel($label);

                    $this->entityManager->persist($mpUnit);
                    $savedProviderUnits[$uId] = $mpUnit;
                }
            }

            // Xử lý prices cho nhà cung cấp để tạo MerchandiseProviderPrice
            if (!empty($pData['prices'])) {
                foreach ($pData['prices'] as $priceItem) {
                    if (empty($priceItem['unitId']) || !isset($priceItem['price']) || $priceItem['price'] === '' || $priceItem['price'] === null) {
                        continue;
                    }

                    $unitObj = $this->unitRepository->find($priceItem['unitId']);
                    if (!$unitObj) {
                        continue;
                    }

                    $uId = (int)$priceItem['unitId'];

                    // Lấy snapshot label và factor từ MerchandiseProviderUnit mới tạo
                    $unitLabelSnapshot = $unitObj->getName();
                    $factorToBaseSnapshot = '1.0000';

                    if (isset($savedProviderUnits[$uId])) {
                        $unitLabelSnapshot = $savedProviderUnits[$uId]->getLabel();
                        $factorToBaseSnapshot = $savedProviderUnits[$uId]->getFactorToBase();
                    } else {
                        // Backup lấy từ MerchandiseUnit của Merchandise
                        $mUnit = $this->merchandiseUnitRepository->findOneBy(['merchandise' => $merchandise, 'unit' => $unitObj]);
                        if ($mUnit) {
                            $unitLabelSnapshot = $mUnit->getLabel();
                            $factorToBaseSnapshot = $mUnit->getFactorToBase();
                        }
                    }

                    $mPrice = new MerchandiseProviderPrice();
                    $mPrice->setMerchandiseProvider($mProvider);
                    $mPrice->setUnit($unitObj);
                    $mPrice->setUnitLabelSnapshot($unitLabelSnapshot);
                    $mPrice->setFactorToBaseSnapshot($factorToBaseSnapshot);
                    $priceVal = ($priceItem['price'] !== null && $priceItem['price'] !== '') ? (string)$priceItem['price'] : '0.00';
                    $discountRateVal = ($priceItem['discountRate'] !== null && $priceItem['discountRate'] !== '') ? (string)$priceItem['discountRate'] : '0.00';
                    $discountAmountVal = ($priceItem['discountAmount'] !== null && $priceItem['discountAmount'] !== '') ? (string)$priceItem['discountAmount'] : '0.00';
                    $priceAfterDiscountVal = ($priceItem['priceAfterDiscount'] !== null && $priceItem['priceAfterDiscount'] !== '') ? (string)$priceItem['priceAfterDiscount'] : $priceVal;

                    $mPrice->setPrice($priceVal);
                    $mPrice->setDiscountRate($discountRateVal);
                    $mPrice->setDiscountAmount($discountAmountVal);
                    $mPrice->setPriceAfterDiscount($priceAfterDiscountVal);

                    if (!empty($priceItem['effectiveFrom'])) {
                        $mPrice->setEffectiveFrom(new \DateTime($priceItem['effectiveFrom']));
                    }
                    if (!empty($priceItem['effectiveTo'])) {
                        $mPrice->setEffectiveTo(new \DateTime($priceItem['effectiveTo']));
                    }

                    $mPrice->setCurrency('VND');
                    $mPrice->setIsDefault(!empty($priceItem['isDefault']));
                    $mPrice->setStatus(1);

                    $this->entityManager->persist($mPrice);
                }
            }
        }
    }

    public function getRecipeData(int $merchandiseId): ?array
    {
        $recipe = $this->merchandiseRecipeRepository->findOneBy(['finishedProduct' => $merchandiseId]);
        if (!$recipe) {
            return null;
        }

        return $recipe->jsonSerialize();
    }

    public function deleteRecipe(Merchandise $finishedProduct): void
    {
        $recipe = $this->merchandiseRecipeRepository->findOneBy(['finishedProduct' => $finishedProduct]);
        if ($recipe) {
            $this->entityManager->remove($recipe);
        }
    }

    public function saveRecipe(Merchandise $finishedProduct, ?RecipeDTO $recipeDTO): void
    {
        if ($recipeDTO === null) {
            return;
        }

        $this->entityManager->flush();

        $outputQuantity = $recipeDTO->outputQuantity ?? 1;
        $outputUnit = $this->unitRepository->find($recipeDTO->outputUnitId);

        $finishedProductUnit = $this->merchandiseUnitRepository->findOneBy([
            'merchandise' => $finishedProduct,
            'unit' => $outputUnit
        ]);
        if (!$finishedProductUnit) {
            throw new \Exception(t('recipe_error.output_unit_not_configured'));
        }

        if (empty($recipeDTO->items)) {
            throw new \Exception(t('recipe_error.items_required'));
        }

        $recipe = $this->merchandiseRecipeRepository->findOneBy(['finishedProduct' => $finishedProduct]);
        if (!$recipe) {
            $recipe = new MerchandiseRecipe();
            $recipe->setFinishedProduct($finishedProduct);
            $this->entityManager->persist($recipe);
        } else {
            foreach ($recipe->getItems() as $oldItem) {
                $this->entityManager->remove($oldItem);
            }
            $recipe->getItems()->clear();
        }

        $recipe->setOutputQuantity(sprintf('%.4f', $outputQuantity));
        $recipe->setOutputUnit($outputUnit);

        $outputFactor = $finishedProductUnit->getFactorToBase();
        $recipe->setOutputFactorToBaseSnapshot($outputFactor);
        $recipe->setOutputBaseQuantitySnapshot(sprintf('%.4f', (float)$outputQuantity * (float)$outputFactor));
        $recipe->setVersion((int)($recipeDTO->version ?? 1));
        $recipe->setIsActive(true);
        $recipe->setStatus(1);
        $recipe->setNotes($recipeDTO->notes ?? null);

        $seenIngredientIds = [];
        foreach ($recipeDTO->items as $index => $itemDTO) {
            /** @var RecipeItemDTO $itemDTO */
            $ingredientId = $itemDTO->ingredientId;

            if (in_array($ingredientId, $seenIngredientIds, true)) {
                throw new \Exception(t('recipe_error.ingredient_duplicate', ['%index%' => $index + 1]));
            }
            $seenIngredientIds[] = $ingredientId;

            $ingredient = $this->merchandiseRepository->find($ingredientId);
            if ($ingredient->getType() !== 'ingredient') {
                throw new \Exception(t('recipe_error.not_an_ingredient', ['%name%' => $ingredient->getName()]));
            }

            $qty = $itemDTO->quantity ?? 0;
            $unit = $this->unitRepository->find($itemDTO->unitId);

            $ingredientUnit = $this->merchandiseUnitRepository->findOneBy([
                'merchandise' => $ingredient,
                'unit' => $unit
            ]);
            if (!$ingredientUnit) {
                throw new \Exception(t('recipe_error.unit_not_configured', ['%name%' => $ingredient->getName()]));
            }

            $wasteRate = $itemDTO->wasteRate ?? 0;

            $recipeItem = new MerchandiseRecipeItem();
            $recipeItem->setRecipe($recipe);
            $recipeItem->setIngredient($ingredient);
            $recipeItem->setQuantity(sprintf('%.4f', $qty));
            $recipeItem->setUnit($unit);

            $factor = $ingredientUnit->getFactorToBase();
            $recipeItem->setFactorToBaseSnapshot($factor);
            $recipeItem->setBaseQuantitySnapshot(sprintf('%.4f', (float)$qty * (float)$factor));
            $recipeItem->setWasteRate(sprintf('%.2f', $wasteRate));
            $recipeItem->setSortOrder((int)($itemDTO->sortOrder ?? $index));
            $recipeItem->setNotes($itemDTO->notes ?? null);

            $this->entityManager->persist($recipeItem);
        }
    }

    // Lấy giá của nguyên liệu hoặc thành phẩm nhập từ nhà cung cấp theo đơn vị tính
    public function getPriceMerchandiseByUnitId(int $merchandiseId, int $unitId)
    {
        $merchandise = $this->merchandiseRepository->find($merchandiseId);
        if (!$merchandise) {
            throw new \Exception(t('error.not_found'));
        }

        $unit = $this->unitRepository->find($unitId);
        if (!$unit) {
            throw new \Exception(t('error.not_found'));
        }

        $defaultProvider = $this->merchandiseProviderRepository->findOneBy([
            'merchandise' => $merchandise,
            'isDefault' => true
        ]);

        // Fallback: Nếu không có nhà cung cấp nào được đánh dấu mặc định, lấy nhà cung cấp đầu tiên
        if (!$defaultProvider) {
            $defaultProvider = $this->merchandiseProviderRepository->findOneBy([
                'merchandise' => $merchandise
            ]);
        }

        if (!$defaultProvider) {
            return 0.0;
        }

        $providerPrice = $this->merchandiseProviderPriceRepository->findOneBy([
            'merchandiseProvider' => $defaultProvider,
            'unit' => $unit
        ]);

        $conversionRate = 1.0;
        // Xử lý case nếu không tìm thấy providerPrice tương ứng theo unitId => Tìm providerPrice có isDefault = true của merchandise, sau đó từ providerPrice default này sẽ quy đổi giá sang unit cần tính
        if (!$providerPrice) {
            $defaultProviderPrice = $this->merchandiseProviderPriceRepository->findOneBy([
                'merchandiseProvider' => $defaultProvider,
                'isDefault' => true
            ]);
            if ($defaultProviderPrice) {
                $providerPrice = $defaultProviderPrice;
                $sourceUnit = $defaultProviderPrice->getUnit();

                $sourceMUnit = $this->merchandiseUnitRepository->findOneBy([
                    'merchandise' => $merchandise,
                    'unit' => $sourceUnit
                ]);
                $targetMUnit = $this->merchandiseUnitRepository->findOneBy([
                    'merchandise' => $merchandise,
                    'unit' => $unit
                ]);

                $sourceFactor = $sourceMUnit ? (float)$sourceMUnit->getFactorToBase() : 1.0;
                $targetFactor = $targetMUnit ? (float)$targetMUnit->getFactorToBase() : 1.0;

                if ($sourceFactor > 0) {
                    $conversionRate = $targetFactor / $sourceFactor;
                }
            }
        }

        // Kiểm tra time hiện tại có nằm trong khoảng effectiveFrom và effectiveTo không
        $currentTime = new \DateTime();
        $finalPrice = (float)$providerPrice->getPrice();

        $hasDiscount = false;
        $from = $providerPrice->getEffectiveFrom();
        $to = $providerPrice->getEffectiveTo();

        if ($from && $to) {
            if ($currentTime >= $from && $currentTime <= $to) {
                $hasDiscount = true;
            }
        } elseif ($from) {
            if ($currentTime >= $from) {
                $hasDiscount = true;
            }
        } elseif ($to) {
            if ($currentTime <= $to) {
                $hasDiscount = true;
            }
        }

        if ($hasDiscount) {
            $discountPrice = $providerPrice->getPriceAfterDiscount();
            if ($discountPrice !== null && $discountPrice !== '') {
                $finalPrice = (float)$discountPrice;
            }
        }

        return (int)formatDecimal(sprintf('%.4f', $finalPrice * $conversionRate));
    }

    // Lấy giá thành phẩm theo đơn vị tính (hỗ trợ đệ quy tính giá từ công thức sản xuất)
    public function getPriceFinishedProductByUnitId(int $merchandiseId, int $unitId, array $visited = [])
    {
        if (in_array($merchandiseId, $visited, true)) {
            return 0.0;
        }
        $visited[] = $merchandiseId;

        $merchandise = $this->merchandiseRepository->findOneBy(['id' => $merchandiseId, 'type' => 'finished_product']);
        if (!$merchandise) {
            throw new \Exception(t('error.not_found'));
        }

        $unit = $this->unitRepository->find($unitId);
        if (!$unit) {
            throw new \Exception(t('error.not_found'));
        }

        // Case 1: Finished product nhập từ nhà cung cấp
        if ($merchandise->getFinishedProductSource() === 'supplier') {
            return $this->getPriceMerchandiseByUnitId($merchandiseId, $unitId);
        }

        // Case 2: Finished product nhập từ sản xuất => Sử dụng recipe
        $recipe = $this->merchandiseRecipeRepository->findOneBy(['finishedProduct' => $merchandise]);
        if (!$recipe) {
            return 0.0;
        }

        $outputQuantity = (float)$recipe->getOutputQuantity();
        if ($outputQuantity <= 0) {
            $outputQuantity = 1.0;
        }

        $totalRecipeCost = 0.0;
        foreach ($recipe->getItems() as $item) {
            $ingredient = $item->getIngredient();
            $ingredientQty = (float)$item->getQuantity();
            $wasteRate = (float)$item->getWasteRate();

            // Tính lượng thực tế cần dùng sau hao hụt
            $actualQty = $ingredientQty * (1.0 + ($wasteRate / 100.0));

            $ingredientUnitId = $item->getUnit()->getId();
            $unitPrice = 0.0;

            if ($ingredient->getType() === 'finished_product') {
                $unitPrice = $this->getPriceFinishedProductByUnitId($ingredient->getId(), $ingredientUnitId, $visited);
            } else {
                $unitPrice = $this->getPriceMerchandiseByUnitId($ingredient->getId(), $ingredientUnitId);
            }

            $totalRecipeCost += $actualQty * $unitPrice;
        }

        $pricePerOutputUnit = $totalRecipeCost / $outputQuantity;

        // Quy đổi giá từ outputUnit sang unit cần tính
        $outputUnit = $recipe->getOutputUnit();
        if ($outputUnit->getId() === $unitId) {
            return (float)formatDecimal(sprintf('%.4f', $pricePerOutputUnit));
        }

        $sourceMUnit = $this->merchandiseUnitRepository->findOneBy([
            'merchandise' => $merchandise,
            'unit' => $outputUnit
        ]);
        $targetMUnit = $this->merchandiseUnitRepository->findOneBy([
            'merchandise' => $merchandise,
            'unit' => $unit
        ]);

        $sourceFactor = $sourceMUnit ? (float)$sourceMUnit->getFactorToBase() : 1.0;
        $targetFactor = $targetMUnit ? (float)$targetMUnit->getFactorToBase() : 1.0;

        $conversionRate = 1.0;
        if ($sourceFactor > 0) {
            $conversionRate = $targetFactor / $sourceFactor;
        }

        $finalPrice = $pricePerOutputUnit * $conversionRate;

        return (int)formatDecimal(sprintf('%.4f', $finalPrice));
    }

    // Lấy giá chung của nguyên liệu/thành phẩm bất kỳ theo đơn vị tính
    public function getPriceByUnitId(int $merchandiseId, int $unitId): float
    {
        $merchandise = $this->merchandiseRepository->find($merchandiseId);
        if (!$merchandise) {
            throw new \Exception(t('error.not_found'));
        }

        if ($merchandise->getType() === 'finished_product') {
            return $this->getPriceFinishedProductByUnitId($merchandiseId, $unitId);
        }

        return $this->getPriceMerchandiseByUnitId($merchandiseId, $unitId);
    }
}
