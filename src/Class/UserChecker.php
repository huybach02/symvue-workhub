<?php

namespace App\Class;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User as AppUser;
use App\Service\AuthService;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

use function Symfony\Component\Clock\now;

class UserChecker implements UserCheckerInterface
{
    public function __construct(private AuthService $authService) {}

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        $attemptsKey = 'login_attempts_' . $user->getId();
        $lockoutKey = 'login_lockout_' . $user->getId();

        $lockout = $this->authService->getLockoutTime($lockoutKey);

        // 1. Kiểm tra khóa tài khoản khi đăng nhập sai quá số lần cho phép
        if ($lockout) {
            throw new CustomUserMessageAccountStatusException(t('auth.locked', ['%lockout%' => $lockout]));
        }

        // 2. Kiểm tra tài khoản có bị khóa do vi phạm
        if ($user->isLocked()) {
            throw new CustomUserMessageAccountStatusException(t('auth.banned'));
        }


        // // 2. Kiểm tra giờ làm việc
        $currentDay = Constanst::CONVERT_DATE_TIME[now()->format('l')];
        $currentTime = now()->format('H:i');
        if (!$this->authService->checkIsTimeWork($currentTime, $currentDay, $user)) {
            throw new CustomUserMessageAccountStatusException(t('auth.time_work'));
        }


        // // Bỏ qua check nếu là ADMIN (tuỳ chọn)
        // if (!in_array('ROLE_ADMIN', $user->getRoles()) && ($hour < $allowedStart || $hour >= $allowedEnd)) {
        //     throw new CustomUserMessageAccountStatusException('Chỉ được phép đăng nhập trong giờ hành chính.');
        // }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Check sau khi mật khẩu đã đúng nhưng trước khi cấp quyền
        // Có thể dùng để check account expired, v.v.
    }
}
