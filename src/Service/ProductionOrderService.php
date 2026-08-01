<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\ProductionOrderDTO;
use App\Entity\ProductionOrder;
use App\Repository\ProductionOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductionOrderService
{
    public function __construct(
        private readonly ProductionOrderRepository $productionOrderRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->productionOrderRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(ProductionOrder $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->productionOrderRepository->createQueryBuilder('e')
            ->andWhere('e.status = 1');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (ProductionOrder $item) {
            return [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->productionOrderRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(ProductionOrderDTO $dto): array
    {
        $item = new ProductionOrder();
        
        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);
        
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, ProductionOrderDTO $dto): array
    {
        $item = $this->productionOrderRepository->find($id);
        
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
        $item = $this->productionOrderRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }
}
