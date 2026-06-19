<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\WarehouseType;
use App\DTO\BranchDTO;
use App\Entity\Branch;
use App\Entity\Warehouse;
use App\Repository\BranchRepository;
use Doctrine\ORM\EntityManagerInterface;

class BranchService
{
    public function __construct(
        private readonly BranchRepository $branchRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->branchRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Branch $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->branchRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(BranchDTO $dto): array
    {
        $item = new Branch();

        $branchCode = generateCodeFromName($dto->name);

        $item->setCode($branchCode);
        $item->setType(WarehouseType::Branch->value);
        $item->setName($dto->name);
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setAddress($dto->address);
        $item->setStatus($dto->status);
        $item->setNote($dto->note);

        $this->entityManager->persist($item);

        $warehouse = new Warehouse();
        $warehouse->setBranch($item);
        $warehouse->setCode($branchCode . '_WAREHOUSE');
        $warehouse->setName($dto->name . ' Warehouse');
        $warehouse->setType(WarehouseType::Branch->value);
        $warehouse->setStatus($dto->status);
        $warehouse->setNote($dto->note);

        $this->entityManager->persist($warehouse);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, BranchDTO $dto): array
    {
        $item = $this->branchRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $branchCode = generateCodeFromName($dto->name);

        $item->setCode($branchCode);
        $item->setName($dto->name);
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setAddress($dto->address);
        $item->setStatus($dto->status);
        $item->setNote($dto->note);

        $warehouse = $item->getWarehouse();
        $warehouse->setCode($branchCode . '_WAREHOUSE');
        $warehouse->setName($dto->name . ' Warehouse');
        $warehouse->setStatus($dto->status);
        $warehouse->setNote($dto->note);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->branchRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        if ($item->getType() !== WarehouseType::Main->value) {
            throw new \Exception(t('error.cannot_delete_record'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->remove($item->getWarehouse());
        $this->entityManager->flush();
    }
}
