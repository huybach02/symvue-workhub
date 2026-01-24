<?php

namespace App\Service;

use App\Repository\CauHinhChungRepository;
use Psr\Cache\CacheItemPoolInterface;

class AuthService
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private CauHinhChungRepository $cauHinhChungRepository
    ) {}

    public function handleLoginAttempts($attemptsKey, $lockoutKey)
    {
        // Lấy số lần đăng nhập sai từ cache
        $attemptsItem = $this->cache->getItem($attemptsKey);
        $attempts = $attemptsItem->isHit() ? (int) $attemptsItem->get() : 0;
        $attempts++; // Tăng số lần thất bại

        $maxAttempts = (int) $this->cauHinhChungRepository->getAllConfig()['SO_LAN_DANG_NHAP_SAI_TOI_DA'];
        $lockoutMinutes = (int) $this->cauHinhChungRepository->getAllConfig()['THOI_GIAN_KHOA_TAI_KHOAN'];

        // Lưu số lần đăng nhập sai vào cache
        $attemptsItem->set($attempts);
        $attemptsItem->expiresAfter($lockoutMinutes * 60); // Tính bằng giây
        $this->cache->save($attemptsItem);

        // Nếu vượt quá số lần cho phép, khóa tài khoản
        if ($attempts >= $maxAttempts) {
            $lockoutItem = $this->cache->getItem($lockoutKey);
            $lockoutExpires = time() + ($lockoutMinutes * 60);
            $lockoutItem->set($lockoutExpires);
            $lockoutItem->expiresAfter($lockoutMinutes * 60);
            $this->cache->save($lockoutItem);
        }
    }

    public function getLockoutTime($lockoutKey): string
    {
        $lockoutItem = $this->cache->getItem($lockoutKey);
        if ($lockoutItem->isHit()) {
            $lockoutExpires = (int) $lockoutItem->get();
            ray(formatSeconds($lockoutExpires - time()));
            return formatSeconds($lockoutExpires - time());
        }
        return "";
    }
}
