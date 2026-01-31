<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        private EntityManagerInterface $em,
    ) {}

    public function upload(UploadedFile $file, User $user)
    {
        $fileName = $this->generateFileName($file);

        // Tạo folder vật lý theo email
        $userFolder = $this->targetDirectory . '/' . $user->getUserIdentifier();
        $file->move($userFolder, $fileName);

        $path = $user->getUserIdentifier() . '/' . $fileName;

        // Lưu vào database
        $media = new Media();
        $media->setPath($path);
        $media->setOriginalName($file->getClientOriginalName());
        $media->setOwner($user);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    public function getAllFileUploadWithUser(string $userEmail): array
    {
        $userFolder = $this->targetDirectory . '/' . $userEmail;
        $files = scandir($userFolder);
        $files = array_filter($files, function ($file) {
            return !in_array($file, ['.', '..']);
        });

        return $files;
    }

    public function deletePermanently($path)
    {
        $file = $this->targetDirectory . '/' . $path;
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Generate file name random with format media_randomstring.extension
    public function generateFileName(UploadedFile $file): string
    {
        $fileName = 'media_' . uniqid() . '.' . $file->guessExtension();

        return $fileName;
    }
}
