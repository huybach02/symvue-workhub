<?php

namespace App\Service\Excel\Import;

use App\DTO\UserDTO;
use App\Entity\ImportLog;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DepartmentService;
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
        private DenormalizerInterface $serializer,
        private readonly DepartmentService $boPhanService,
    ) {}

    public function import($filePath, $originalFileName, $user)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, false, false);

        $log = new ImportLog();
        $log->setFileName($originalFileName);
        $log->setEntityType('user');
        $log->setCreatedBy($user->getId());

        $successCount = 0;
        $errorCount = 0;
        $errorDetails = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Map dữ liệu từ Excel theo đúng thứ tự cột của file export
            // A=STT (bỏ qua), B=Mã NV, C=Họ tên, D=Giới tính (hiển thị),
            // E=Ngày sinh, F=CMND, G=Ngày cấp CMND, H=Nơi cấp CMND, I=Ngày vào làm,
            // J=Trạng thái (hiển thị), K=Email, L=SĐT, M=Tỉnh/TP, N=Xã/Phường, O=Địa chỉ
            // P=gender_code (ẩn, do dropdown tự điền), Q=status_code (ẩn, do dropdown tự điền)
            $data = [
                // Thông tin cá nhân
                'maNhanVien'  => excelGetValue($row, 'B'),
                'name'        => excelGetValue($row, 'C'),
                'gender'      => excelGetValue($row, 'P') ?: excelGetValue($row, 'D'),
                'birthday'    => excelGetValue($row, 'E'),
                'cmnd'        => (string)excelGetValue($row, 'F'),
                'ngayCapCmnd' => excelGetValue($row, 'G'),
                'noiCapCmnd'  => excelGetValue($row, 'H'),
                'ngayVaoLam'  => excelGetValue($row, 'I'),
                'status'      => (int)(excelGetValue($row, 'Q') !== null ? excelGetValue($row, 'Q') : excelGetValue($row, 'J')),
                'email'       => excelGetValue($row, 'K'),
                'phone'       => excelGetValue($row, 'L'),
                'boPhanId'    => (int)(excelGetValue($row, 'R') ?: excelGetValue($row, 'M')),
                // 'province'    => excelGetValue($row, 'M'),
                // 'ward'        => excelGetValue($row, 'N'),
                // 'address'     => excelGetValue($row, 'O'),
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

                // Kiểm tra mã nhân viên đã tồn tại chưa
                if ($dto->maNhanVien && $this->userRepository->findOneBy(['maNhanVien' => $dto->maNhanVien])) {
                    $errorCount++;
                    $errorDetails[] = [
                        'row' => $i + 1,
                        'data_raw' => $data,
                        'errors' => ['Mã nhân viên này đã tồn tại trong hệ thống.']
                    ];
                    continue;
                }

                $user = new User();

                // Thông tin cá nhân
                $user->setMaNhanVien($dto->maNhanVien);
                $user->setName($dto->name);
                $user->setGender($dto->gender);
                $user->setBirthday($dto->birthday);
                $user->setCmnd($dto->cmnd);
                $user->setNgayCapCmnd($dto->ngayCapCmnd);
                $user->setNoiCapCmnd($dto->noiCapCmnd);

                // Thông tin công việc
                $user->setNgayVaoLam($dto->ngayVaoLam);
                $user->setStatus($dto->status);

                // Thông tin liên hệ
                $user->setEmail($dto->email);
                $user->setPhone($dto->phone);
                $user->setBoPhanId($dto->boPhanId);
                // $user->setProvince($dto->province);
                // $user->setWard($dto->ward);
                // $user->setAddress($dto->address);

                $user->setPassword(password_hash('password', PASSWORD_DEFAULT));

                // ============== HẾT CHỖ CẦN SỬA 2 ============== 


                $this->em->persist($user);
                $this->em->flush();

                if ($dto->boPhanId) {
                    $this->boPhanService->handleAddUserPermission($user->getId(), $dto->boPhanId, true);
                }

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
