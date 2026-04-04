<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class PresenceService
{
    private const ONLINE_TTL = 90;

    private const KEY_PREFIX = 'user_online_';

    private array $mercureConfig;

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly HubInterface $hub,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
    }

    public function setOnline(int $userId): void
    {
        $cacheKey = self::KEY_PREFIX . $userId;
        $isNewOnline = !$this->cacheService->has($cacheKey);
        $this->cacheService->set($cacheKey, 1, self::ONLINE_TTL);

        if ($isNewOnline) {
            $this->publishPresence($userId, true);
        }
    }

    public function setOffline(int $userId): void
    {
        $cacheKey = self::KEY_PREFIX . $userId;
        $this->cacheService->delete($cacheKey);

        $this->publishPresence($userId, false);
    }

    public function getStatuses(array $userIds): array
    {
        $result = [];

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $cacheKey = self::KEY_PREFIX . $userId;
            $result[$userId] = $this->cacheService->get($cacheKey) !== null;
        }

        return $result;
    }

    private function publishPresence(int $userId, bool $isOnline): void
    {
        $topic = $this->mercureConfig['topics']['presence'];

        $data = [
            'type'     => 'presence',
            'userId'   => $userId,
            'online'   => $isOnline,
        ];

        $update = new Update($topic, json_encode($data), false);
        $this->hub->publish($update);
    }
}
