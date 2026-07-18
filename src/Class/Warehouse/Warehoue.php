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
        $configs = $this->generalSettingRepository->getAllConfig();
        $warehouseId = $configs['RECEIVE_FROM_PROVIDER_WAREHOUSE_ID'] ?? null;

        if (!$warehouseId) {
            throw new \Exception('Chưa cấu hình kho nhận hàng từ nhà cung cấp');
        }

        $warehouse = $this->warehouseRepository->find((int) $warehouseId);

        if ($warehouse === null) {
            throw new \Exception('Kho nhận hàng từ nhà cung cấp không tồn tại');
        }

        return $warehouse;
    }
}
