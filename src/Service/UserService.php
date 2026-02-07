<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
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

        $password = generateRandomString(10);

        $item->setName($dto->name);
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setPassword($this->passwordHasher->hashPassword($item, $password));
        $item->setBirthday($dto->birthday);
        $item->setGender($dto->gender);
        $item->setProvince($dto->province);
        $item->setWard($dto->ward);
        $item->setAddress($dto->address);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);

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

        $item->setName($dto->name);
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setBirthday($dto->birthday);
        $item->setGender($dto->gender);
        $item->setProvince($dto->province);
        $item->setWard($dto->ward);
        $item->setAddress($dto->address);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);

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
