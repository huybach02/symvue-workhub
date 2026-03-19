<?php

namespace App\EventListener;

use App\Class\Constanst;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function Symfony\Component\Clock\now;

#[AsEventListener(event: Events::JWT_DECODED, method: 'onJWTDecoded')]
class JWTBlacklistListener
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly RequestStack $requestStack,
        private readonly AuthService $authService
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

        $item = $this->cache->getItem($tokenKey);
        $isBlacklisted = $item->isHit() ? $item->get() : false;

        if ($isBlacklisted === true) {
            $event->markAsInvalid();
        }

        $currentDay = Constanst::CONVERT_DATE_TIME[now()->format('l')];
        $currentTime = now()->format('H:i');
        if (!$this->authService->checkIsTimeWork($currentTime, $currentDay)) {
            $event->markAsInvalid();
        }
    }
}
