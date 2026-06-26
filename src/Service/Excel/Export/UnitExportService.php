<?php

declare(strict_types=1);

namespace App\Service\Excel\Export;

use App\Entity\Unit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitExportService
{
    private const STATUS_LABELS = [
        1 => 'Hoạt động',
        0 => 'Không hoạt động',
    ];

    public function export(array $units): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh sách đơn vị tính');

        $headers = [
            'A' => 'STT',
            'B' => 'Mã đơn vị tính',
            'C' => 'Tên đơn vị tính',
            'D' => 'Ký hiệu',
            'E' => 'Trạng thái',
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
        foreach ($units as $unit) {
            $sheet->setCellValue('A' . $row, $row - 1);
            $sheet->setCellValue('B' . $row, $unit->getCode());
            $sheet->setCellValue('C' . $row, $unit->getName());
            $sheet->setCellValue('D' . $row, $unit->getSymbol() ?? '');
            $sheet->setCellValue('E' . $row, self::STATUS_LABELS[$unit->getStatus()] ?? '');

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
        $response->headers->set('Content-Disposition', 'attachment;filename="units_export_' . date('Y-m-d') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
