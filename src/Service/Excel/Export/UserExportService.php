<?php

namespace App\Service\Excel\Export;

use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportService
{
    public function exportUsers(array $users): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Tạo Header
        $headers = ['STT', 'Tên', 'Email', 'Số điện thoại', 'Giới tính', 'Ngày sinh', 'Tỉnh/Thành phố', 'Quận/Huyện', 'Địa chỉ', 'Hình thức làm việc', 'Cho phép ngoài giờ', 'Trạng thái'];
        $columnLetter = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($columnLetter . '1', $header);
            $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true); // In đậm header
            $columnLetter++;
        }

        // 2. Đổ dữ liệu
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $row - 1);
            $sheet->setCellValue('B' . $row, $user->getName());
            $sheet->setCellValue('C' . $row, $user->getEmail());
            $sheet->setCellValue('D' . $row, $user->getPhone());
            $sheet->setCellValue('E' . $row, $user->getGender() == 'male' ? 'Nam' : 'Nữ');
            $sheet->setCellValue('F' . $row, $user->getBirthday());
            $sheet->setCellValue('G' . $row, getProvinceByCode($user->getProvince()) ?? '');
            $sheet->setCellValue('H' . $row, getWardByCode($user->getWard()) ?? '');
            $sheet->setCellValue('I' . $row, $user->getAddress());
            $sheet->setCellValue('J' . $row, $user->getHinhThucLamViec() == 1 ? 'Cố định' : 'Theo ca');
            $sheet->setCellValue('K' . $row, $user->getIsNgoaiGio() == 0 ? 'Không cho phép' : 'Cho phép');
            $sheet->setCellValue('L' . $row, $user->getStatus() ? 'Hoạt động' : 'Không hoạt động');

            $row++;
        }

        // Auto size các cột cho đẹp
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 3. Trả về StreamedResponse
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        // Thiết lập Header cho response
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="users_export_' . date('Y-m-d') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
