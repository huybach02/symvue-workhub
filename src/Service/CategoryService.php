<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\CategoryDTO;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryService
{
    private const PATH_SEPARATOR = '/';

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {}

    public function findAll(array $params): array
    {
        $rows = $this->categoryRepository->findTreeRows(type: $params['type'] ?? null);
        $tree = $this->categoryRepository->buildTree($rows);

        return $tree;
    }

    public function findById(int $id): array
    {
        $item = $this->categoryRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(CategoryDTO $dto): array
    {
        $conn = $this->entityManager->getConnection();

        $conn->beginTransaction();

        try {
            $parent = null;

            if ($dto->parentId !== null) {
                $parent = $this->categoryRepository->find($dto->parentId);

                if (!$parent) {
                    throw new \Exception('Parent category not found.');
                }
            }

            $slug = strtolower((string) $this->slugger->slug($dto->name));

            $this->assertSlugIsUnique($slug, $parent?->getId(), null);

            $position = $this->getNextPosition($parent?->getId());

            $category = new Category();
            $category->setName($dto->name);
            $category->setType($dto->type);
            $category->setSlug($slug);
            $category->setParent($parent);
            $category->setLevel($parent ? $parent->getLevel() + 1 : 0);
            $category->setPosition($position);
            $category->setIsActive($dto->status === 1);

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $path = $parent
                ? $parent->getPath() . self::PATH_SEPARATOR . $category->getId()
                : (string) $category->getId();

            $category->setPath($path);

            $this->entityManager->flush();

            $conn->commit();

            return $category->jsonSerialize();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function move(
        int $categoryId,
        ?int $newParentId,
        array $targetOrderedIds,
        ?array $sourceOrderedIds = null,
    ): void {
        $conn = $this->entityManager->getConnection();

        $conn->beginTransaction();

        try {
            /**
             * Lock toàn bộ thao tác chỉnh tree.
             * Với admin category, cách này đơn giản và an toàn.
             */
            $conn->executeStatement("SELECT pg_advisory_xact_lock(hashtext('category_tree'))");
            $this->rebuildTreePaths($conn);

            $node = $conn->fetchAssociative(
                "
                SELECT id, parent_id, slug, path, level
                FROM category
                WHERE id = :id AND deleted_at IS NULL
                FOR UPDATE
                ",
                ['id' => $categoryId]
            );

            if (!$node) {
                throw new \Exception('Category not found.');
            }

            $oldParentId = $node['parent_id'] !== null ? (int) $node['parent_id'] : null;
            $oldPath = $node['path'];
            $oldLevel = (int) $node['level'];

            if ($categoryId === $newParentId) {
                throw new \Exception('Cannot move category into itself.');
            }

            /**
             * Case 1: chỉ đổi thứ tự trong cùng parent.
             */
            if ($oldParentId === $newParentId) {
                $this->updatePositions($oldParentId, $targetOrderedIds);
                $conn->commit();
                return;
            }

            /**
             * Case 2: kéo sang parent khác.
             */
            $newParent = null;

            if ($newParentId !== null) {
                $newParent = $conn->fetchAssociative(
                    "
                    SELECT id, path, level
                    FROM category
                    WHERE id = :id AND deleted_at IS NULL
                    FOR UPDATE
                    ",
                    ['id' => $newParentId]
                );

                if (!$newParent) {
                    throw new \Exception('New parent category not found.');
                }

                $newParentPath = $newParent['path'];

                /**
                 * Không cho kéo node vào chính con/cháu của nó.
                 *
                 * Ví dụ:
                 * - Move node path = 1/2
                 * - New parent path = 1/2/3
                 * => Sai, vì 1/2/3 là descendant của 1/2.
                 */
                if ($newParentPath === $oldPath || str_starts_with($newParentPath, $oldPath . self::PATH_SEPARATOR)) {
                    throw new \Exception('Cannot move category into its own descendant.');
                }

                $newPath = $newParentPath . self::PATH_SEPARATOR . $categoryId;
            } else {
                $newPath = (string) $categoryId;
            }

            $this->assertSlugIsUnique($node['slug'], $newParentId, $categoryId);

            /**
             * Update parent_id cho node được kéo.
             */
            $conn->executeStatement(
                "
                UPDATE category
                SET parent_id = :newParentId,
                    updated_at = NOW()
                WHERE id = :id
                ",
                [
                    'id' => $categoryId,
                    'newParentId' => $newParentId,
                ]
            );

            /**
             * Update path + level cho chính node và toàn bộ con/cháu.
             */
            $pathExpression = "
                CASE
                    WHEN id = :categoryId THEN :newPath
                    ELSE :newPath || substring(path from :suffixStart)
                END
            ";

            $conn->executeStatement(
                "
                UPDATE category
                SET
                    path = {$pathExpression},
                    level = array_length(string_to_array({$pathExpression}, '" . self::PATH_SEPARATOR . "'), 1) - 1,
                    updated_at = NOW()
                WHERE deleted_at IS NULL
                  AND (
                    path = :oldPath
                    OR path LIKE :oldPathPrefix
                  )
                ",
                [
                    'categoryId' => $categoryId,
                    'newPath' => $newPath,
                    'suffixStart' => strlen($oldPath) + 1,
                    'oldPath' => $oldPath,
                    'oldPathPrefix' => $oldPath . self::PATH_SEPARATOR . '%',
                ]
            );

            /**
             * Update position cho source parent và target parent.
             */
            if ($sourceOrderedIds !== null) {
                $this->updatePositions($oldParentId, $sourceOrderedIds);
            }

            $this->updatePositions($newParentId, $targetOrderedIds);

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function update(int $id, CategoryDTO $dto): array
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw new \Exception(t('error.not_found'));
        }

        $conn = $this->entityManager->getConnection();

        $conn->beginTransaction();

        try {
            /**
             * Đồng bộ path trước khi validate parent, tránh dữ liệu cũ lệch path
             * làm check descendant sai hoặc làm rỗng parent ở form.
             */
            $conn->executeStatement("SELECT pg_advisory_xact_lock(hashtext('category_tree'))");
            $this->rebuildTreePaths($conn);

            $oldParentId = $category->getParent()?->getId();
            $newParentId = $dto->parentId;
            $parent = null;

            if ($newParentId !== null) {
                if ($id === $newParentId) {
                    throw new \Exception('Cannot move category into itself.');
                }

                $parentRow = $conn->fetchAssociative(
                    "
                    SELECT id, path
                    FROM category
                    WHERE id = :id AND deleted_at IS NULL
                    FOR UPDATE
                    ",
                    ['id' => $newParentId]
                );

                if (!$parentRow) {
                    throw new \Exception('Parent category not found.');
                }

                if (
                    $parentRow['path'] === $category->getPath() ||
                    str_starts_with($parentRow['path'], $category->getPath() . self::PATH_SEPARATOR)
                ) {
                    throw new \Exception('Cannot move category into its own descendant.');
                }

                $parent = $this->categoryRepository->find($newParentId);

                if (!$parent) {
                    throw new \Exception('Parent category not found.');
                }
            }

            $slug = strtolower((string) $this->slugger->slug($dto->name));

            $this->assertSlugIsUnique($slug, $parent?->getId(), $id);

            $category->setName($dto->name);
            $category->setType($dto->type);
            $category->setSlug($slug);
            $category->setParent($parent);
            $category->setLevel($parent ? $parent->getLevel() + 1 : 0);
            if ($oldParentId !== $newParentId) {
                $category->setPosition($this->getNextPosition($newParentId));
            }
            $category->setIsActive($dto->status === 1);

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $path = $parent
                ? $parent->getPath() . self::PATH_SEPARATOR . $category->getId()
                : (string) $category->getId();

            $category->setPath($path);

            $this->entityManager->flush();

            if ($oldParentId !== $newParentId) {
                $this->rebuildTreePaths($conn);
            }

            $conn->commit();

            return $category->jsonSerialize();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $conn = $this->entityManager->getConnection();

        $conn->beginTransaction();

        try {
            $conn->executeStatement("SELECT pg_advisory_xact_lock(hashtext('category_tree'))");
            $this->rebuildTreePaths($conn);

            $item = $conn->fetchAssociative(
                '
                SELECT id, path
                FROM category
                WHERE id = :id
                  AND deleted_at IS NULL
                FOR UPDATE
                ',
                ['id' => $id]
            );

            if (!$item) {
                throw new \Exception(t('error.not_found'));
            }

            $conn->executeStatement(
                '
                UPDATE category
                SET deleted_at = NOW(),
                    updated_at = NOW()
                WHERE deleted_at IS NULL
                  AND (
                    path = :path
                    OR path LIKE :pathPrefix
                  )
                ',
                [
                    'path' => $item['path'],
                    'pathPrefix' => $item['path'] . self::PATH_SEPARATOR . '%',
                ]
            );

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    private function assertSlugIsUnique(string $slug, ?int $parentId, ?int $exceptId): void
    {
        $conn = $this->entityManager->getConnection();

        $sql = '
            SELECT COUNT(*)
            FROM category
            WHERE slug = :slug
              AND deleted_at IS NULL
        ';

        $params = ['slug' => $slug];

        if ($parentId === null) {
            $sql .= ' AND parent_id IS NULL';
        } else {
            $sql .= ' AND parent_id = :parentId';
            $params['parentId'] = $parentId;
        }

        if ($exceptId !== null) {
            $sql .= ' AND id <> :exceptId';
            $params['exceptId'] = $exceptId;
        }

        $count = (int) $conn->fetchOne(
            $sql,
            $params
        );

        if ($count > 0) {
            throw new \RuntimeException('Category slug already exists in this parent.');
        }
    }

    private function getNextPosition(?int $parentId): int
    {
        $conn = $this->entityManager->getConnection();

        if ($parentId === null) {
            return (int) $conn->fetchOne(
                '
                SELECT COALESCE(MAX(position), 0) + 1
                FROM category
                WHERE deleted_at IS NULL
                  AND parent_id IS NULL
                '
            );
        }

        return (int) $conn->fetchOne(
            "
            SELECT COALESCE(MAX(position), 0) + 1
            FROM category
            WHERE deleted_at IS NULL
              AND parent_id = :parentId
            ",
            ['parentId' => $parentId]
        );
    }

    private function updatePositions(?int $parentId, array $orderedIds): void
    {
        $conn = $this->entityManager->getConnection();

        foreach (array_values($orderedIds) as $index => $id) {
            $affected = $conn->executeStatement(
                "
                UPDATE category
                SET position = :position,
                    updated_at = NOW()
                WHERE id = :id
                  AND deleted_at IS NULL
                  AND parent_id IS NOT DISTINCT FROM :parentId
                ",
                [
                    'id' => (int) $id,
                    'parentId' => $parentId,
                    'position' => $index + 1,
                ]
            );

            if ($affected !== 1) {
                throw new \RuntimeException('Invalid ordered category ids.');
            }
        }
    }

    private function getMaxRelativeDepth(string $oldPath, int $oldLevel): int
    {
        $conn = $this->entityManager->getConnection();

        return (int) $conn->fetchOne(
            "
            SELECT COALESCE(MAX(level - :oldLevel), 0)
            FROM category
            WHERE deleted_at IS NULL
              AND (
                path = :oldPath
                OR path LIKE :oldPathPrefix
              )
            ",
            [
                'oldLevel' => $oldLevel,
                'oldPath' => $oldPath,
                'oldPathPrefix' => $oldPath . self::PATH_SEPARATOR . '%',
            ]
        );
    }

    private function rebuildTreePaths(Connection $conn): void
    {
        $conn->executeStatement(
            "
            WITH RECURSIVE tree AS (
                SELECT
                    id,
                    parent_id,
                    id::text AS path,
                    0 AS level
                FROM category
                WHERE deleted_at IS NULL
                  AND parent_id IS NULL

                UNION ALL

                SELECT
                    child.id,
                    child.parent_id,
                    tree.path || '" . self::PATH_SEPARATOR . "' || child.id::text AS path,
                    tree.level + 1 AS level
                FROM category child
                INNER JOIN tree ON child.parent_id = tree.id
                WHERE child.deleted_at IS NULL
            )
            UPDATE category
            SET
                path = tree.path,
                level = tree.level,
                updated_at = NOW()
            FROM tree
            WHERE category.id = tree.id
              AND (
                category.path IS DISTINCT FROM tree.path
                OR category.level IS DISTINCT FROM tree.level
              )
            "
        );
    }
}
