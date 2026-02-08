<?php

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Base Helper cho việc tạo Excel Template với dropdown và auto-mapping
 * Cung cấp các phương thức tái sử dụng cho mọi template Excel
 */
abstract class BaseExcelTemplateHelper
{
    /**
     * Tạo reference sheet với mapping code <-> text
     * 
     * @param Spreadsheet $spreadsheet
     * @param string $sheetName Tên sheet tham chiếu
     * @param array $mappings Mảng mapping ['code' => 'text'] hoặc [['code' => 'value', 'text' => 'label']]
     * @return void
     */
    protected function createReferenceSheet(Spreadsheet $spreadsheet, string $sheetName, array $mappings): void
    {
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle($sheetName);

        $rowIndex = 2; // Bắt đầu từ row 2 (row 1 để trống hoặc header)

        foreach ($mappings as $key => $value) {
            // Hỗ trợ 2 format: ['code' => 'text'] hoặc [['code' => ..., 'text' => ...]]
            if (is_array($value)) {
                $code = $value['code'] ?? $key;
                $text = $value['text'] ?? $value;
            } else {
                $code = $key;
                $text = $value;
            }

            $refSheet->setCellValue('A' . $rowIndex, $code);
            $refSheet->setCellValue('B' . $rowIndex, $text);
            $rowIndex++;
        }
    }

    /**
     * Tạo dropdown validation cho một cột
     * 
     * @param Worksheet $sheet Sheet chính
     * @param string $column Cột cần thêm dropdown (vd: 'D', 'I')
     * @param string $refSheetName Tên reference sheet
     * @param int $refStartRow Row bắt đầu trong reference sheet (mặc định: 2)
     * @param int $refEndRow Row kết thúc trong reference sheet
     * @param string $promptTitle Tiêu đề prompt
     * @param string $promptMessage Nội dung prompt
     * @param int $startRow Row bắt đầu áp dụng validation (mặc định: 2)
     * @param int $endRow Row kết thúc áp dụng validation (mặc định: 1000)
     * @return void
     */
    protected function addDropdownValidation(
        Worksheet $sheet,
        string $column,
        string $refSheetName,
        int $refStartRow,
        int $refEndRow,
        string $promptTitle,
        string $promptMessage,
        int $startRow = 2,
        int $endRow = 1000
    ): void {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell($column . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Giá trị không hợp lệ');
            $validation->setError('Vui lòng chọn giá trị từ danh sách.');
            $validation->setPromptTitle($promptTitle);
            $validation->setPrompt($promptMessage);
            $validation->setFormula1("'{$refSheetName}'!\$B\${$refStartRow}:\$B\${$refEndRow}");
        }
    }

    /**
     * Thêm INDEX-MATCH formula để tự động convert text sang code
     * 
     * @param Worksheet $sheet Sheet chính
     * @param string $sourceColumn Cột chứa text (vd: 'D')
     * @param string $targetColumn Cột ẩn chứa code (vd: 'L')
     * @param string $refSheetName Tên reference sheet
     * @param int $startRow Row bắt đầu (mặc định: 2)
     * @param int $endRow Row kết thúc (mặc định: 1000)
     * @return void
     */
    protected function addIndexMatchFormula(
        Worksheet $sheet,
        string $sourceColumn,
        string $targetColumn,
        string $refSheetName,
        int $startRow = 2,
        int $endRow = 1000
    ): void {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $formula = "=IFERROR(INDEX('{$refSheetName}'!A:A,MATCH({$sourceColumn}{$row},'{$refSheetName}'!B:B,0)),\"\")";
            $sheet->setCellValue($targetColumn . $row, $formula);
        }
    }

    /**
     * Tạo cột ẩn và ẩn chúng
     * 
     * @param Worksheet $sheet
     * @param array $hiddenColumns Mảng các cột cần ẩn với header ['L' => 'gender_code', 'M' => 'status_code']
     * @return void
     */
    protected function createHiddenColumns(Worksheet $sheet, array $hiddenColumns): void
    {
        foreach ($hiddenColumns as $column => $headerName) {
            $sheet->setCellValue($column . '1', $headerName);
            $sheet->getColumnDimension($column)->setVisible(false);
        }
    }

    /**
     * Setup dropdown với auto-mapping (all-in-one helper)
     * Tự động tạo reference sheet, dropdown validation, và INDEX-MATCH formula
     * 
     * @param Spreadsheet $spreadsheet
     * @param Worksheet $sheet Sheet chính
     * @param array $config Cấu hình dropdown
     *   [
     *     'sourceColumn' => 'D',              // Cột hiển thị dropdown
     *     'targetColumn' => 'L',              // Cột ẩn chứa code
     *     'refSheetName' => 'Giới tính',      // Tên reference sheet
     *     'mappings' => ['male' => 'Nam', 'female' => 'Nữ'],  // Code => Text
     *     'promptTitle' => 'Chọn giới tính',
     *     'promptMessage' => 'Chọn Nam hoặc Nữ',
     *     'headerName' => 'gender_code'       // Tên header cho cột ẩn
     *   ]
     * @return void
     */
    protected function setupDropdownWithMapping(Spreadsheet $spreadsheet, Worksheet $sheet, array $config): void
    {
        // 1. Tạo reference sheet
        $this->createReferenceSheet($spreadsheet, $config['refSheetName'], $config['mappings']);

        // 2. Tính số lượng options
        $optionCount = count($config['mappings']);
        $refStartRow = 2;
        $refEndRow = $refStartRow + $optionCount - 1;

        // 3. Thêm dropdown validation
        $this->addDropdownValidation(
            $sheet,
            $config['sourceColumn'],
            $config['refSheetName'],
            $refStartRow,
            $refEndRow,
            $config['promptTitle'],
            $config['promptMessage']
        );

        // 4. Tạo cột ẩn
        $this->createHiddenColumns($sheet, [$config['targetColumn'] => $config['headerName']]);

        // 5. Thêm INDEX-MATCH formula
        $this->addIndexMatchFormula(
            $sheet,
            $config['sourceColumn'],
            $config['targetColumn'],
            $config['refSheetName']
        );
    }

    /**
     * Setup nhiều dropdown cùng lúc
     * 
     * @param Spreadsheet $spreadsheet
     * @param Worksheet $sheet
     * @param array $configs Mảng các config dropdown
     * @return void
     */
    protected function setupMultipleDropdowns(Spreadsheet $spreadsheet, Worksheet $sheet, array $configs): void
    {
        foreach ($configs as $config) {
            $this->setupDropdownWithMapping($spreadsheet, $sheet, $config);
        }
    }
}
