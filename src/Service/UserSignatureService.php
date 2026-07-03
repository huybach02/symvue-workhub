<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSignature;
use App\Repository\UserSignatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserSignatureService
{
    public function __construct(
        private readonly UserSignatureRepository $userSignatureRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function getCurrentUserSignature(User $user): ?UserSignature
    {
        return $this->userSignatureRepository->findOneBy(['user' => $user]);
    }

    public function saveSignature(User $user, array $payload): UserSignature
    {
        $pngDataUrl = $payload['pngDataUrl'] ?? null;
        $svgContent = $payload['svgContent'] ?? null;
        $strokes = $payload['strokes'] ?? null;

        if (!$pngDataUrl || !str_starts_with($pngDataUrl, 'data:image/png;base64,')) {
            throw new \Exception('Định dạng ảnh PNG không hợp lệ.');
        }

        $base64Str = substr($pngDataUrl, strlen('data:image/png;base64,'));
        $pngData = base64_decode($base64Str, true);

        if ($pngData === false) {
            throw new \Exception('Không thể decode dữ liệu base64 PNG.');
        }

        // Validate kích thước (giới hạn 2MB)
        $fileSize = strlen($pngData);
        if ($fileSize > 2 * 1024 * 1024) {
            throw new \Exception('Dung lượng chữ ký không được vượt quá 2MB.');
        }

        // Kiểm tra nội dung file đúng là PNG bằng finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($pngData);
        if ($mimeType !== 'image/png') {
            throw new \Exception('File gửi lên không phải là ảnh PNG hợp lệ.');
        }

        // Sanitizer SVG cơ bản
        if ($svgContent) {
            $svgContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $svgContent);
        }

        $projectDir = $this->parameterBag->get("kernel.project_dir");
        $targetDir = $projectDir . "/public/uploads/signatures/" . $user->getEmail();
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $pngFilePath = $targetDir . "/signature.png";
        $svgFilePath = $targetDir . "/signature.svg";

        file_put_contents($pngFilePath, $pngData);
        if ($svgContent) {
            file_put_contents($svgFilePath, $svgContent);
        }

        // Lấy width & height từ payload (kích thước CSS của canvas) hoặc từ file ảnh PNG vật lý làm dự phòng
        $width = $payload['width'] ?? null;
        $height = $payload['height'] ?? null;

        if (!$width || !$height) {
            $imageSize = getimagesize($pngFilePath);
            if ($imageSize !== false) {
                $width = $imageSize[0];
                $height = $imageSize[1];
            }
        }

        // Tìm signature hiện tại hoặc tạo mới
        $signature = $this->getCurrentUserSignature($user);
        if (!$signature) {
            $signature = new UserSignature();
            $signature->setUser($user);
            $this->entityManager->persist($signature);
        }

        $signature->setSignaturePngPath("/uploads/signatures/" . $user->getEmail() . "/signature.png");
        $signature->setSignatureSvgPath("/uploads/signatures/" . $user->getEmail() . "/signature.svg");
        $signature->setSignatureStrokes($strokes);
        $signature->setWidth($width);
        $signature->setHeight($height);
        $signature->setFileSize($fileSize);

        // Cập nhật thời gian thay đổi
        $signature->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $signature;
    }

    public function deleteSignature(User $user): void
    {
        $signature = $this->getCurrentUserSignature($user);
        if (!$signature) {
            return;
        }

        $projectDir = $this->parameterBag->get("kernel.project_dir");
        $targetDir = $projectDir . "/public/uploads/signatures/" . $user->getEmail();

        $pngFilePath = $targetDir . "/signature.png";
        $svgFilePath = $targetDir . "/signature.svg";

        if (file_exists($pngFilePath)) {
            @unlink($pngFilePath);
        }
        if (file_exists($svgFilePath)) {
            @unlink($svgFilePath);
        }

        $this->entityManager->remove($signature);
        $this->entityManager->flush();
    }
}
