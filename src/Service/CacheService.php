<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CachePersist;
use App\Repository\CachePersistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;

class CacheService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CachePersistRepository $cachePersistRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Client $redis,
    ) {}

    public function set(string $key, mixed $value, int $ttlSeconds, bool $persistToDatabase = true): void
    {
        try {
            $encodedValue = $this->encodeValue($value);

            $this->redis->setex($key, $ttlSeconds, $encodedValue);

            if (!$persistToDatabase) {
                return;
            }

            $cachePersist = $this->cachePersistRepository->findOneBy(['key' => $key]);

            if (!$cachePersist) {
                $cachePersist = new CachePersist();
                $cachePersist->setKey($key);
            }

            $cachePersist->setValue($encodedValue);
            $cachePersist->setExpireAt(time() + $ttlSeconds);

            $this->entityManager->persist($cachePersist);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Cache set failed', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $cachedValue = $this->redis->get($key);

            if ($cachedValue !== null) {
                return $this->decodeValue($cachedValue);
            }

            $cachePersist = $this->cachePersistRepository->findOneBy(['key' => $key]);

            if (!$cachePersist) {
                return $default;
            }

            $remainingTtl = $cachePersist->getExpireAt() - time();

            if ($remainingTtl <= 0) {
                $this->entityManager->remove($cachePersist);
                $this->entityManager->flush();

                return $default;
            }

            $decodedValue = $this->decodeValue($cachePersist->getValue());

            $this->redis->setex(
                $key,
                $remainingTtl,
                $this->encodeValue($decodedValue),
            );

            return $decodedValue;
        } catch (\Throwable $e) {
            $this->logger->error('Cache get failed', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key, null) !== null;
    }

    public function delete(string $key): void
    {
        try {
            $this->redis->del($key);

            $cachePersist = $this->cachePersistRepository->findOneBy(['key' => $key]);

            if ($cachePersist) {
                $this->entityManager->remove($cachePersist);
                $this->entityManager->flush();
            }
        } catch (\Throwable $e) {
            $this->logger->error('Cache delete failed', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getTTLByKey(string $key): int
    {
        $cachePersist = $this->cachePersistRepository->findOneBy(['key' => $key]);

        if (!$cachePersist) {
            return 0;
        }

        return $cachePersist->getExpireAt() - time();
    }

    public function acquireLock(string $key, int $ttlSeconds): ?string
    {
        $token = bin2hex(random_bytes(16));

        try {
            $result = $this->redis->set($key, $token, 'EX', $ttlSeconds, 'NX');
            // EX: đặt thời gian hết hạn theo giây, NX: chỉ set nếu chưa tồn tại, nếu key đã tồn tại thì không ghi đè

            return $result === 'OK' ? $token : null;
        } catch (\Throwable $e) {
            $this->logger->error('Cache lock acquire failed', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function removeLock(string $key, string $token): void
    {
        try {
            $script = <<<'LUA'
                    local lockKey = KEYS[1]
                    local expectedToken = ARGV[1]
                    local currentToken = redis.call("GET", lockKey)

                    if currentToken == expectedToken then
                        return redis.call("DEL", lockKey)
                    end

                    return 0
                LUA;

            $this->redis->eval($script, 1, $key, $token);
        } catch (\Throwable $e) {
            $this->logger->error('Cache lock release failed', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function encodeValue(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new \RuntimeException('Failed to encode cache value.');
        }

        return $encoded;
    }

    private function decodeValue(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode cache value: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
