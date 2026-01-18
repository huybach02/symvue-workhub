<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsEventListener(event: Events::AUTHENTICATION_FAILURE, method: 'onAuthenticationFailure')]
class AuthenticationFailureListener
{
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'Thông tin đăng nhập không chính xác',
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
