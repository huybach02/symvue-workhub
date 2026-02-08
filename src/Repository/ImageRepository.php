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

    public function removeImages(ImageableInterface $owner): void
    {
        $this->createQueryBuilder('i')
            ->delete()
            ->where('i.entityType = :entityType')
            ->andWhere('i.entityId = :entityId')
            ->setParameter('entityType', $this->getRealClass(get_class($owner)))
            ->setParameter('entityId', $owner->getId())
            ->getQuery()
            ->execute();
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
