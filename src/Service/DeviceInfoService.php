<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\LoginDeviceRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service để lấy thông tin v� thiết bị, browser, OS và IP từ request
 */
class DeviceInfoService
{
    public function __construct(
        private readonly LoginDeviceRepository $thietBiDangNhapRepository,
    ) {}

    public function verifyDeviceId(?Request $request, User $user, array $metadata): bool
    {
        $deviceId = $request->headers->get('Device-Id');

        if ($deviceId) {
            $thietBiDangNhaps = $this->thietBiDangNhapRepository->findBy([
                'user_id' => $user->getId(),
            ]);

            foreach ($thietBiDangNhaps as $thietBi) {
                if (password_verify($thietBi->getDeviceKey(), $deviceId)) {
                    $metadataEncoded = json_encode($metadata);
                    if ($metadataEncoded === json_encode($thietBi->getMetadata())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Lấy thông tin metadata từ request
     * 
     * @return array{browser: string, os: string, ip_subnet: string}
     */
    public function getMetadata(?Request $request): array
    {
        if (!$request) {
            return [
                'browser' => 'Unknown',
                'os' => 'Unknown',
                'ip_subnet' => 'Unknown'
            ];
        }

        $userAgent = $request->headers->get('User-Agent', '');
        $ip = $request->getClientIp() ?? 'Unknown';

        return [
            'browser' => $this->parseBrowser($userAgent),
            'os' => $this->parseOS($userAgent),
            'ip_subnet' => $ip
        ];
    }

    /**
     * Parse browser name và version từ User-Agent
     */
    private function parseBrowser(string $userAgent): string
    {
        // Danh sách các browser patterns để check
        // Format: [pattern => tên browser]
        $browsers = [
            '/Edg\/(\d+)/' => 'Edge',           // Edge phải check trước Chrome vì nó chứa cả Chrome
            '/Chrome\/(\d+)/' => 'Chrome',
            '/Firefox\/(\d+)/' => 'Firefox',
            '/OPR\/(\d+)/' => 'Opera',
            '/MSIE (\d+)/' => 'IE',
            '/Trident\/.*rv:(\d+)/' => 'IE',
        ];

        // Duyệt qua từng pattern để tìm match
        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $name . ' ' . $matches[1];
            }
        }

        // Safari đặc biệt vì phải check không chứa Chrome
        if (preg_match('/Safari\//', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            if (preg_match('/Version\/(\d+)/', $userAgent, $matches)) {
                return 'Safari ' . $matches[1];
            }
            return 'Safari';
        }

        return 'Unknown';
    }

    /**
     * Parse OS name và version từ User-Agent
     */
    private function parseOS(string $userAgent): string
    {
        // Check Windows
        if (preg_match('/Windows NT (\d+\.\d+)/', $userAgent, $matches)) {
            return $this->getWindowsVersion($matches[1]);
        }

        // Check macOS
        if (preg_match('/Mac OS X (\d+[._]\d+)/', $userAgent, $matches)) {
            $version = str_replace('_', '.', $matches[1]);
            return 'macOS ' . $version;
        }

        // Check Android
        if (preg_match('/Android (\d+)/', $userAgent, $matches)) {
            return 'Android ' . $matches[1];
        }

        // Check iOS (iPhone/iPad)
        if (preg_match('/(?:iPhone|iPad).*OS (\d+[._]\d+)/', $userAgent, $matches)) {
            $version = str_replace('_', '.', $matches[1]);
            return 'iOS ' . $version;
        }

        // Check Linux
        if (preg_match('/Linux/', $userAgent)) {
            return preg_match('/Ubuntu/', $userAgent) ? 'Ubuntu' : 'Linux';
        }

        return 'Unknown';
    }

    /**
     * Chuyển đổi Windows NT version sang tên Windows thông thường
     */
    private function getWindowsVersion(string $ntVersion): string
    {
        $versions = [
            '10.0' => 'Windows 10/11',
            '6.3' => 'Windows 8.1',
            '6.2' => 'Windows 8',
            '6.1' => 'Windows 7',
            '6.0' => 'Windows Vista',
            '5.1' => 'Windows XP',
        ];

        return $versions[$ntVersion] ?? 'Windows';
    }
}
