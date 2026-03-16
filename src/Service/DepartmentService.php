<?php

namespace App\Service;

use App\Class\Constanst;
use App\Class\FilterWithPagination;
use App\DTO\DepartmentDTO;
use App\Entity\Department;
use App\Entity\Conversation;
use App\Entity\ConversationUser;
use App\Entity\User;
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

        $user = $this->entityManager->find(User::class, $dto->quanLyBoPhanId);

        $checkExistQuanLy = $this->entityManager->getRepository(Department::class)->findOneBy([
            'quanLyBoPhan' => $dto->quanLyBoPhanId,
        ]);

        if ($checkExistQuanLy) {
            throw new \Exception(t('error.quan_ly_bo_phan_exist', ['%name%' => $user->getName(), '%bo_phan%' => $checkExistQuanLy->getTenBoPhan()]));
        }

        $checkExistMaBoPhan = $this->entityManager->getRepository(Department::class)->findOneBy([
            'maBoPhan' => $dto->maBoPhan,
        ]);

        if ($checkExistMaBoPhan) {
            throw new \Exception(t('error.bo_phan_exist', ['%name%' => $dto->tenBoPhan, '%ma_bo_phan%' => $dto->maBoPhan]));
        }

        $item->setQuanLyBoPhan($user);
        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setPhanQuyen($dto->permissions);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $permissions = [];
        foreach ($dto->permissions as $permission) {
            $permissions[] = [
                "name" => $permission['name'],
                "actions" => $permission['manager'],
            ];
        }

        $userPermission = new UserPermission();
        $userPermission->setUserId($user->getId());
        $userPermission->setBoPhanId($item->getId());
        $userPermission->setPhanQuyen($permissions);
        $userPermission->setIsManager(true);
        $this->entityManager->persist($userPermission);
        $this->entityManager->flush();

        $conversation = new Conversation();
        $conversation->setType(Constanst::TYPE_CONVERSATION['department']);
        $conversation->setName($item->getTenBoPhan());
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $item->setConversation($conversation);
        $this->entityManager->flush();

        $conversationUser = new ConversationUser();
        $conversationUser->setConversation($conversation);
        $conversationUser->setMember($user);
        $this->entityManager->persist($conversationUser);
        $this->entityManager->flush();

        $this->mergeUserPermissions($user->getId());

        return $item->jsonSerialize();
    }

    public function update(int $id, DepartmentDTO $dto): array
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $user = $this->entityManager->find(User::class, $dto->quanLyBoPhanId);

        // LÆ°u láº¡i quáº£n lÃ½ cÅ© trÆ°á»›c khi thay Ä‘á»•i
        $oldManager = $item->getQuanLyBoPhan();
        $isManagerChanged = $oldManager && $oldManager->getId() !== $user->getId();

        $item->setQuanLyBoPhan($user);
        $item->setTenBoPhan($dto->tenBoPhan);
        $item->setMaBoPhan($dto->maBoPhan);
        $item->setStatus($dto->status);
        $item->setPhanQuyen($dto->permissions);

        $this->entityManager->flush();

        // Xá»­ lÃ½ háº¡ cáº¥p permission cá»§a quáº£n lÃ½ cÅ© náº¿u cÃ³ thay Ä‘á»•i ngÆ°á»�i quáº£n lÃ½
        if ($isManagerChanged) {
            $this->handleOldManagerPermission($id, $oldManager->getId(), $dto->permissions);
        }

        // Cáº­p nháº­t permission cho manager vÃ  employee
        $this->handleUpdateAllUserPermission($item->getId(), $dto->permissions, $user->getId());

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->boPhanRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $userPermissions = $this->entityManager->getRepository(UserPermission::class)->findBy([
            'boPhanId' => $id,
        ]);

        if (count($userPermissions) > 1) {
            throw new \Exception(t('error.bo_phan_has_user', ['%name%' => $item->getTenBoPhan(), '%ma_bo_phan%' => $item->getMaBoPhan()]));
        }

        foreach ($userPermissions as $userPermission) {
            $this->entityManager->remove($userPermission);
        }

        $conversation = $item->getConversation();
        if ($conversation) {
            foreach ($conversation->getConversationUsers() as $conversationUser) {
                $this->entityManager->remove($conversationUser);
            }
            $item->setConversation(null);
            $this->entityManager->remove($conversation);
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

    public function handleAddUserPermission(int $userId, int $boPhanId, bool $isDefault = false): void
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);

        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        $permissions = [];
        foreach ($boPhan->getPhanQuyen() as $permission) {
            $permissions[] = [
                "name" => $permission['name'],
                "actions" => $permission['employee'],
            ];
        }

        $userPermission = $this->entityManager->getRepository(UserPermission::class)->findOneBy([
            'userId' => $userId,
            'boPhanId' => $boPhanId,
        ]);

        if ($userPermission) {
            $userPermission->setPhanQuyen($permissions);
            $userPermission->setIsDefault($isDefault);
            $this->entityManager->persist($userPermission);
        } else {
            $userPermission = new UserPermission();
            $userPermission->setUserId($userId);
            $userPermission->setBoPhanId($boPhanId);
            $userPermission->setPhanQuyen($permissions);
            $userPermission->setIsDefault($isDefault);
            $this->entityManager->persist($userPermission);
        }

        $this->entityManager->flush();

        $this->mergeUserPermissions($userId);
    }

    public function handleOldManagerPermission(int $boPhanId, int $oldManagerId, array $permissions): void
    {
        $oldManagerPermission = $this->entityManager->getRepository(UserPermission::class)->findOneBy([
            'userId' => $oldManagerId,
            'boPhanId' => $boPhanId,
        ]);

        if (!$oldManagerPermission) {
            return;
        }

        // Kiá»ƒm tra xem quáº£n lÃ½ cÅ© cÃ³ pháº£i lÃ  thÃ nh viÃªn thá»±c sá»± cá»§a bá»™ pháº­n khÃ´ng
        // (náº¿u isManager=true vÃ  chá»‰ cÃ³ báº£n ghi nÃ y thÃ¬ há»� chá»‰ lÃ  manager, khÃ´ng pháº£i employee)
        // Háº¡ cáº¥p xuá»‘ng employee permission vÃ  bá»� cá»� isManager
        $employeePermissions = [];
        foreach ($permissions as $permission) {
            $employeePermissions[] = [
                "name" => $permission['name'],
                "actions" => $permission['employee'],
            ];
        }

        $oldManagerPermission->setIsManager(false);
        $oldManagerPermission->setPhanQuyen($employeePermissions);
        $this->entityManager->persist($oldManagerPermission);
        $this->entityManager->flush();

        $this->mergeUserPermissions($oldManagerId);
    }

    public function handleUpdateAllUserPermission(int $boPhanId, array $permissions, ?int $managerId = null): void
    {
        // Cáº­p nháº­t permission cho manager
        if ($managerId) {
            $managerPermissions = [];
            foreach ($permissions as $permission) {
                $managerPermissions[] = [
                    "name" => $permission['name'],
                    "actions" => $permission['manager'],
                ];
            }

            $managerUserPermission = $this->entityManager->getRepository(UserPermission::class)->findOneBy([
                'userId' => $managerId,
                'boPhanId' => $boPhanId,
            ]);

            if ($managerUserPermission) {
                $managerUserPermission->setPhanQuyen($managerPermissions);
                $managerUserPermission->setIsManager(true);
                $this->entityManager->persist($managerUserPermission);
            } else {
                $managerUserPermission = new UserPermission();
                $managerUserPermission->setUserId($managerId);
                $managerUserPermission->setBoPhanId($boPhanId);
                $managerUserPermission->setPhanQuyen($managerPermissions);
                $managerUserPermission->setIsManager(true);
                $this->entityManager->persist($managerUserPermission);
            }

            $this->mergeUserPermissions($managerId);
        }

        // Cáº­p nháº­t permission cho employee (khÃ´ng pháº£i default, manager, hay custom)
        $employeePermissions = [];
        foreach ($permissions as $permission) {
            $employeePermissions[] = [
                "name" => $permission['name'],
                "actions" => $permission['employee'],
            ];
        }

        $userPermissions = $this->entityManager->getRepository(UserPermission::class)->findBy([
            'boPhanId' => $boPhanId,
            'isManager' => false,
            'isCustom' => false,
        ]);

        foreach ($userPermissions as $userPermission) {
            $userPermission->setPhanQuyen($employeePermissions);
            $this->entityManager->persist($userPermission);
        }

        $this->entityManager->flush();

        foreach ($userPermissions as $userPermission) {
            $this->mergeUserPermissions($userPermission->getUserId());
        }
    }

    public function getMembers(int $boPhanId): array
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);

        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        $userPermissions = $this->entityManager
            ->getRepository(UserPermission::class)
            ->findBy(['boPhanId' => $boPhanId]);

        if (empty($userPermissions)) {
            return [];
        }

        $userIds = array_map(fn(UserPermission $up) => $up->getUserId(), $userPermissions);
        $managerUserIds = array_map(
            fn(UserPermission $up) => $up->getUserId(),
            array_filter($userPermissions, fn(UserPermission $up) => $up->isManager() === true)
        );

        $users = $this->entityManager
            ->getRepository(User::class)
            ->findBy(['id' => $userIds]);

        $result = array_map(function (User $user) use ($managerUserIds) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'image' => $user->getImage(),
                'status' => $user->getStatus(),
                'isManager' => in_array($user->getId(), $managerUserIds),
            ];
        }, $users);

        usort($result, fn($a, $b) => $b['isManager'] <=> $a['isManager']);

        return $result;
    }

    public function removeMember(int $boPhanId, int $userId): void
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);

        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        $userPermissions = $this->entityManager
            ->getRepository(UserPermission::class)
            ->findBy([
                'boPhanId' => $boPhanId,
                'userId' => $userId,
            ]);

        if (empty($userPermissions)) {
            throw new \Exception(t('error.not_found'));
        }

        foreach ($userPermissions as $userPermission) {
            $this->entityManager->remove($userPermission);
        }

        $user = $this->entityManager->find(User::class, $userId);
        if ($user && $user->getBoPhanId() === $boPhanId) {
            $user->setBoPhanId(null);
        }

        $conversation = $boPhan->getConversation();
        if ($conversation) {
            $conversationUser = $this->entityManager
                ->getRepository(ConversationUser::class)
                ->findOneBy([
                    'conversation' => $conversation,
                    'member' => $user,
                ]);

            if ($conversationUser) {
                $this->entityManager->remove($conversationUser);
            }
        }

        $this->entityManager->flush();

        $this->mergeUserPermissions($userId);
    }

    public function mergeUserPermissions(int $userId): void
    {
        $userPermissions = $this->entityManager
            ->getRepository(UserPermission::class)
            ->findBy(['userId' => $userId]);
        if (empty($userPermissions)) {
            return;
        }
        $merged = [];
        foreach ($userPermissions as $up) {
            foreach ($up->getPhanQuyen() ?? [] as $perm) {
                $name = $perm['name'];
                if (!isset($merged[$name])) {
                    $merged[$name] = $perm['actions'];
                } else {
                    // OR merge: náº¿u báº¥t ká»³ true â†’ true
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

        // LÆ°u result vÃ o cache redis
        $cacheKey = "user_permissions_" . $userId;
        $item = $this->cache->getItem($cacheKey);
        $item->set($result);
        $item->expiresAfter(3600 * 24 * 90); // 90 ngÃ y
        $this->cache->save($item);
    }

    public function addMemberToBoPhan(int $userId, int $boPhanId): void
    {
        $boPhan = $this->boPhanRepository->find($boPhanId);
        if (!$boPhan) {
            throw new \Exception(t('error.not_found'));
        }

        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $existingPermission = $this->entityManager
            ->getRepository(UserPermission::class)
            ->findOneBy([
                'boPhanId' => $boPhanId,
                'userId' => $userId,
            ]);

        if ($existingPermission) {
            throw new \Exception(t('error.user_already_member'));
        }

        $this->handleAddUserPermission($userId, $boPhanId);

        $conversationUser = new ConversationUser();
        $conversationUser->setConversation($boPhan->getConversation());
        $conversationUser->setMember($user);
        $this->entityManager->persist($conversationUser);
        $this->entityManager->flush();
    }
}
