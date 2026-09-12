<?php

namespace App\Repository;

use App\Entity\Image;
use App\Interface\ImageableInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function addOneImage(ImageableInterface $entity, string $path, ?string $type = null, bool $flush = true): Image
    {
        $entityType = $this->getRealClass(get_class($entity));

        $image = new Image();
        $image->setPath($path);
        $image->setEntityType($entityType);
        $image->setEntityId($entity->getId());
        $image->setType($type);

        $this->getEntityManager()->persist($image);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $image;
    }

    public function addManyImages(ImageableInterface $entity, array $paths, ?string $type = null): array
    {
        $images = [];
        foreach ($paths as $path) {
            $images[] = $this->addOneImage($entity, $path, $type, false);
        }

        $this->getEntityManager()->flush();

        return $images;
    }

    public function getOneImage(ImageableInterface $owner, ?string $type = null): ?string
    {
        $entityType = $this->getRealClass(get_class($owner));
        $entityId = $owner->getId();

        $qb = $this->createQueryBuilder('i')
            ->select('i.path')
            ->where('i.entityType = :entityType')
            ->andWhere('i.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->setMaxResults(1);

        if ($type) {
            $qb->andWhere('i.type = :type')
                ->setParameter('type', $type);
        }

        $res = $qb->getQuery()->getOneOrNullResult();

        return $res['path'] ?? null;
    }

    /**
     * Batch load 1 ảnh cho danh sách entityId để tránh N+1 query.
     * Trả về mảng key-value: [entityId => path]
     *
     * @param string $entityClass
     * @param int[] $entityIds
     * @param string|null $type
     * @return array<int, string>
     */
    public function getImagesMap(string $entityClass, array $entityIds, ?string $type = null): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $entityType = $this->getRealClass($entityClass);

        $qb = $this->createQueryBuilder('i')
            ->select('i.entityId, i.path')
            ->where('i.entityType = :entityType')
            ->andWhere('i.entityId IN (:entityIds)')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityIds', $entityIds)
            ->orderBy('i.id', 'ASC');

        if ($type) {
            $qb->andWhere('i.type = :type')
                ->setParameter('type', $type);
        }

        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $eId = (int) $row['entityId'];
            if (!isset($map[$eId])) {
                $map[$eId] = $row['path'];
            }
        }

        return $map;
    }

    public function getImages(ImageableInterface $owner, ?string $type = null)
    {
        $entityType = $this->getRealClass(get_class($owner));
        $entityId = $owner->getId();

        $qb = $this->createQueryBuilder('i')
            ->where('i.entityType = :entityType')
            ->andWhere('i.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId);

        if ($type) {
            $qb->andWhere('i.type = :type')
                ->setParameter('type', $type);
        }

        $result = $qb->getQuery()->getResult();

        if (count($result) > 1) {
            return array_map(fn($img) => $img->getPath(), $result);
        } else {
            if (count($result) == 1) {
                return $result[0]->getPath();
            }
            return null;
        }
    }

    public function removeImages(ImageableInterface $owner, ?string $type = null): void
    {
        $qb = $this->createQueryBuilder('i')
            ->delete()
            ->where('i.entityType = :entityType')
            ->andWhere('i.entityId = :entityId')
            ->setParameter('entityType', $this->getRealClass(get_class($owner)))
            ->setParameter('entityId', $owner->getId());

        if ($type !== null) {
            $qb->andWhere('i.type = :type')
                ->setParameter('type', $type);
        }

        $qb->getQuery()->execute();
    }

    private function getRealClass(string $className): string
    {
        // Xử lý Doctrine Proxy: Proxies\__CG__\App\Entity\User -> App\Entity\User
        if (str_contains($className, 'Proxies\\__CG__\\')) {
            return str_replace('Proxies\\__CG__\\', '', $className);
        }
        return $className;
    }
}
