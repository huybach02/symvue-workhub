<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\WarehouseType;
use App\DTO\BranchDTO;
use App\Entity\Branch;
use App\Entity\Warehouse;
use App\Repository\BranchRepository;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;

class BranchService
{
    public function __construct(
        private readonly BranchRepository $branchRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ImageRepository $imageRepository,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->branchRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        $branchIds = array_map(fn(Branch $item) => (int) $item->getId(), $result['collection']);
        $imagesMap = $this->imageRepository->getImagesMap(Branch::class, $branchIds, 'image');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (Branch $item) use ($imagesMap) {
                $data = $item->jsonSerialize();
                $data['image'] = $imagesMap[(int) $item->getId()] ?? null;
                return $data;
            },
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

        $data = $item->jsonSerialize();
        $data['image'] = $this->imageRepository->getOneImage($item, 'image');

        return $data;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->branchRepository
            ->createQueryBuilder('branch')
            ->andWhere('branch.status = :status')
            ->setParameter('status', true);

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'branch',
        );

        return array_map(
            static fn(Branch $branch): array => [
                'label' => sprintf('%s (%s)', $branch->getName(), $branch->getCode()),
                'value' => $branch->getId(),
                'code' => $branch->getCode(),
            ],
            $result['collection'],
        );
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

        if (!empty($dto->image)) {
            $this->imageRepository->addOneImage($item, $dto->image, 'image', false);
        }

        $this->entityManager->flush();

        $data = $item->jsonSerialize();
        $data['image'] = $this->imageRepository->getOneImage($item, 'image');

        return $data;
    }

    public function update(int $id, BranchDTO $dto): array
    {
        $item = $this->branchRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $item->setName($dto->name);
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setAddress($dto->address);
        $item->setStatus($dto->status);
        $item->setNote($dto->note);

        $warehouse = $item->getWarehouse();
        $warehouse->setName($dto->name . ' Warehouse');
        $warehouse->setStatus($dto->status);
        $warehouse->setNote($dto->note);

        $this->imageRepository->removeImages($item, 'image');
        if (!empty($dto->image)) {
            $this->imageRepository->addOneImage($item, $dto->image, 'image', false);
        }

        $this->entityManager->flush();

        $data = $item->jsonSerialize();
        $data['image'] = $this->imageRepository->getOneImage($item, 'image');

        return $data;
    }

    public function delete(int $id): void
    {
        $item = $this->branchRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        if ($item->getType() === WarehouseType::Main->value) {
            throw new \Exception(t('error.cannot_delete_record'));
        }

        if (!$item->getDepartments()->isEmpty()) {
            throw new \Exception(t('error.branch_has_department'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->remove($item->getWarehouse());
        $this->entityManager->flush();
    }
}
