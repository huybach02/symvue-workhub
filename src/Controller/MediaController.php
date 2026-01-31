<?php

namespace App\Controller;

use App\Class\CustomResponse;
use App\Entity\Media;
use App\Entity\Folder;
use App\Entity\User;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class MediaController extends AbstractController
{
    public function __construct(
        private FileUploader $uploader,
        private EntityManagerInterface $em
    ) {}

    // Upload file
    #[Route('/media/upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[CurrentUser()] ?User $user
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file) {
            return CustomResponse::error('Vui lòng chọn file để upload');
        }

        try {
            $media = $this->uploader->upload($file, $user);

            $mediaData = $media->jsonSerialize();
            $mediaData['path'] = $this->generateMediaUrl($request, $media->getPath());

            return CustomResponse::success($mediaData, 'File uploaded successfully');
        } catch (\Exception $e) {
            return CustomResponse::error($e->getMessage());
        }
    }

    // Lấy danh sách ảnh thuộc user
    #[Route('/media', methods: ['GET'])]
    public function index(
        Request $request,
        #[CurrentUser()] ?User $user,
    ): JsonResponse {
        $medias = $this->em->getRepository(Media::class)->findBy([
            'owner' => $user,
            'deletedAt' => null
        ], ['id' => 'DESC']);

        $data = [];
        foreach ($medias as $media) {
            $mediaData = $media->jsonSerialize();
            $mediaData['path'] = $this->generateMediaUrl($request, $media->getPath());
            $data[] = $mediaData;
        }

        return CustomResponse::success($data);
    }

    // Lấy thùng rác (Chỉ lấy ảnh)
    #[Route('/media/trash', methods: ['GET'])]
    public function trash(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $filters = $this->em->getFilters();
        $filters->disable('softdeleteable');

        try {
            $qb = $this->em->getRepository(Media::class)->createQueryBuilder('m');
            $medias = $qb->where('m.owner = :owner')
                ->andWhere('m.deletedAt IS NOT NULL')
                ->setParameter('owner', $user)
                ->orderBy('m.deletedAt', 'DESC')
                ->getQuery()->getResult();

            $data = [];
            foreach ($medias as $media) {
                $mediaData = $media->jsonSerialize();
                $mediaData['path'] = $this->generateMediaUrl($request, $media->getPath());
                $data[] = $mediaData;
            }

            return CustomResponse::success($data);
        } finally {
            $filters->enable('softdeleteable');
        }
    }

    // Xóa mềm (Đưa vào thùng rác)
    #[Route('/media/delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $repo = $this->em->getRepository(Media::class);

        foreach ($ids as $id) {
            $media = $repo->find($id);
            if ($media && $media->getOwner() === $this->getUser()) {
                $this->em->remove($media);
            }
        }
        $this->em->flush();
        return CustomResponse::success([], 'Deleted successfully');
    }

    // Khôi phục
    #[Route('/media/restore', methods: ['POST'])]
    public function restore(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $repo = $this->em->getRepository(Media::class);

        $filters = $this->em->getFilters();
        $filters->disable('softdeleteable');

        try {
            foreach ($ids as $id) {
                $media = $repo->find($id);
                if ($media && $media->getOwner() === $this->getUser()) {
                    $media->setDeletedAt(null);
                }
            }
            $this->em->flush();
            return CustomResponse::success([], 'Restored successfully');
        } finally {
            $filters->enable('softdeleteable');
        }
    }

    // Xóa vĩnh viễn
    #[Route('/media/delete-permanently', methods: ['POST'])]
    public function deletePermanently(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $repo = $this->em->getRepository(Media::class);

        $filters = $this->em->getFilters();
        $filters->disable('softdeleteable');

        try {
            foreach ($ids as $id) {
                $media = $repo->find($id);
                if ($media && $media->getOwner() === $this->getUser()) {
                    $this->em->remove($media);
                    $this->uploader->deletePermanently($media->getPath());
                }
            }
            $this->em->flush();
            return CustomResponse::success([], 'Deleted permanently successfully');
        } finally {
            $filters->enable('softdeleteable');
        }
    }

    private function generateMediaUrl(Request $request, string $filePath): string
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        return $baseUrl . '/uploads/' . $filePath;
    }
}
