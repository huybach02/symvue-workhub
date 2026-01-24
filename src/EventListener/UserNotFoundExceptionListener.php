<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException', priority: 10)]
class UserNotFoundExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof UserNotFoundException) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getPathInfo() !== '/api/auth/login') {
            return;
        }

        $response = new JsonResponse([
            'success' => false,
            'message' => t('auth.login.invalid'),
        ], JsonResponse::HTTP_UNAUTHORIZED);

        $event->setResponse($response);
    }
}
