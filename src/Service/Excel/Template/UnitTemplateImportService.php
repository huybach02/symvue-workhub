<?php

declare(strict_types=1);

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitTemplateImportService extends BaseExcelTemplateHelper
{
    public function generateUnitTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Units_Import');

        $headers = [
            'A' => 'STT',
            'B' => 'Mã đơn vị tính (*)',
            'C' => 'Tên đơn vị tính (*)',
            'D' => 'Ký hiệu',
            'E' => 'Trạng thái (*)',
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
            '',         // A: STT
            'KG',       // B: Mã đơn vị tính
            'Kilôgam',  // C: Tên đơn vị tính
            'kg',       // D: Ký hiệu
            'Hoạt động',// E: Trạng thái
        ], null, 'A2');

        $dropdownConfigs = [
            [
                'sourceColumn' => 'E',       // Cột hiển thị dropdown
                'targetColumn' => 'F',       // Cột ẩn chứa giá trị code
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
        $response->headers->set('Content-Disposition', 'attachment;filename="unit_template.xlsx"');

        return $response;
    }
}
