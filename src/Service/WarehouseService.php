<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\WarehouseDTO;
use App\Entity\Warehouse;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->warehouseRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Warehouse $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->warehouseRepository->createQueryBuilder('e')
            ->andWhere('e.status = true');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (Warehouse $item) {
            return [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->warehouseRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(WarehouseDTO $dto): array
    {
        $item = new Warehouse();
        
        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);
        
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, WarehouseDTO $dto): array
    {
        $item = $this->warehouseRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->warehouseRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }
}
