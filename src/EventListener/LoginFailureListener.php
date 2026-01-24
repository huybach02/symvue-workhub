<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

#[AsEventListener(event: LoginFailureEvent::class, method: 'onLoginFailure')]
class LoginFailureListener
{
    public function __construct(private EntityManagerInterface $em, private AuthService $authService) {}

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        // Chỉ xử lý nếu lỗi là do sai mật khẩu (BadCredentials)
        // Nếu lỗi do UserChecker (đang bị khóa) thì không cần đếm thêm làm gì
        if (!$event->getException() instanceof BadCredentialsException) {
            return;
        }

        // Lấy Passport để tìm User
        $passport = $event->getPassport();
        if (!$passport) {
            return;
        }

        $user = $passport->getUser();
        if (!$user instanceof User) {
            return;
        }

        $attemptsKey = 'login_attempts_' . $user->getId();
        $lockoutKey = 'login_lockout_' . $user->getId();

        $this->authService->handleLoginAttempts($attemptsKey, $lockoutKey);
    }
}
