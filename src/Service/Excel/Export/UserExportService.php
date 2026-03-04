<?php

declare(strict_types=1);

namespace App\Service\Excel\Export;

use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportService
{
    private const STATUS_LABELS = [
        1 => 'Hoạt động',
        0 => 'Không hoạt động',
    ];

    private const GENDER_LABELS = [
        'male'   => 'Nam',
        'female' => 'Nữ',
        'other'  => 'Khác',
    ];

    public function export(array $users): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách người dùng');

        // 1. Tạo Header
        $headers = [
            'A' => 'STT',
            'B' => 'Mã nhân viên',
            'C' => 'Họ và tên',
            'D' => 'Giới tính',
            'E' => 'Ngày sinh',
            'F' => 'CMND/CCCD',
            'G' => 'Ngày cấp CMND/CCCD',
            'H' => 'Nơi cấp CMND/CCCD',
            'I' => 'Ngày vào làm',
            'J' => 'Trạng thái',
            'K' => 'Email',
            'L' => 'Số điện thoại',
            'M' => 'Tỉnh/Thành phố',
            'N' => 'Xã/Phường',
            'O' => 'Địa chỉ',
        ];

        foreach ($headers as $col => $label) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DBEAFE'); // Nền xanh nhạt cho header
        }

        // 2. Đổ dữ liệu
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $row - 1);
            $sheet->setCellValue('B' . $row, $user->getMaNhanVien() ?? '');
            $sheet->setCellValue('C' . $row, $user->getName());
            $sheet->setCellValue('D' . $row, self::GENDER_LABELS[$user->getGender()] ?? $user->getGender());
            $sheet->setCellValue('E' . $row, $user->getBirthday() ?? '');
            $sheet->setCellValue('F' . $row, $user->getCmnd() ?? '');
            $sheet->setCellValue('G' . $row, $user->getNgayCapCmnd() ?? '');
            $sheet->setCellValue('H' . $row, $user->getNoiCapCmnd() ?? '');
            $sheet->setCellValue('I' . $row, $user->getNgayVaoLam() ?? '');
            $sheet->setCellValue('J' . $row, self::STATUS_LABELS[$user->getStatus()] ?? '');
            $sheet->setCellValue('K' . $row, $user->getEmail());
            $sheet->setCellValue('L' . $row, $user->getPhone() ?? '');
            $sheet->setCellValue('M' . $row, getProvinceByCode($user->getProvince()) ?? '');
            $sheet->setCellValue('N' . $row, getWardByCode($user->getWard()) ?? '');
            $sheet->setCellValue('O' . $row, $user->getAddress() ?? '');

            $row++;
        }

        // Auto size tất cả các cột
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 3. Trả về StreamedResponse
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="users_export_' . date('Y-m-d') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
