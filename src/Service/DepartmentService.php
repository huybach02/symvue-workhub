<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\DTO\DepartmentDTO;
use App\DTO\PositionDTO;
use App\Entity\Department;
use App\Entity\Conversation;
use App\Entity\ConversationUser;
use App\Entity\Position;
use App\Entity\User;
use App\Entity\UserHasCustomPermission;
use App\Entity\UserPermission;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $boPhanRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->boPhanRepository->createQueryBuilder('bp');

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'bp',
            [
                'quanLyBoPhan' => [
                    'alias' => 'qlbp',
                    'joinField' => 'bp.quanLyBoPhan',
                    'targetField' => 'id'
                ]
            ]
        );

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Department $item) => $item->jsonSerialize(),
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

    public function create(DepartmentDTO $dto): array
    {
        $item = new Department();

        // $user = $this->entityManager->find(User::class, $dto->quanLyBoPhanId);

        // $checkExistQuanLy = $this->entityManager->getRepository(Department::class)->findOneBy([
        //     'quanLyBoPhan' => $dto->quanLyBoPhanId,
        // ]);

        // if ($checkExistQuanLy) {
        //     throw new \Exception(t('error.quan_ly_bo_phan_exist', ['%name%' => $user->getName(), '%bo_phan%' => $checkExistQuanLy->getTenBoPhan()]));
        // }

        $checkExistMaBoPhan = $this->entityManager->getRepository(Department::class)->findOneBy([
            'maBoPhan' => $dto->maBoPhan,
        ]);

        if ($checkExistMaBoPhan) {
            throw new \Exception(t('error.bo_phan_exist', ['%name%' => $dto->tenBoPhan, '%ma_bo_phan%' => $dto->maBoPhan]));
        }

        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setGhiChu($dto->ghiChu);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, DepartmentDTO $dto): array
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setGhiChu($dto->ghiChu);

        $this->entityManager->flush();

        // $this->handleUpdateAllUserPermission($item->getId(), $dto->permissions, $user->getId());

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $positions = $item->getPositions();

        if ($positions->count() > 0) {
            throw new \Exception(t('error.bo_phan_has_position', ['%name%' => $item->getTenBoPhan(), '%ma_bo_phan%' => $item->getMaBoPhan()]));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->boPhanRepository->createQueryBuilder('bp');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'bp');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (Department $boPhan) {
                $data = $boPhan->jsonSerialize();
                return [
                    'label' => $data['tenBoPhan'] . ' (' . $data['maBoPhan'] . ')',
                    'value' => $data['id'],
                ];
            },
            $result['collection']
        );

        return $result['collection'];
    }

    public function getPositions(int $boPhanId): array
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);

        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        return array_map(
            fn(Position $position) => $position->jsonSerialize(),
            $boPhan->getPositions()->toArray()
        );
    }

    public function createPosition(int $boPhanId, PositionDTO $dto): array
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);

        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        $position = new Position();
        $position->setDepartment($boPhan);

        $position->setCode($dto->code);
        $position->setName($dto->name);
        $position->setDescription($dto->description);
        $position->setEmploymentType($dto->employmentType);
        $position->setMinSalary($dto->minSalary);
        $position->setMaxSalary($dto->maxSalary);
        $position->setCurrency($dto->currency);
        $position->setProbationMonths($dto->probationMonths);
        $position->setProbationSalaryRate($dto->probationSalaryRate);
        $position->setAnnualLeaveDays($dto->annualLeaveDays);
        $position->setReviewCycleMonths($dto->reviewCycleMonths);
        $position->setNoticePeriodDays($dto->noticePeriodDays);
        $position->setAllowances($dto->allowances);
        $position->setIsManager($dto->isManager);
        $position->setStatus($dto->status);

        $this->entityManager->persist($position);
        $this->entityManager->flush();

        return $position->jsonSerialize();
    }

    public function updatePosition(int $boPhanId, int $positionId, PositionDTO $dto): array
    {
        $position = $this->entityManager->getRepository(Position::class)->find($positionId);

        if (!$position || $position->getDepartment()?->getId() !== $boPhanId) {
            throw new \Exception(t('error.not_found'));
        }

        $position->setCode($dto->code);
        $position->setName($dto->name);
        $position->setDescription($dto->description);
        $position->setEmploymentType($dto->employmentType);
        $position->setMinSalary($dto->minSalary);
        $position->setMaxSalary($dto->maxSalary);
        $position->setCurrency($dto->currency);
        $position->setProbationMonths($dto->probationMonths);
        $position->setProbationSalaryRate($dto->probationSalaryRate);
        $position->setAnnualLeaveDays($dto->annualLeaveDays);
        $position->setReviewCycleMonths($dto->reviewCycleMonths);
        $position->setNoticePeriodDays($dto->noticePeriodDays);
        $position->setAllowances($dto->allowances);
        $position->setIsManager($dto->isManager);
        $position->setStatus($dto->status);
        $this->entityManager->flush();

        return $position->jsonSerialize();
    }

    public function deletePosition(int $boPhanId, int $positionId): void
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);
        $position = $this->entityManager->getRepository(Position::class)->find($positionId);

        if (!$boPhan || !$position || $position->getDepartment()?->getId() !== $boPhanId) {
            throw new \Exception(t('error.not_found'));
        }

        $positionCode = $position->getCode();

        $phanQuyen = $boPhan->getPhanQuyen() ?? [];

        if ($positionCode && array_key_exists($positionCode, $phanQuyen)) {
            unset($phanQuyen[$positionCode]);
        }

        $boPhan->setPhanQuyen($phanQuyen);

        $this->entityManager->remove($position);
        $this->entityManager->flush();
    }

    public function updatePositionPermissions(int $boPhanId, array $permissions): array
    {
        $department = $this->boPhanRepository->find($boPhanId);

        if (!$department) {
            throw new \Exception(t('error.not_found'));
        }

        $positions = $department->getPositions();

        $department->setPhanQuyen($permissions);

        $affectedUserIds = [];

        foreach ($positions as $position) {
            $userPermissions = $this->entityManager->getRepository(UserPermission::class)->findBy([
                'departmentId' => $department->getId(),
                'positionId' => $position->getId()
            ]);

            foreach ($userPermissions as $userPermission) {
                $userPermission->setPhanQuyen($permissions[$position->getCode()] ?? []);
                $affectedUserIds[$userPermission->getUserId()] = true;
            }
        }

        $this->entityManager->flush();

        foreach (array_keys($affectedUserIds) as $userId) {
            $this->mergeUserPermissions($userId);
        }

        return $department->jsonSerialize();
    }

    public function mergeUserPermissions(int $userId): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $checkUserHasCustomPermission = $this->entityManager->getRepository(UserHasCustomPermission::class)->findOneBy(['user' => $user]);
        if ($checkUserHasCustomPermission) {
            return;
        }

        $cacheKey = "user_permissions_" . $userId;
        $userPermissions = $this->entityManager
            ->getRepository(UserPermission::class)
            ->findBy(['userId' => $userId]);
        $merged = [];
        foreach ($userPermissions as $up) {
            foreach ($up->getPhanQuyen() ?? [] as $perm) {
                if (!isset($perm['name']) || !is_array($perm['actions'] ?? null)) {
                    continue;
                }

                $name = $perm['name'];
                if (!isset($merged[$name])) {
                    $merged[$name] = $perm['actions'];
                } else {
                    foreach ($perm['actions'] as $action => $value) {
                        $merged[$name][$action] =
                            ($merged[$name][$action] ?? false) || $value;
                    }
                }
            }
        }
        $result = [];
        foreach ($merged as $name => $actions) {
            $result[] = ['name' => $name, 'actions' => $actions];
        }

        // Lưu result vào cache redis.
        $item = $this->cache->getItem($cacheKey);
        $item->set($result);
        $item->expiresAfter(3600 * 24 * 90); // 90 ngày
        $this->cache->save($item);
    }
}
