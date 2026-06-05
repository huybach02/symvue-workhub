<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\AttendanceDTO;
use App\Entity\Attendance;
use App\Repository\AttendanceRepository;
use App\Repository\GeneralSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class AttendanceService
{
    private array $mercureConfig;

    public function __construct(
        private readonly AttendanceRepository $attendanceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GeneralSettingRepository $cauHinhChungRepository,
        private readonly CacheService $cacheService,
        private readonly HubInterface $hub,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        $this->mercureConfig = require $this->projectDir . '/config/mercure.php';
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

    public function getQrAttendance(string $channel): void
    {
        if ('' === trim($channel)) {
            throw new \InvalidArgumentException('Thiếu channel QR attendance.');
        }

        $qrTtlSeconds = (int) $this->cauHinhChungRepository->getAllConfig()['QR_TTL_SECONDS'];
        $serverTimeMs = (int) floor(microtime(true) * 1000);
        $expiresAtMs = $serverTimeMs + ($qrTtlSeconds * 1000);

        $token = generateQRCodeAttendance();
        $this->cacheService->set($token, $token, $qrTtlSeconds);

        $payload = [
            'type' => 'attendance_qr',
            'channel' => $channel,
            'token' => $token,
            'serverTimeMs' => $serverTimeMs,
            'expiresAtMs' => $expiresAtMs,
            'ttlMs' => $qrTtlSeconds * 1000,
        ];

        $topic = str_replace(':channel', $channel, $this->mercureConfig['topics']['attendance']);
        $this->hub->publish(new Update($topic, json_encode($payload), false));
    }
}
