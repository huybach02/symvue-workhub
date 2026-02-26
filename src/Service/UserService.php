<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UserDTO;
use App\Entity\BoPhan;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Repository\ImageRepository;
use App\Repository\UserPermissionRepository;
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

        // Nếu user này là quản lý của 1 bộ phận thì báo lỗi không cho xóa
        $checkIsManager = $this->entityManager->getRepository(BoPhan::class)->findOneBy([
            'quanLyBoPhan' => $item,
        ]);

        if ($checkIsManager) {
            throw new \Exception(t('error.user_is_manager', [
                '%name%' => $item->getName(),
                '%bo_phan%' => $checkIsManager->getTenBoPhan(),
            ]));
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

    public function getUserDepartment(int $userId): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT up.*, bp.ten_bo_phan, bp.ma_bo_phan, u.name
            FROM user_permission up
            LEFT JOIN bo_phan bp ON bp.id = up.bo_phan_id
            LEFT JOIN "user" u ON u.id = up.user_id
            WHERE up.user_id = :userId
            ORDER BY up.id DESC
        ';
        $items = $conn->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();

        foreach ($items as $key => $item) {
            $items[$key]['phan_quyen'] = json_decode($item['phan_quyen'], true);
        }

        return $items;
    }

    public function updatePhanQuyen(int $permissionId, array $phanQuyen): void
    {
        $userPermission = $this->entityManager->getRepository(\App\Entity\UserPermission::class)->find($permissionId);

        if (!$userPermission) {
            throw new \Exception("Không tìm thấy bản ghi phân quyền với ID: $permissionId");
        }

        $userPermission->setPhanQuyen($phanQuyen);
        $userPermission->setIsCustom(true);

        $this->entityManager->flush();

        $this->boPhanService->mergeUserPermissions($userPermission->getUserId());
    }

    public function addUserDepartment(int $userId, int $boPhanId): void
    {
        $checkExist = $this->entityManager->getRepository(UserPermission::class)->findOneBy([
            'userId' => $userId,
            'boPhanId' => $boPhanId,
        ]);

        if ($checkExist) {
            throw new \Exception("Người dùng đã thuộc bộ phận này. Vui lòng chọn lại");
        }

        $boPhan =  $this->entityManager->getRepository(BoPhan::class)->find($boPhanId);
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        $employeePermissions = [];
        foreach ($boPhan->getPhanQuyen() as $permission) {
            $employeePermissions[] = [
                "name" => $permission['name'],
                "actions" => $permission['employee'],
            ];
        }

        $userPermission = new UserPermission();
        $userPermission->setUserId($userId);
        $userPermission->setBoPhanId($boPhanId);
        $userPermission->setPhanQuyen($employeePermissions);
        if (!$user->getBoPhanId()) {
            $userPermission->setIsDefault(true);
        }
        $this->entityManager->persist($userPermission);
        $this->entityManager->flush();

        if (!$user->getBoPhanId()) {
            $user->setBoPhanId($boPhanId);
            $this->entityManager->flush();
        }

        $this->boPhanService->mergeUserPermissions($userId);
    }

    public function resetOrDeletePermission(int $userId, int $permissionId, string $action): void
    {
        $userPermission = $this->entityManager->getRepository(UserPermission::class)->find($permissionId);

        if (!$userPermission) {
            throw new \Exception("Không tìm thấy bản ghi phân quyền với ID: $permissionId");
        }

        if ($action === 'delete') {
            $this->entityManager->remove($userPermission);
            $this->entityManager->flush();
        } else {
            $boPhan =  $this->entityManager->getRepository(BoPhan::class)->find($userPermission->getBoPhanId());

            $employeePermissions = [];
            foreach ($boPhan->getPhanQuyen() as $permission) {
                $employeePermissions[] = [
                    "name" => $permission['name'],
                    "actions" => $userPermission->isManager() ? $permission['manager'] : $permission['employee'],
                ];
            }

            $userPermission->setPhanQuyen($employeePermissions);
            $userPermission->setIsCustom(false);
            $this->entityManager->flush();
        }

        $this->boPhanService->mergeUserPermissions($userId);
    }
}
