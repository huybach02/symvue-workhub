<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

#[AsEventListener(event: Events::AUTHENTICATION_FAILURE, method: 'onAuthenticationFailure')]
class AuthenticationFailureListener
{
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        // Xử lý trường hợp tài khoản có vấn đề (bị khóa, chưa kích hoạt, v.v.)
        if ($exception instanceof AccountStatusException) {
            $response = new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);

            $event->setResponse($response);
            return;
        }

        if ($exception instanceof BadCredentialsException) {
            $response = new JsonResponse([
                'success' => false,
                'message' => t('auth.login.invalid'),
            ], JsonResponse::HTTP_UNAUTHORIZED);

            $event->setResponse($response);
            return;
        }

        $response = new JsonResponse([
            'success' => false,
            'message' => t('auth.login.error'),
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
