<?php

declare(strict_types=1);

namespace App\Service\Excel\Export;

use App\Entity\Provider;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderExportService
{
    private const STATUS_LABELS = [
        1 => 'Hoạt động',
        0 => 'Không hoạt động',
    ];

    public function export(array $providers): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách nhà cung cấp');

        $headers = [
            'A' => 'STT',
            'B' => 'Mã nhà cung cấp',
            'C' => 'Tên nhà cung cấp',
            'D' => 'Số điện thoại',
            'E' => 'Email',
            'F' => 'Địa chỉ',
            'G' => 'Mã số thuế',
            'H' => 'Tên ngân hàng',
            'I' => 'Số tài khoản ngân hàng',
            'J' => 'Trạng thái',
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

        $row = 2;
        foreach ($providers as $provider) {
            $sheet->setCellValue('A' . $row, $row - 1);
            $sheet->setCellValue('B' . $row, $provider->getCode());
            $sheet->setCellValue('C' . $row, $provider->getName());
            $sheet->setCellValue('D' . $row, $provider->getPhone() ?? '');
            $sheet->setCellValue('E' . $row, $provider->getEmail() ?? '');
            $sheet->setCellValue('F' . $row, $provider->getAddress() ?? '');
            $sheet->setCellValue('G' . $row, $provider->getTaxNumber() ?? '');
            $sheet->setCellValue('H' . $row, $provider->getBankName() ?? '');
            $sheet->setCellValue('I' . $row, $provider->getBankNumber() ?? '');
            $sheet->setCellValue('J' . $row, self::STATUS_LABELS[$provider->getStatus()] ?? '');
            $sheet->setCellValue('K' . $row, $provider->getNote() ?? '');

            $row++;
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="providers_export_' . date('Y-m-d') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
