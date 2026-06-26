<?php

namespace App\Service;

use App\Class\CheckWorkScheduleOfUser;
use App\Class\Constanst;
use App\Class\StatusAttendance;
use App\Entity\LoginDevice;
use App\Entity\User;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use App\Repository\LoginDeviceRepository;
use App\Repository\WorkingTimeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private CacheService $cacheService,
        private GeneralSettingRepository $cauHinhChungRepository,
        private WorkingTimeRepository $thoiGianLamViecRepository,
        private UserRepository $userRepository,
        private MailService $mailService,
        private EntityManagerInterface $entityManager,
        private LoginDeviceRepository $thietBiDangNhapRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private AttendanceRepository $attendanceRepository,
        private AttendanceReminderService $attendanceReminderService,
        private CheckWorkScheduleOfUser $checkWorkScheduleOfUser,
        private WorkScheduleService $workScheduleService,
    ) {}

    public function handleLoginAttempts($attemptsKey, $lockoutKey)
    {
        // Lấy số lần đăng nhập sai từ cache
        $attempts = (int) $this->cacheService->get($attemptsKey, 0);
        $attempts++; // Tăng số lần thất bại

        $maxAttempts = (int) $this->cauHinhChungRepository->getAllConfig()['SO_LAN_DANG_NHAP_SAI_TOI_DA'];
        $lockoutMinutes = (int) $this->cauHinhChungRepository->getAllConfig()['THOI_GIAN_KHOA_TAI_KHOAN'];

        // Lưu số lần đăng nhập sai vào cache
        $this->cacheService->set($attemptsKey, $attempts, $lockoutMinutes * 60);

        // Nếu vượt quá số lần cho phép, khóa tài khoản
        if ($attempts >= $maxAttempts) {
            $lockoutExpires = time() + ($lockoutMinutes * 60);
            $this->cacheService->set($lockoutKey, $lockoutExpires, $lockoutMinutes * 60);
        }
    }

    public function getLockoutTime($lockoutKey): string
    {
        $lockoutExpires = $this->cacheService->get($lockoutKey);
        if ($lockoutExpires !== null) {
            $lockoutExpires = (int) $lockoutExpires;
            ray(formatSeconds($lockoutExpires - time()));
            return formatSeconds($lockoutExpires - time());
        }
        return "";
    }

    public function checkIsTimeWork(string $currentTime, string $currentDay, User $currentUser): bool
    {
        if (isAdmin($currentUser)) {
            return true;
        }

        $now = new \DateTimeImmutable();
        $cauHinhChung = $this->cauHinhChungRepository->getAllConfig();

        if ($cauHinhChung['CHECK_THOI_GIAN_LAM_VIEC'] != Constanst::CHECK_THOI_GIAN_LAM_VIEC['KICH_HOAT']) {
            return true;
        }

        $attendances = $this->attendanceRepository->findAttendanceCandidatesByDateTimeAndEmployee(
            $now,
            $currentUser,
        );

        foreach ($attendances as $attendance) {
            if (
                $attendance->getStatus() !== StatusAttendance::Absent->value
                && $this->checkWorkScheduleOfUser->isWithinAttendanceSchedule($attendance, $now)
            ) {
                return true;
            }
        }

        $workingSchedule = $this->workScheduleService->getWorkingScheduleForUserOnDate(
            $currentUser,
            $now,
        );

        if (
            !($workingSchedule['isWorkingDay'] ?? false)
            || empty($workingSchedule['schedules'])
        ) {
            return false;
        }

        if (empty($attendances)) {
            throw new \Exception(t('error.not_in_working_schedule'));
        }

        return false;
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
        $otpCache = $this->cacheService->get($keyOtp);
        if ($otpCache !== null) {
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
        $thoiGianHieuLucOtp = (int) $this->cauHinhChungRepository->getAllConfig()['THOI_GIAN_HET_HAN_OTP'];
        $this->cacheService->set($keyOtp, $newOtp, $thoiGianHieuLucOtp * 60);

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
