<?php

declare(strict_types=1);

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderTemplateImportService extends BaseExcelTemplateHelper
{
    public function generateProviderTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Providers_Import');

        $headers = [
            'A' => 'STT',
            'B' => 'Mã nhà cung cấp (*)',
            'C' => 'Tên nhà cung cấp (*)',
            'D' => 'Số điện thoại',
            'E' => 'Email',
            'F' => 'Địa chỉ',
            'G' => 'Mã số thuế',
            'H' => 'Tên ngân hàng',
            'I' => 'Số tài khoản ngân hàng',
            'J' => 'Trạng thái (*)',
            'K' => 'Ghi chú',
        ];

        foreach ($headers as $col => $label) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DBEAFE');
        }

        // Dữ liệu mẫu (Row 2)
        $sheet->fromArray([
            '',                                         // A: STT
            'VINAMILK',                                 // B: Mã nhà cung cấp
            'Công ty Cổ phần Sữa Việt Nam (Vinamilk)',  // C: Tên nhà cung cấp
            '02854155555',                              // D: Số điện thoại
            'vinamilk@vinamilk.com.vn',                 // E: Email
            '10 Tân Trào, Phường Tân Phú, Quận 7, TP. Hồ Chí Minh', // F: Địa chỉ
            '0300588569',                               // G: Mã số thuế
            'Vietcombank',                              // H: Tên ngân hàng
            '0071001234567',                            // I: Số tài khoản ngân hàng
            'Hoạt động',                                // J: Trạng thái (dropdown)
            'Nhà cung cấp sữa mẫu.',                    // K: Ghi chú
        ], null, 'A2');

        $dropdownConfigs = [
            [
                'sourceColumn' => 'J',       // Cột hiển thị dropdown
                'targetColumn' => 'L',       // Cột ẩn chứa giá trị code
                'refSheetName' => 'Trạng thái',
                'mappings' => [
                    '1' => 'Hoạt động',
                    '0' => 'Không hoạt động',
                ],
                'promptTitle'   => 'Chọn trạng thái',
                'promptMessage' => 'Chọn Hoạt động hoặc Không hoạt động',
                'headerName'    => 'status_code',
            ],
        ];

        $this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="provider_template.xlsx"');

        return $response;
    }
}
