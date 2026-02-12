<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\BoPhanDTO;
use App\Entity\BoPhan;
use App\Entity\User;
use App\Repository\BoPhanRepository;
use Doctrine\ORM\EntityManagerInterface;

class BoPhanService
{
    public function __construct(
        private readonly BoPhanRepository $boPhanRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->boPhanRepository->createQueryBuilder('bp');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'bp');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(BoPhan $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(BoPhanDTO $dto): array
    {
        $item = new BoPhan();

        $user = $this->entityManager->find(User::class, $dto->quanLyBoPhanId);

        $item->setQuanLyBoPhan($user);
        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setPhanQuyen($dto->permissions);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, BoPhanDTO $dto): array
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $user = $this->entityManager->find(User::class, $dto->quanLyBoPhanId);

        $item->setQuanLyBoPhan($user);
        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setPhanQuyen($dto->permissions);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }
}
