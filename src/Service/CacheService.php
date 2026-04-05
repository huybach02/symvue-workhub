<?php

namespace App\Service;

use App\Entity\CachePersist;
use App\Repository\CachePersistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class CacheService
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly CachePersistRepository $cachePersistRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        try {
            $encodedValue = $this->encodeValue($value);

            $cacheItem = $this->cache->getItem($key);
            $cacheItem->set($value);
            $cacheItem->expiresAfter($ttlSeconds);
            $this->cache->save($cacheItem);

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
            $cacheItem = $this->cache->getItem($key);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
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

            $cacheItem->set($decodedValue);
            $cacheItem->expiresAfter($remainingTtl);
            $this->cache->save($cacheItem);

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
            $this->cache->deleteItem($key);

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
