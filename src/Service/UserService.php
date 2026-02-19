<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\BoPhanService;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private readonly ParameterBagInterface $parameterBag,
        private readonly ImageRepository $imageRepository,
        private readonly BoPhanService $boPhanService,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'u');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (User $user) {
                $data = $user->jsonSerialize();
                $data['image'] = $this->imageRepository->getImages($user, 'avatar');
                return $data;
            },
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

        $data = $item->jsonSerialize();
        $data['image'] = $this->imageRepository->getImages($item, 'avatar');

        return $data;
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
        $item->setBoPhanId($dto->boPhanId);
        $item->setStatus($dto->status);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        if ($dto->avatar) {
            $this->imageRepository->addOneImage($item, $dto->avatar, "avatar");
        }

        $this->boPhanService->handleAddUserPermission($item->getId(), $dto->boPhanId, true);

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
        $item->setBoPhanId($dto->boPhanId);
        $item->setStatus($dto->status);

        $this->entityManager->flush();

        if ($dto->avatar) {
            $this->imageRepository->removeImages($item);
            $this->imageRepository->addOneImage($item, $dto->avatar, "avatar");
        }

        $this->boPhanService->handleAddUserPermission($item->getId(), $dto->boPhanId, true);

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

    public function getDataSelect(array $params): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'u');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (User $user) {
                $data = $user->jsonSerialize();
                return [
                    'label' => $data['name'],
                    'value' => $data['id'],
                ];
            },
            $result['collection']
        );

        return $result['collection'];
    }

    public function getProvince(): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $filePath = $projectDir . '/public/province.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File province.json không tồn tại');
        }

        $content = file_get_contents($filePath);
        $items = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File province.json không đúng định dạng JSON');
        }

        return $items ?? [];
    }

    public function getWard(string $provinceCode): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $filePath = $projectDir . '/public/ward.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File ward.json không tồn tại');
        }

        $content = file_get_contents($filePath);
        $items = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File ward.json không đúng định dạng JSON');
        }

        $items = array_filter($items, fn($item) => $item['parent_code'] === $provinceCode);

        return $items ?? [];
    }
}
