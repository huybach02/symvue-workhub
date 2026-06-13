<?php

namespace App\EventListener;

use App\Class\Constanst;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\CacheService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use function Symfony\Component\Clock\now;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(event: Events::JWT_DECODED, method: 'onJWTDecoded')]
class JWTBlacklistListener
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly RequestStack $requestStack,
        private readonly AuthService $authService,
        private readonly UserRepository $userRepository,
    ) {}

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader) {
            return;
        }

        $accessToken = str_replace('Bearer ', '', $authorizationHeader);
        $tokenKey = 'blacklist_' . md5($accessToken);

        $isBlacklisted = $this->cacheService->get($tokenKey, false);

        if ($isBlacklisted === true) {
            $event->markAsInvalid();
        }

        $payload = $event->getPayload();
        $email = $payload['username'] ?? null;

        if (!$email) {
            $event->markAsInvalid();
            return;
        }

        $currentUser = $this->userRepository->findOneBy(['email' => $email]);

        if (!$currentUser) {
            $event->markAsInvalid();
            return;
        }

        $currentDay = Constanst::CONVERT_DATE_TIME[now()->format('l')];
        $currentTime = now()->format('H:i');
        if (!$this->authService->checkIsTimeWork($currentTime, $currentDay, $currentUser)) {
            $event->markAsInvalid();
        }
    }
}
