<?php

declare(strict_types=1);

namespace App\Service\Excel\Import;

use App\DTO\ProviderDTO;
use App\Entity\ImportLog;
use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProviderImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProviderRepository $providerRepository,
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
        $log->setEntityType('provider');
        $log->setCreatedBy($user->getId());

        $successCount = 0;
        $errorCount = 0;
        $errorDetails = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // A=STT, B=Mã NCC, C=Tên NCC, D=SĐT, E=Email, F=Địa chỉ, G=Mã số thuế, H=Ngân hàng, I=Số TK, J=Trạng thái, K=Ghi chú
            // L=status_code (cột ẩn)
            $data = [
                'code'       => excelGetValue($row, 'B'),
                'name'       => excelGetValue($row, 'C'),
                'phone'      => excelGetValue($row, 'D') ? (string)excelGetValue($row, 'D') : null,
                'email'      => excelGetValue($row, 'E'),
                'address'    => excelGetValue($row, 'F'),
                'taxNumber'  => excelGetValue($row, 'G') ? (string)excelGetValue($row, 'G') : null,
                'bankName'   => excelGetValue($row, 'H'),
                'bankNumber' => excelGetValue($row, 'I') ? (string)excelGetValue($row, 'I') : null,
                'status'     => (int)(excelGetValue($row, 'L') !== null ? excelGetValue($row, 'L') : excelGetValue($row, 'J')),
                'note'       => excelGetValue($row, 'K'),
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

                // Kiểm tra mã nhà cung cấp đã tồn tại chưa
                if ($dto->code && $this->providerRepository->findOneBy(['code' => $dto->code])) {
                    $errorCount++;
                    $errorDetails[] = [
                        'row' => $i + 1,
                        'data_raw' => $data,
                        'errors' => ['Mã nhà cung cấp này đã tồn tại trong hệ thống.']
                    ];
                    continue;
                }

                $provider = new Provider();
                $provider->setCode($dto->code);
                $provider->setName($dto->name);
                $provider->setPhone($dto->phone);
                $provider->setEmail($dto->email);
                $provider->setAddress($dto->address);
                $provider->setTaxNumber($dto->taxNumber);
                $provider->setBankName($dto->bankName);
                $provider->setBankNumber($dto->bankNumber);
                $provider->setStatus($dto->status);
                $provider->setNote($dto->note);

                $this->em->persist($provider);
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
            $dto = $this->serializer->denormalize($rawData, ProviderDTO::class);

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
