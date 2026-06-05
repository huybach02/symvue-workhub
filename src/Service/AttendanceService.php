<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\AttendanceDTO;
use App\Entity\Attendance;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class AttendanceService
{
    private const QR_DISPLAY_ACCESS_PREFIX = 'attendance.qr.display.';
    private const QR_DISPLAY_ACCESS_CURRENT_KEY = 'attendance.qr.display.current';
    private const QR_DISPLAY_ACCESS_TTL_SECONDS = 157680000; // 5 years
    private const QR_HARD_RELOAD_WINDOWS = [
        [
            'hour' => 0,
            'minute' => 0,
            'durationMinutes' => 5,
        ],
    ];

    public function __construct(
        private readonly AttendanceRepository $attendanceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GeneralSettingRepository $cauHinhChungRepository,
        private readonly CacheService $cacheService,
    ) {
    }

    public function findAll(array $params): array
    {
        $qb = $this->attendanceRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Attendance $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->attendanceRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(AttendanceDTO $dto): array
    {
        $item = new Attendance();

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, AttendanceDTO $dto): array
    {
        $item = $this->attendanceRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->attendanceRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function createQrDisplayAccess(): array
    {
        $this->revokeQrDisplayAccess();

        $token = bin2hex(random_bytes(32));
        $this->cacheService->set(
            self::QR_DISPLAY_ACCESS_PREFIX . $token,
            1,
            self::QR_DISPLAY_ACCESS_TTL_SECONDS,
            false,
        );
        $this->cacheService->set(
            self::QR_DISPLAY_ACCESS_CURRENT_KEY,
            $token,
            self::QR_DISPLAY_ACCESS_TTL_SECONDS,
            false,
        );

        return [
            'token' => $token,
            'expiresAtMs' => ((int) floor(microtime(true) * 1000)) +
                (self::QR_DISPLAY_ACCESS_TTL_SECONDS * 1000),
        ];
    }

    public function revokeQrDisplayAccess(?string $accessToken = null): void
    {
        $resolvedToken = $accessToken;

        if (!$resolvedToken) {
            $currentToken = $this->cacheService->get(
                self::QR_DISPLAY_ACCESS_CURRENT_KEY,
            );
            $resolvedToken = is_string($currentToken) ? $currentToken : null;
        }

        if (!$resolvedToken) {
            return;
        }

        $this->cacheService->delete(self::QR_DISPLAY_ACCESS_PREFIX . $resolvedToken);

        $currentToken = $this->cacheService->get(self::QR_DISPLAY_ACCESS_CURRENT_KEY);
        if ($currentToken === $resolvedToken) {
            $this->cacheService->delete(self::QR_DISPLAY_ACCESS_CURRENT_KEY);
        }
    }

    public function validateQrDisplayAccess(?string $accessToken): void
    {
        if (!$accessToken) {
            throw new \InvalidArgumentException('Thiếu access token cho trang QR attendance.');
        }

        $cacheKey = self::QR_DISPLAY_ACCESS_PREFIX . $accessToken;
        if ($this->cacheService->get($cacheKey) === null) {
            throw new \InvalidArgumentException('Access token cho trang QR attendance không hợp lệ hoặc đã hết hạn.');
        }
    }

    public function getQrAttendance(?string $accessToken = null): array
    {
        $this->validateQrDisplayAccess($accessToken);

        $qrTtlSeconds = (int) $this->cauHinhChungRepository->getAllConfig()['QR_TTL_SECONDS'];
        $serverTimeMs = (int) floor(microtime(true) * 1000);
        $expiresAtMs = $serverTimeMs + ($qrTtlSeconds * 1000);

        $token = generateQRCodeAttendance();
        // QR attendance chi can song trong Redis/cache trong thoi gian ngan,
        // khong can persist xuong DB moi lan rotate de tranh tang tai khong can thiet.
        $this->cacheService->set($token, $token, $qrTtlSeconds, false);

        $serverTime = new \DateTime();
        $expiresAt = (clone $serverTime)->modify(
            sprintf('+%d seconds', $qrTtlSeconds),
        );

        $payload = [
            'token' => $token,
            'serverTime' => $serverTime->format(DATE_ATOM),
            'expiresAt' => $expiresAt->format(DATE_ATOM),
            'serverTimeMs' => $serverTimeMs,
            'expiresAtMs' => $expiresAtMs,
            'ttlMs' => $qrTtlSeconds * 1000,
        ];

        $hardReloadKey = $this->resolveHardReloadKey($serverTime);
        if ($hardReloadKey !== null) {
            $payload['isHardReload'] = true;
            $payload['hardReloadKey'] = $hardReloadKey;
        }

        return $payload;
    }

    private function resolveHardReloadKey(\DateTimeInterface $serverTime): ?string
    {
        $currentMinuteOfDay = ((int) $serverTime->format('H') * 60) +
            (int) $serverTime->format('i');

        foreach (self::QR_HARD_RELOAD_WINDOWS as $window) {
            $startMinuteOfDay = (((int) ($window['hour'] ?? 0)) * 60) +
                (int) ($window['minute'] ?? 0);
            $durationMinutes = max(1, (int) ($window['durationMinutes'] ?? 1));

            if (
                $currentMinuteOfDay >= $startMinuteOfDay &&
                $currentMinuteOfDay < ($startMinuteOfDay + $durationMinutes)
            ) {
                return sprintf(
                    '%s-%02d%02d',
                    $serverTime->format('Y-m-d'),
                    (int) ($window['hour'] ?? 0),
                    (int) ($window['minute'] ?? 0),
                );
            }
        }

        return null;
    }
}
