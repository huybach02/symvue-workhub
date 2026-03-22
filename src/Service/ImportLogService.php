<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\ImportLogDTO;
use App\Entity\ImportLog;
use App\Entity\User;
use App\Repository\ImportLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportLogService
{
    public function __construct(
        private readonly ImportLogRepository $importLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params, User $currentUser): array
    {
        $qb = $this->importLogRepository->createQueryBuilder('il');

        if (!isAdmin($currentUser)) {
            $qb
                ->andWhere('il.createdBy = :currentUserId')
                ->setParameter('currentUserId', $currentUser->getId());
        }

        $result = FilterWithPagination::findWithPagination($qb, $params, 'il');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(ImportLog $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->importLogRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }
}
