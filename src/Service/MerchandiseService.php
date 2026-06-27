<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\MerchandiseDTO;
use App\Entity\Merchandise;
use App\Repository\MerchandiseRepository;
use Doctrine\ORM\EntityManagerInterface;

class MerchandiseService
{
    public function __construct(
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly EntityManagerInterface $entityManager,
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

        return $item->jsonSerialize();
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

        if ($dto->categoryId) {
            $category = $this->entityManager->getRepository(\App\Entity\Category::class)->find($dto->categoryId);
            if ($category) {
                $item->setCategory($category);
            }
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
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

        if ($dto->categoryId) {
            $category = $this->entityManager->getRepository(\App\Entity\Category::class)->find($dto->categoryId);
            if ($category) {
                $item->setCategory($category);
            } else {
                $item->setCategory(null);
            }
        } else {
            $item->setCategory(null);
        }

        $this->entityManager->flush();

        return $item->jsonSerialize();
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
}
