<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\CheckWorkScheduleOfUser;
use App\Class\FilterWithPagination;
use App\Class\ValidationStatus;
use App\DTO\AttendanceDTO;
use App\Entity\Attendance;
use App\Entity\User;
use App\Repository\AttendanceLogRepository;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AttendanceService
{
    private const QR_DISPLAY_ACCESS_PREFIX = 'attendance.qr.display.';
    private const QR_DISPLAY_ACCESS_CURRENT_KEY = 'attendance.qr.display.current';
    private const QR_TOKEN_CACHE_PREFIX = 'attendance.qr.token.';
    private const LOG_REASON_QR_CODE_VALID = 'qr_code_valid';
    private const LOG_REASON_QR_CODE_INVALID = 'qr_code_invalid';
    private const LOG_REASON_IP_ADDRESS_VALID = 'ip_address_valid';
    private const LOG_REASON_IP_ADDRESS_INVALID = 'ip_address_invalid';
    private const LOG_REASON_LOCATION_VALID = 'location_valid';
    private const LOG_REASON_LOCATION_INVALID = 'location_invalid';
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
        private readonly WorkScheduleService $workScheduleService,
        private readonly CheckWorkScheduleOfUser $checkWorkScheduleOfUser,
        private readonly AttendanceLogRepository $attendanceLogRepository,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->attendanceRepository->createQueryBuilder('e');

        if (($params['realtime_view'] ?? 0) == 1) {
            $qb
                ->andWhere('e.status IS NOT NULL')
                ->andWhere('e.status != :scheduledStatus')
                ->setParameter('scheduledStatus', 'scheduled');
        }

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
        $this->cacheService->set(
            $this->buildQrTokenCacheKey($token),
            $token,
            $qrTtlSeconds,
            false,
        );

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

    public function verifyAttendance(Request $request, AttendanceDTO $attendanceDTO, User $currentUser): bool
    {
        $configs = $this->cauHinhChungRepository->getAllConfig();
        $now = new \DateTimeImmutable();
        $attendanceInfo = $this->buildAttendanceInfo($request, $attendanceDTO);
        $attendance = $this->resolveAttendanceForLog($now, $currentUser, $configs);

        // 1. Verify qr code từ dto
        $qrCode = $attendanceDTO->qrCode;
        $qrCodeFromCache = $this->cacheService->get(
            $this->buildQrTokenCacheKey((string) $qrCode),
        );
        if ($qrCodeFromCache === null) {
            $this->createValidationLog(
                attendance: $attendance,
                attendanceInfo: $attendanceInfo,
                validationStatus: ValidationStatus::Invalid->value,
                validationReason: self::LOG_REASON_QR_CODE_INVALID,
            );
            throw new \Exception(t("error.qr_code_invalid"));
        }
        $this->createValidationLog(
            attendance: $attendance,
            attendanceInfo: $attendanceInfo,
            validationStatus: ValidationStatus::Valid->value,
            validationReason: self::LOG_REASON_QR_CODE_VALID,
        );

        // 2. Check IP address
        // Kiểm tra env APP_ENV có là dev hay không
        $ipAddress = $attendanceInfo['ipAddress'];
        if (appEnv('APP_ENV') !== 'dev') {
            $allowedIpAddresses = $this->parseAllowedIpAddresses(
                $configs['IP_ADDRESS'] ?? null,
            );

            if ($ipAddress === null || !in_array($ipAddress, $allowedIpAddresses, true)) {
                $this->createValidationLog(
                    attendance: $attendance,
                    attendanceInfo: $attendanceInfo,
                    validationStatus: ValidationStatus::Invalid->value,
                    validationReason: self::LOG_REASON_IP_ADDRESS_INVALID,
                );
                throw new \Exception(t("error.ip_address_invalid"));
            }
        }
        $this->createValidationLog(
            attendance: $attendance,
            attendanceInfo: $attendanceInfo,
            validationStatus: ValidationStatus::Valid->value,
            validationReason: self::LOG_REASON_IP_ADDRESS_VALID,
        );

        // 3. Check location
        try {
            assertAttendanceLocationWithinConfiguredRadius(
                $attendanceDTO->latitude,
                $attendanceDTO->longitude,
                $configs
            );
        } catch (\Exception $exception) {
            $this->createValidationLog(
                attendance: $attendance,
                attendanceInfo: $attendanceInfo,
                validationStatus: ValidationStatus::Invalid->value,
                validationReason: self::LOG_REASON_LOCATION_INVALID,
            );

            throw $exception;
        }
        $this->createValidationLog(
            attendance: $attendance,
            attendanceInfo: $attendanceInfo,
            validationStatus: ValidationStatus::Valid->value,
            validationReason: self::LOG_REASON_LOCATION_VALID,
        );

        // 4. Check working schedule
        $this->checkWorkScheduleOfUser->checkWorkingScheduleOfUser(
            $now,
            $currentUser,
            $attendanceInfo,
        );

        return true;
    }

    private function buildQrTokenCacheKey(string $token): string
    {
        return self::QR_TOKEN_CACHE_PREFIX . hash('sha256', $token);
    }

    /**
     * @return string[]
     */
    private function parseAllowedIpAddresses(null|string|array $value): array
    {
        if (is_array($value)) {
            $values = $value;
        } else {
            $values = preg_split('/[\s,;]+/', (string) $value) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn(mixed $item): string => trim((string) $item),
            $values,
        )));
    }

    private function buildAttendanceInfo(Request $request, AttendanceDTO $attendanceDTO): array
    {
        return [
            "ipAddress" => $request->getClientIp(),
            "latitude" => $attendanceDTO->latitude !== null ? (string) $attendanceDTO->latitude : null,
            "longtitude" => $attendanceDTO->longitude !== null ? (string) $attendanceDTO->longitude : null,
            "gpsAccuracyMeter" => $attendanceDTO->accuracy !== null ? (int) $attendanceDTO->accuracy : null,
            "deviceId" => $request->headers->get('Device-Id') ? (int) $request->headers->get('Device-Id') : null,
            "qrToken" => $attendanceDTO->qrCode,
        ];
    }

    private function resolveAttendanceForLog(
        \DateTimeImmutable $now,
        User $currentUser,
        array $configs,
    ): ?Attendance {
        $attendances = $this->attendanceRepository->findAttendanceCandidatesByDateTimeAndEmployee(
            $now,
            $currentUser,
        );

        return $this->checkWorkScheduleOfUser->resolveAttendanceRecordByNow(
            $attendances,
            $now,
            $configs,
        );
    }

    private function createValidationLog(
        ?Attendance $attendance,
        array $attendanceInfo,
        string $validationStatus,
        string $validationReason,
    ): void {
        $this->attendanceLogRepository->createAttendanceLog(
            attendance: $attendance,
            ipAddress: $attendanceInfo['ipAddress'],
            latitude: $attendanceInfo['latitude'],
            longtitude: $attendanceInfo['longtitude'],
            gpsAccuracyMeter: $attendanceInfo['gpsAccuracyMeter'],
            deviceId: $attendanceInfo['deviceId'],
            qrToken: $attendanceInfo['qrToken'],
            validationStatus: $validationStatus,
            validationReason: $validationReason,
        );
    }
}
