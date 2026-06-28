<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UnitDTO;
use App\Entity\Unit;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

class UnitService
{
    public function __construct(
        private readonly UnitRepository $unitRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->unitRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Unit $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->unitRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(UnitDTO $dto): array
    {
        $item = new Unit();
        
        $item->setName($dto->name);
        $item->setCode($dto->code);
        $item->setSymbol($dto->symbol);
        $item->setStatus($dto->status ?? 1);
        
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, UnitDTO $dto): array
    {
        $item = $this->unitRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $item->setName($dto->name);
        $item->setCode($dto->code);
        $item->setSymbol($dto->symbol);
        $item->setStatus($dto->status ?? 1);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->unitRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->unitRepository->createQueryBuilder('e')
            ->andWhere('e.status = 1');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        $result['collection'] = array_map(function (Unit $item) {
            return [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);

        return $result['collection'];
    }
}
