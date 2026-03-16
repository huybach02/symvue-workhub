<?php

declare(strict_types=1);

namespace App\Service\Excel\Template;

use App\Repository\DepartmentRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repository\UserRepository;

class UserTemplateImportService extends BaseExcelTemplateHelper
{
    public function __construct(private UserRepository $userRepository, private DepartmentRepository $boPhanRepository) {}

    public function generateUserTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // --- SHEET 1: FORM NHẬP LIỆU ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users_Import');

        /**
         * Cấu trúc cột (đồng bộ với Export và Import):
         * A=STT (bỏ trống, ImportService bỏ qua), B=Mã NV, C=Họ tên, D=Giới tính,
         * E=Ngày sinh, F=CMND, G=Ngày cấp CMND, H=Nơi cấp CMND,
         * I=Ngày vào làm, J=Trạng thái (dropdown → code), K=Email,
         * L=SĐT, M=Tỉnh/TP, N=Xã/Phường, O=Địa chỉ
         *
         * Cột ẩn chứa code (tự động điền bởi dropdown mapping):
         * P=gender_code, Q=status_code
         */
        $headers = [
            'A' => 'STT',
            'B' => 'Mã nhân viên (*)',
            'C' => 'Họ và tên (*)',
            'D' => 'Giới tính (*)',
            'E' => 'Ngày sinh (*) (YYYY-MM-DD)',
            'F' => 'CMND/CCCD',
            'G' => 'Ngày cấp CMND/CCCD (YYYY-MM-DD)',
            'H' => 'Nơi cấp CMND/CCCD',
            'I' => 'Ngày vào làm (YYYY-MM-DD)',
            'J' => 'Trạng thái (*)',
            'K' => 'Email (*)',
            'L' => 'Số điện thoại (*)',
            'M' => 'Bộ phận (*)',
            // 'N' => 'Tỉnh/Thành phố (*)',
            // 'O' => 'Xã/Phường (*)',
            // 'P' => 'Địa chỉ (*)',
        ];

        foreach ($headers as $col => $label) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DBEAFE'); // Nền xanh nhạt
        }

        // Dữ liệu mẫu (Row 2)
        $sheet->fromArray([
            '',                      // A: STT (bỏ trống)
            'NV001',                 // B: Mã nhân viên
            'Nguyễn Văn A',          // C: Họ và tên
            'Nam',                   // D: Giới tính (dropdown)
            '1990-01-15',            // E: Ngày sinh
            '123456789',             // F: CMND/CCCD
            '2015-06-20',            // G: Ngày cấp CMND/CCCD
            'Cục Cảnh sát QLHC về TTXH',  // H: Nơi cấp
            '2020-01-01',            // I: Ngày vào làm
            'Hoạt động',            // J: Trạng thái (dropdown)
            'email@example.com',     // K: Email
            '0912345678',            // L: Số điện thoại
            // 'Hà Nội',                // M: Tỉnh/Thành phố
            // 'Phường Hàng Bông',      // N: Xã/Phường
            // 'Số 123, Đường Đinh Tiên Hoàng', // O: Địa chỉ
        ], null, 'A2');

        // --- SETUP DROPDOWN VỚI AUTO-MAPPING ---
        // Dropdown sẽ hiển thị text thân thiện, cột ẩn lưu code để import

        $boPhanList = $this->boPhanRepository->findAll();
        $boPhanMappings = [];
        foreach ($boPhanList as $boPhan) {
            $boPhanMappings[$boPhan->getId()] = $boPhan->getTenBoPhan();
        }

        $dropdownConfigs = [
            [
                'sourceColumn' => 'D',       // Cột hiển thị dropdown
                'targetColumn' => 'P',       // Cột ẩn chứa giá trị code
                'refSheetName' => 'Giới tính',
                'mappings' => [
                    'male'   => 'Nam',
                    'female' => 'Nữ',
                ],
                'promptTitle'   => 'Chọn giới tính',
                'promptMessage' => 'Chọn Nam hoặc Nữ',
                'headerName'    => 'gender_code',
            ],
            [
                'sourceColumn' => 'J',       // Cột hiển thị dropdown
                'targetColumn' => 'Q',       // Cột ẩn chứa giá trị code
                'refSheetName' => 'Trạng thái',
                'mappings' => [
                    '1' => 'Hoạt động',
                    '0' => 'Không hoạt động',
                ],
                'promptTitle'   => 'Chọn trạng thái',
                'promptMessage' => 'Chọn Hoạt động hoặc Không hoạt động',
                'headerName'    => 'status_code',
            ],
            [
                'sourceColumn' => 'M',       // Cột hiển thị dropdown
                'targetColumn' => 'R',       // Cột ẩn chứa giá trị code
                'refSheetName' => 'Bộ phận',
                'mappings' => $boPhanMappings,
                'promptTitle'   => 'Chọn bộ phận',
                'promptMessage' => 'Chọn bộ phận',
                'headerName'    => 'bo_phan_code',
            ],
        ];

        // Tự động setup tất cả dropdown với 1 dòng code
        $this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);

        // Auto-size cho tất cả các cột hiển thị (A-O)
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // --- XUẤT FILE ---
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="user_template.xlsx"');

        return $response;
    }
}
