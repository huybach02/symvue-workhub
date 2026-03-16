<?php

namespace App\Service;

use App\Class\Constanst;
use App\Entity\LoginDevice;
use App\Repository\GeneralSettingRepository;
use App\Repository\LoginDeviceRepository;
use App\Repository\WorkingTimeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private GeneralSettingRepository $cauHinhChungRepository,
        private WorkingTimeRepository $thoiGianLamViecRepository,
        private UserRepository $userRepository,
        private MailService $mailService,
        private EntityManagerInterface $entityManager,
        private LoginDeviceRepository $thietBiDangNhapRepository,
        private UserPasswordHasherInterface $passwordHasher
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

    public function checkIsTimeWork($currentTime, $currentDay)
    {
        $cauHinhChung = $this->cauHinhChungRepository->getAllConfig();
        if ($cauHinhChung['CHECK_THOI_GIAN_LAM_VIEC'] == Constanst::CHECK_THOI_GIAN_LAM_VIEC['KICH_HOAT']) {
            $thoiGianLamViec = $this->thoiGianLamViecRepository->findOneBy(['thu' => $currentDay]);
            if ($thoiGianLamViec) {
                $gioBatDau = $thoiGianLamViec->getGioBatDau();
                $gioKetThuc = $thoiGianLamViec->getGioKetThuc();

                if ($currentTime < $gioBatDau || $currentTime > $gioKetThuc) {
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    public function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function sendOtpEmail($email, $otp)
    {
        $this->mailService->sendOtpEmail($email, $otp);
    }

    public function verifyOtp($email, $otp, $metadata)
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new \Exception(t("auth.not_found"));
        }

        $keyOtp = "otp_" . $user->getId();

        // Lấy opt từ cache ra và verify
        $otpItem = $this->cache->getItem($keyOtp);
        if ($otpItem->isHit()) {
            $otpCache = $otpItem->get();
            if ($otpCache == $otp) {

                $this->handleLimitDeviceLogin($user);

                // Dùng uuid để generate device id
                $deviceId = Uuid::uuid4()->toString();

                // Tạo record vào database
                $device = new LoginDevice();
                $device->setDeviceKey($deviceId);
                $device->setUserId($user->getId());
                $device->setMetadata($metadata);

                $this->entityManager->persist($device);
                $this->entityManager->flush();

                // Dùng bcrypt để hash deviceId
                $deviceIdHash = password_hash($deviceId, PASSWORD_BCRYPT);
                return $deviceIdHash;
            } else {
                throw new \Exception(t("auth.otp_invalid"));
            }
        }

        // Gửi lại OTP
        $newOtp = $this->generateOtp();
        $otpItem->set($newOtp);
        $thoiGianHieuLucOtp = (int) $this->cauHinhChungRepository->getAllConfig()['THOI_GIAN_HET_HAN_OTP'];
        $otpItem->expiresAfter($thoiGianHieuLucOtp * 60);
        $this->cache->save($otpItem);

        $this->sendOtpEmail($email, $newOtp);

        throw new \Exception(t("auth.otp_resend"));
    }

    public function handleLimitDeviceLogin($user)
    {
        $cauHinhChung = $this->cauHinhChungRepository->getAllConfig()['SO_THIET_BI_DANG_NHAP_TOI_DA'];
        $thietBiDangNhaps = $this->thietBiDangNhapRepository->findBy([
            'user_id' => $user->getId(),
        ], [
            'id' => 'ASC',
        ]);
        if (count($thietBiDangNhaps) > $cauHinhChung) {
            // Remove record thiết bị đăng nhập cũ nhất
            $this->entityManager->remove($thietBiDangNhaps[0]);
            $this->entityManager->flush();
        }
    }


    public function sendForgotPasswordEmail($email, $password)
    {
        $this->mailService->sendForgotPasswordEmail($email, $password);
    }

    public function forgotPassword($email)
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw new \Exception(t("auth.not_found"));
        }

        $password = generateRandomString(10);

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsFirstLogin(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendForgotPasswordEmail($email, $password);
    }

    public function changePassword($email, $password, $confirmPassword)
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw new \Exception(t("auth.not_found"));
        }

        if ($password != $confirmPassword) {
            throw new \Exception(t("auth.password_not_match"));
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsFirstLogin(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
