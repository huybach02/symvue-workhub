<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Entity Listener tự động điền created_by và updated_by
 * cho các entity sử dụng ModifierTrait
 */
class ModifierEntityListener
{
    public function __construct(
        private readonly Security $security
    ) {}

    /**
     * Tự động set created_by và updated_by khi tạo mới entity
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // Kiểm tra entity có sử dụng ModifierTrait không
        if (!method_exists($entity, 'setCreatedBy') || !method_exists($entity, 'setUpdatedBy')) {
            return;
        }

        // Lấy user hiện tại
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return; // Không có user đăng nhập hoặc không phải User entity
        }

        $userId = $user->getId();
        if ($userId === null) {
            return;
        }

        // Set created_by và updated_by
        $entity->setCreatedBy($userId);
        $entity->setUpdatedBy($userId);
    }

    /**
     * Tự động set updated_by khi cập nhật entity
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Kiểm tra entity có sử dụng ModifierTrait không
        if (!method_exists($entity, 'setUpdatedBy')) {
            return;
        }

        // Lấy user hiện tại
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return; // Không có user đăng nhập hoặc không phải User entity
        }

        $userId = $user->getId();
        if ($userId === null) {
            return;
        }

        // Chỉ set updated_by, không thay đổi created_by
        $entity->setUpdatedBy($userId);

        // Thông báo cho Doctrine rằng field này đã thay đổi
        $entityManager = $args->getObjectManager();
        $metadata = $entityManager->getClassMetadata(get_class($entity));
        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($metadata, $entity);
    }
}
