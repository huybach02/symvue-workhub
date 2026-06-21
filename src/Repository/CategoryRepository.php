<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findTreeRows(bool $onlyActive = false, ?string $type = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $where = 'deleted_at IS NULL';
        $params = [];

        if ($onlyActive) {
            $where .= ' AND is_active = TRUE';
        }

        if ($type !== null && $type !== '') {
            $where .= ' AND type = :type';
            $params['type'] = $type;
        }

        $sql = "
            SELECT
                id,
                parent_id,
                name,
                slug,
                type,
                path,
                level,
                position,
                is_active
            FROM category
            WHERE {$where}
            ORDER BY level ASC, position ASC, id ASC
        ";

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function buildTree(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            $items[$id] = [
                'id' => $id,
                'parentId' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'path' => $row['path'],
                'level' => (int) $row['level'],
                'position' => (int) $row['position'],
                'type' => $row['type'],
                'isActive' => (bool) $row['is_active'],
                'children' => [],
            ];
        }

        $tree = [];

        foreach ($items as $id => &$item) {
            if ($item['parentId'] === null) {
                $tree[] = &$item;
                continue;
            }

            if (isset($items[$item['parentId']])) {
                $items[$item['parentId']]['children'][] = &$item;
            }
        }
        unset($item);

        $this->sortTree($tree);

        return $tree;
    }

    private function sortTree(array &$items): void
    {
        usort($items, static function (array $first, array $second): int {
            return [$first['position'], $first['id']] <=> [$second['position'], $second['id']];
        });

        foreach ($items as &$item) {
            if ($item['children'] !== []) {
                $this->sortTree($item['children']);
            }
        }
        unset($item);
    }
}
