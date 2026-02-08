<?php

namespace App\EventListener;

use App\Interface\ImageableInterface as InterfaceImageableInterface;
use App\Repository\ImageRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preRemove)]
class ImageCleanupListener
{
    public function __construct(private ImageRepository $imageRepo) {}

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof InterfaceImageableInterface) {
            // Tự động xóa các ảnh liên quan khi entity cha bị xóa
            $this->imageRepo->removeImages($entity);
        }
    }
}
