<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\MerchandiseDTO;
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
            fn(Merchandise $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
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

        return $data;
    }

    public function create(MerchandiseDTO $dto): array
    {
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
            $category = $this->categoryRepository->find($dto->categoryId);
            if ($category) {
                $item->setCategory($category);
            }
        }

        if ($dto->baseUnitId) {
            $baseUnit = $this->unitRepository->find($dto->baseUnitId);
            if ($baseUnit) {
                $item->setBaseUnit($baseUnit);
            }
        }

        $this->entityManager->persist($item);

        if ($dto->baseUnitId || !empty($dto->conversions)) {
            $this->saveConversionsAndUnits($item, $dto->conversions, $dto->baseUnitId);
        }

        if (!empty($dto->providers)) {
            $this->saveProviders($item, $dto->providers);
        }

        $this->entityManager->flush();

        $data = $item->jsonSerialize();
        $data['conversions'] = $this->getConversionsData($item->getId());
        $data['providers'] = $this->getProvidersData($item->getId());

        return $data;
    }

    public function update(int $id, MerchandiseDTO $dto): array
    {
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

        if ($dto->categoryId) {
            $category = $this->categoryRepository->find($dto->categoryId);
            if ($category) {
                $item->setCategory($category);
            } else {
                $item->setCategory(null);
            }
        } else {
            $item->setCategory(null);
        }

        if ($dto->baseUnitId) {
            $baseUnit = $this->unitRepository->find($dto->baseUnitId);
            if ($baseUnit) {
                $item->setBaseUnit($baseUnit);
            } else {
                $item->setBaseUnit(null);
            }
        } else {
            $item->setBaseUnit(null);
        }

        $this->saveConversionsAndUnits($item, $dto->conversions, $dto->baseUnitId);

        $this->saveProviders($item, $dto->providers);

        $this->entityManager->flush();

        $data = $item->jsonSerialize();
        $data['conversions'] = $this->getConversionsData($item->getId());
        $data['providers'] = $this->getProvidersData($item->getId());

        return $data;
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
            'fromValue' => $c->getFromValue(),
            'toUnitId' => $c->getToUnit()?->getId(),
            'toUnit' => $c->getToUnit()?->jsonSerialize(),
            'toValue' => $c->getToValue(),
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
        $providerRepo = $this->merchandiseProviderRepository;
        $mProviders = $providerRepo->findBy(['merchandise' => $merchandiseId]);

        $mConversionRepo = $this->merchandiseUnitConversionRepository;
        $mProviderUnitRepo = $this->merchandiseProviderUnitRepository;
        $mProviderPriceRepo = $this->merchandiseProviderPriceRepository;

        // Lấy conversions gốc của Merchandise
        $baseConversions = $mConversionRepo->findBy(['merchandise' => $merchandiseId], ['sortOrder' => 'ASC']);

        $result = [];
        foreach ($mProviders as $mp) {
            $mpId = $mp->getId();

            // Lấy các units đã lưu cho provider này
            $providerUnits = $mProviderUnitRepo->findBy(['merchandiseProvider' => $mpId]);
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
                        $fromVal = 1.0;
                        $toVal = $fromFactor / $toFactor;
                    }
                }

                $conversions[] = [
                    'fromUnitId' => $fromUnitId,
                    'fromUnit' => $bc->getFromUnit()?->jsonSerialize(),
                    'fromValue' => sprintf('%.2f', $fromVal),
                    'toUnitId' => $toUnitId,
                    'toUnit' => $bc->getToUnit()?->jsonSerialize(),
                    'toValue' => sprintf('%.2f', $toVal),
                    'sortOrder' => $bc->getSortOrder(),
                ];
            }

            // Lấy danh sách giá mặc định theo từng đơn vị của provider
            $prices = [];
            $providerPrices = $mProviderPriceRepo->findBy(['merchandiseProvider' => $mpId]);
            foreach ($providerPrices as $pp) {
                $prices[] = [
                    'unitId' => $pp->getUnit()->getId(),
                    'price' => $pp->getPrice(),
                    'discountRate' => $pp->getDiscountRate(),
                    'discountAmount' => $pp->getDiscountAmount(),
                    'priceAfterDiscount' => $pp->getPriceAfterDiscount(),
                    'effectiveFrom' => $pp->getEffectiveFrom()?->format('Y-m-d'),
                    'effectiveTo' => $pp->getEffectiveTo()?->format('Y-m-d'),
                ];
            }

            $result[] = [
                'id' => $mpId,
                'providerId' => $mp->getProvider()?->getId(),
                'provider' => $mp->getProvider()?->jsonSerialize(),
                'unitConfigMode' => $mp->getUnitConfigMode(),
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

                usort($unitFactors, fn($a, $b) => $a['factor'] <=> $b['factor']);

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
                    if (empty($priceItem['unitId']) || !isset($priceItem['price'])) {
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
                        $mUnitRepo = $this->merchandiseUnitRepository;
                        $mUnit = $mUnitRepo->findOneBy(['merchandise' => $merchandise, 'unit' => $unitObj]);
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
                    $mPrice->setPrice((string)($priceItem['price'] ?? 0));
                    $mPrice->setDiscountRate((string)($priceItem['discountRate'] ?? '0.00'));
                    $mPrice->setDiscountAmount((string)($priceItem['discountAmount'] ?? '0.00'));
                    $mPrice->setPriceAfterDiscount((string)($priceItem['priceAfterDiscount'] ?? $priceItem['price'] ?? 0));

                    if (!empty($priceItem['effectiveFrom'])) {
                        $mPrice->setEffectiveFrom(new \DateTime($priceItem['effectiveFrom']));
                    }
                    if (!empty($priceItem['effectiveTo'])) {
                        $mPrice->setEffectiveTo(new \DateTime($priceItem['effectiveTo']));
                    }

                    $mPrice->setCurrency('VND');
                    $mPrice->setIsDefault(true);
                    $mPrice->setStatus(1);

                    $this->entityManager->persist($mPrice);
                }
            }
        }
    }
}
