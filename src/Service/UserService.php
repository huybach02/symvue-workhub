<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'u');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(User $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->userRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(UserDTO $dto): array
    {
        $item = new User();

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, UserDTO $dto): array
    {
        $item = $this->userRepository->find($id);

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
        $item = $this->userRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }
}
