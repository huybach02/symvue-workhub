<?php

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repository\UserRepository;

class UserTemplateImportService extends BaseExcelTemplateHelper
{
    public function __construct(private UserRepository $userRepository) {}

    public function generateUserTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // --- SHEET 1: FORM NHẬP LIỆU ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users_Import');

        // Header - Cột hiển thị (người dùng nhập)
        $headers = [
            'Họ và tên (*)',           // A
            'Email (*)',               // B
            'Số điện thoại (*)',       // C
            'Giới tính (*)',           // D - Dropdown text
            'Ngày sinh (*)',           // E
            'Tỉnh/Thành phố (*)',      // F
            'Quận/Huyện (*)',          // G
            'Địa chỉ (*)',             // H
            'Hình thức làm việc (*)',  // I - Dropdown text
            'Cho phép ngoài giờ (*)',  // J - Dropdown text
            'Trạng thái (*)',          // K - Dropdown text
        ];

        $columnLetter = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($columnLetter . '1', $header);
            $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
            $columnLetter++;
        }

        // Dữ liệu mẫu (Row 2)
        $sheet->fromArray([
            'Nguyễn Văn A',
            'email@example.com',
            '0123456789',
            'Nam',                  // D: Giới tính
            '1990-01-01',
            'Hà Nội',
            'Quận Hoàn Kiếm',
            'Số 123, Đường ABC',
            'Cố định',              // I: Hình thức làm việc
            'Có',                   // J: Cho phép ngoài giờ
            'Hoạt động'             // K: Trạng thái
        ], null, 'A2');

        // --- SETUP DROPDOWN VỚI AUTO-MAPPING ---
        // Cấu hình tất cả dropdown cần thiết
        $dropdownConfigs = [
            [
                'sourceColumn' => 'D',
                'targetColumn' => 'L',
                'refSheetName' => 'Giới tính',
                'mappings' => [
                    'male' => 'Nam',
                    'female' => 'Nữ',
                ],
                'promptTitle' => 'Chọn giới tính',
                'promptMessage' => 'Chọn Nam hoặc Nữ',
                'headerName' => 'gender_code',
            ],
            [
                'sourceColumn' => 'I',
                'targetColumn' => 'M',
                'refSheetName' => 'Hình thức làm việc',
                'mappings' => [
                    '1' => 'Cố định',
                    '2' => 'Thời vụ',
                ],
                'promptTitle' => 'Chọn hình thức làm việc',
                'promptMessage' => 'Chọn Cố định hoặc Thời vụ',
                'headerName' => 'hinhThucLamViec_code',
            ],
            [
                'sourceColumn' => 'J',
                'targetColumn' => 'N',
                'refSheetName' => 'Cho phép ngoài giờ',
                'mappings' => [
                    '0' => 'Không',
                    '1' => 'Có',
                ],
                'promptTitle' => 'Cho phép ngoài giờ',
                'promptMessage' => 'Chọn Không hoặc Có',
                'headerName' => 'isNgoaiGio_code',
            ],
            [
                'sourceColumn' => 'K',
                'targetColumn' => 'O',
                'refSheetName' => 'Trạng thái',
                'mappings' => [
                    '0' => 'Không hoạt động',
                    '1' => 'Hoạt động',
                ],
                'promptTitle' => 'Chọn trạng thái',
                'promptMessage' => 'Chọn Không hoạt động hoặc Hoạt động',
                'headerName' => 'status_code',
            ],
        ];

        // Tự động setup tất cả dropdown với 1 dòng code
        $this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);

        // Auto-size cho các cột hiển thị (A-K)
        foreach (range('A', 'K') as $col) {
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
