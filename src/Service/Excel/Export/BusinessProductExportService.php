<?php

declare(strict_types=1);

namespace App\Service\Excel\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessProductExportService
{
    private const STATUS_LABELS = [
        1 => 'Hoạt động',
        0 => 'Không hoạt động',
    ];

    private const HEADER_STYLE = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    public function export(array $data): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // ================================================================
        // Sheet 1: Tổng quan Sản phẩm kinh doanh
        // ================================================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Sản phẩm');

        $headers1 = [
            'A' => 'STT',
            'B' => 'Mã SP',
            'C' => 'Tên sản phẩm',
            'D' => 'Danh mục',
            'E' => 'Lợi nhuận mục tiêu (%)',
            'F' => 'Số biến thể',
            'G' => 'Mô tả',
            'H' => 'Ghi chú',
            'I' => 'Trạng thái',
            'J' => 'Ngày tạo',
            'K' => 'Ngày cập nhật',
        ];

        $row = 1;
        foreach ($headers1 as $col => $label) {
            $cell = $col . $row;
            $sheet1->setCellValue($cell, $label);
            $sheet1->getStyle($cell)->applyFromArray(self::HEADER_STYLE);
        }

        $row = 2;
        foreach ($data as $i => $item) {
            $p = $item['product'] ?? [];
            $sheet1->setCellValue('A' . $row, $row - 1);
            $sheet1->setCellValue('B' . $row, $p['code'] ?? '');
            $sheet1->setCellValue('C' . $row, $p['name'] ?? '');
            $sheet1->setCellValue('D' . $row, $p['category']['name'] ?? '');
            $sheet1->setCellValue('E' . $row, $p['targetProfitMargin'] ?? '');
            $sheet1->setCellValue('F' . $row, count($item['variants'] ?? []));
            $sheet1->setCellValue('G' . $row, strip_tags($p['description'] ?? ''));
            $sheet1->setCellValue('H' . $row, $p['notes'] ?? '');
            $sheet1->setCellValue('I' . $row, self::STATUS_LABELS[$p['status']] ?? '');
            $sheet1->setCellValue('J' . $row, $p['createdAt'] ?? '');
            $sheet1->setCellValue('K' . $row, $p['updatedAt'] ?? '');
            $row++;
        }

        foreach (array_keys($headers1) as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // ================================================================
        // Sheet 2: Biến thể
        // ================================================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Biến thể');

        $headers2 = [
            'A' => 'STT',
            'B' => 'Mã SP',
            'C' => 'Tên SP',
            'D' => 'Mã biến thể',
            'E' => 'Tên biến thể',
            'F' => 'Đơn vị tính',
            'G' => 'Mã vạch',
            'H' => 'Mặc định',
            'I' => 'Giá bán',
            'J' => 'Giá gợi ý',
            'K' => 'Giá vốn',
            'L' => 'Lợi nhuận mục tiêu (%)',
            'M' => 'Tiền tệ',
            'N' => 'Trạng thái',
        ];

        $row = 1;
        foreach ($headers2 as $col => $label) {
            $cell = $col . $row;
            $sheet2->setCellValue($cell, $label);
            $sheet2->getStyle($cell)->applyFromArray(self::HEADER_STYLE);
        }

        $row = 2;
        $stt = 1;
        foreach ($data as $item) {
            $p = $item['product'] ?? [];
            foreach ($item['variants'] ?? [] as $variant) {
                $sheet2->setCellValue('A' . $row, $stt++);
                $sheet2->setCellValue('B' . $row, $p['code'] ?? '');
                $sheet2->setCellValue('C' . $row, $p['name'] ?? '');
                $sheet2->setCellValue('D' . $row, $variant['code'] ?? '');
                $sheet2->setCellValue('E' . $row, $variant['name'] ?? '');
                $sheet2->setCellValue('F' . $row, $variant['unit'] ?? '');
                $sheet2->setCellValue('G' . $row, $variant['barcode'] ?? '');
                $sheet2->setCellValue('H' . $row, ($variant['isDefault'] ?? false) ? '✓' : '');
                $sheet2->setCellValue('I' . $row, $variant['sellingPrice'] ?? '');
                $sheet2->setCellValue('J' . $row, $variant['suggestedPrice'] ?? '');
                $sheet2->setCellValue('K' . $row, $variant['costSnapshot'] ?? '');
                $sheet2->setCellValue('L' . $row, $p['targetProfitMargin'] ?? '');
                $sheet2->setCellValue('M' . $row, $variant['currency'] ?? 'VND');
                $sheet2->setCellValue('N' . $row, self::STATUS_LABELS[$variant['status']] ?? '');
                $row++;
            }
        }

        foreach (array_keys($headers2) as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ================================================================
        // Sheet 3: Công thức (Recipe Items)
        // ================================================================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Công thức');

        $headers3 = [
            'A' => 'STT',
            'B' => 'Mã SP',
            'C' => 'Tên SP',
            'D' => 'Biến thể',
            'E' => 'Mã biến thể',
            'F' => 'Version',
            'G' => 'Tổng giá vốn',
            'H' => 'Giá gợi ý',
            'I' => 'Mã TP',
            'J' => 'Tên thành phẩm',
            'K' => 'Định lượng',
            'L' => 'Đơn vị',
            'M' => 'Hao hụt (%)',
            'N' => 'ĐL quy đổi chuẩn',
            'O' => 'Giá vốn dòng',
        ];

        $row = 1;
        foreach ($headers3 as $col => $label) {
            $cell = $col . $row;
            $sheet3->setCellValue($cell, $label);
            $sheet3->getStyle($cell)->applyFromArray(self::HEADER_STYLE);
        }

        $row = 2;
        $stt = 1;
        foreach ($data as $item) {
            $p = $item['product'] ?? [];
            foreach ($item['variants'] ?? [] as $variant) {
                $items = $variant['recipeItems'] ?? [];
                if (empty($items)) {
                    // Vẫn ghi dòng biến thể không có công thức
                    $sheet3->setCellValue('A' . $row, $stt++);
                    $sheet3->setCellValue('B' . $row, $p['code'] ?? '');
                    $sheet3->setCellValue('C' . $row, $p['name'] ?? '');
                    $sheet3->setCellValue('D' . $row, $variant['name'] ?? '');
                    $sheet3->setCellValue('E' . $row, $variant['code'] ?? '');
                    $row++;
                    continue;
                }

                foreach ($items as $recipeItem) {
                    $sheet3->setCellValue('A' . $row, $stt++);
                    $sheet3->setCellValue('B' . $row, $p['code'] ?? '');
                    $sheet3->setCellValue('C' . $row, $p['name'] ?? '');
                    $sheet3->setCellValue('D' . $row, $variant['name'] ?? '');
                    $sheet3->setCellValue('E' . $row, $variant['code'] ?? '');
                    $sheet3->setCellValue('F' . $row, $variant['recipeVersion'] ?? '');
                    $sheet3->setCellValue('G' . $row, $variant['recipeTotalCost'] ?? '');
                    $sheet3->setCellValue('H' . $row, $variant['suggestedPrice'] ?? '');
                    $sheet3->setCellValue('I' . $row, $recipeItem['code'] ?? '');
                    $sheet3->setCellValue('J' . $row, $recipeItem['name'] ?? '');
                    $sheet3->setCellValue('K' . $row, $recipeItem['quantity'] ?? '');
                    $sheet3->setCellValue('L' . $row, $recipeItem['unit'] ?? '');
                    $sheet3->setCellValue('M' . $row, $recipeItem['wasteRate'] ?? '');
                    $sheet3->setCellValue('N' . $row, $recipeItem['baseQuantity'] ?? '');
                    $sheet3->setCellValue('O' . $row, $recipeItem['costSnapshot'] ?? '');
                    $row++;
                }
            }
        }

        foreach (array_keys($headers3) as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // ================================================================
        // Response
        // ================================================================
        $sheet1->setSelectedCell('A1');

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="business_products_export_' . date('Y-m-d') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
