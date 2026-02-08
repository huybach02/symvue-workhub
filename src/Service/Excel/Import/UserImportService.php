<?php

namespace App\Service\Excel\Import;

use App\DTO\UserDTO;
use App\Entity\ImportLog;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private DenormalizerInterface $serializer
    ) {}

    public function import($filePath, $originalFileName, $user)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray();

        $log = new ImportLog();
        $log->setFileName($originalFileName);
        $log->setEntityType('user');
        $log->setCreatedBy($user->getId());

        $successCount = 0;
        $errorCount = 0;
        $errorDetails = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Map dữ liệu từ Excel
            // ============== CHỖ CẦN SỬA 1 ==============
            $data = [
                'name' => excelGetValue($row, 'A'),
                'email' => excelGetValue($row, 'B'),
                'phone' => excelGetValue($row, 'C'),
                'gender' => excelGetValue($row, 'L'),
                'birthday' => excelGetValue($row, 'E'),
                'province' => excelGetValue($row, 'F'),
                'ward' => excelGetValue($row, 'G'),
                'address' => excelGetValue($row, 'H'),
                'hinhThucLamViec' => (int)excelGetValue($row, 'M'),
                'isNgoaiGio' => (int)excelGetValue($row, 'N'),
                'status' => (int)excelGetValue($row, 'O'),
            ];
            // ============== HẾT CHỖ CẦN SỬA 1 ==============

            if (empty(array_filter($data))) continue;

            // 1. VALIDATE DỮ LIỆU
            $validationResult = $this->processRowData($data);

            if (!empty($validationResult['errors'])) {
                // TRƯỜNG HỢP LỖI VALIDATE
                $errorCount++;
                $errorDetails[] = [
                    'row' => $i + 1,
                    'data_raw' => $data,
                    'errors' => $validationResult['errors']
                ];
            } else {
                // TRƯỜNG HỢP VALIDATE THÀNH CÔNG

                // ============== CHỖ CẦN SỬA 2 ============== 
                $dto = $validationResult['dto'];

                // Kiểm tra Email đã tồn tại chưa
                if ($this->userRepository->findOneBy(['email' => $dto->email])) {
                    $errorCount++;
                    $errorDetails[] = [
                        'row' => $i + 1,
                        'data_raw' => $data,
                        'errors' => ['Email này đã tồn tại trong hệ thống.']
                    ];
                    continue;
                }

                $user = new User();
                $user->setName($dto->name);
                $user->setEmail($dto->email);
                $user->setPhone($dto->phone);
                $user->setGender($dto->gender);
                $user->setBirthday($dto->birthday);
                // $user->setProvince($dto->province);
                // $user->setWard($dto->ward);
                $user->setAddress($dto->address);
                $user->setHinhThucLamViec($dto->hinhThucLamViec);
                $user->setIsNgoaiGio($dto->isNgoaiGio);
                $user->setStatus($dto->status);

                $user->setPassword(password_hash('password', PASSWORD_DEFAULT));

                // ============== HẾT CHỖ CẦN SỬA 2 ============== 


                $this->em->persist($user);
                $successCount++;
            }
        }

        if ($successCount > 0) {
            $this->em->flush();
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
            // 1. Denormalize: Chuyển Array thành Object DTO
            $dto = $this->serializer->denormalize($rawData, UserDTO::class);

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
