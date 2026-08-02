<?php

namespace App\Class\Warehouse;

use App\Repository\GeneralSettingRepository;
use App\Repository\WarehouseRepository;

class Warehoue
{
    public function __construct(
        private readonly GeneralSettingRepository $generalSettingRepository,
        private readonly WarehouseRepository $warehouseRepository,
    ) {}

    public function getReceivingWarehouse(): \App\Entity\Warehouse {
        return $this->getConfiguredWarehouse(
            'RECEIVE_FROM_PROVIDER_WAREHOUSE_ID',
            'Chưa cấu hình kho nhận hàng từ nhà cung cấp',
            'Kho nhận hàng từ nhà cung cấp không tồn tại',
        );
    }

    public function getProductionMaterialWarehouse(): \App\Entity\Warehouse {
        return $this->getConfiguredWarehouse(
            'PRODUCTION_MATERIAL_WAREHOUSE_ID',
            'Chưa cấu hình kho xuất nguyên liệu để sản xuất',
            'Kho xuất nguyên liệu để sản xuất không tồn tại',
        );
    }

    public function getProductionFinishedGoodsWarehouse(): \App\Entity\Warehouse {
        return $this->getConfiguredWarehouse(
            'PRODUCTION_FINISHED_GOODS_WAREHOUSE_ID',
            'Chưa cấu hình kho lưu thành phẩm sản xuất',
            'Kho lưu thành phẩm sản xuất không tồn tại',
        );
    }

    private function getConfiguredWarehouse(
        string $configKey,
        string $missingConfigMessage,
        string $notFoundMessage,
    ): \App\Entity\Warehouse {
        $configs = $this->generalSettingRepository->getAllConfig();
        $warehouseId = $configs[$configKey] ?? null;

        if (!$warehouseId) {
            throw new \Exception($missingConfigMessage);
        }

        $warehouse = $this->warehouseRepository->find((int) $warehouseId);

        if ($warehouse === null) {
            throw new \Exception($notFoundMessage);
        }

        return $warehouse;
    }
}
