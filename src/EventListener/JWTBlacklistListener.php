<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(event: Events::JWT_DECODED, method: 'onJWTDecoded')]
class JWTBlacklistListener
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly RequestStack $requestStack
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

        $isBlacklisted = $this->cache->get($tokenKey, fn() => false);

        if ($isBlacklisted === true) {
            $event->markAsInvalid();
        }
    }
}
