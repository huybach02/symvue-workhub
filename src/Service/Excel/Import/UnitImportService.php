<?php

declare(strict_types=1);

namespace App\Service\Excel\Import;

use App\DTO\UnitDTO;
use App\Entity\ImportLog;
use App\Entity\Unit;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UnitImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UnitRepository $unitRepository,
        private ValidatorInterface $validator,
        private DenormalizerInterface $serializer,
    ) {}

    public function import($filePath, $originalFileName, $user)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, false, false);

        $log = new ImportLog();
        $log->setFileName($originalFileName);
        $log->setEntityType('unit');
        $log->setCreatedBy($user->getId());

        $successCount = 0;
        $errorCount = 0;
        $errorDetails = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // A=STT, B=Mã ĐVT, C=Tên ĐVT, D=Ký hiệu, E=Trạng thái
            // F=status_code (cột ẩn)
            $data = [
                'code'   => excelGetValue($row, 'B'),
                'name'   => excelGetValue($row, 'C'),
                'symbol' => excelGetValue($row, 'D'),
                'status' => (int)(excelGetValue($row, 'F') !== null ? excelGetValue($row, 'F') : excelGetValue($row, 'E')),
            ];

            if (empty(array_filter($data))) {
                continue;
            }

            // 1. VALIDATE DỮ LIỆU
            $validationResult = $this->processRowData($data);

            if (!empty($validationResult['errors'])) {
                $errorCount++;
                $errorDetails[] = [
                    'row' => $i + 1,
                    'data_raw' => $data,
                    'errors' => $validationResult['errors']
                ];
            } else {
                $dto = $validationResult['dto'];

                // Kiểm tra mã đơn vị tính đã tồn tại chưa
                if ($dto->code && $this->unitRepository->findOneBy(['code' => $dto->code])) {
                    $errorCount++;
                    $errorDetails[] = [
                        'row' => $i + 1,
                        'data_raw' => $data,
                        'errors' => ['Mã đơn vị tính này đã tồn tại trong hệ thống.']
                    ];
                    continue;
                }

                $unit = new Unit();
                $unit->setCode($dto->code);
                $unit->setName($dto->name);
                $unit->setSymbol($dto->symbol);
                $unit->setStatus($dto->status);

                $this->em->persist($unit);
                $this->em->flush();

                $successCount++;
            }
        }

        $log->setTotalRows($successCount + $errorCount);
        $log->setSuccessRows($successCount);
        $log->setErrorRows($errorCount);
        $log->setErrorDetails($errorDetails);
        $log->setStatus($errorCount === 0 ? 'SUCCESS' : ($successCount === 0 ? 'FAILED' : 'PARTIAL'));

        $this->em->persist($log);
        $this->em->flush();

        return $errorCount;
    }

    private function processRowData(array $rawData): array
    {
        try {
            $dto = $this->serializer->denormalize($rawData, UnitDTO::class);

            $violations = $this->validator->validate($dto, null, ['create']);
            $errors = [];

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }
            }

            return ['dto' => $dto, 'errors' => $errors];
        } catch (\Exception $e) {
            return ['dto' => null, 'errors' => ['Dữ liệu không đúng định dạng: ' . $e->getMessage()]];
        }
    }
}
