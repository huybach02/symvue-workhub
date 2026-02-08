# Hướng Dẫn Sử Dụng BaseExcelTemplateHelper

## 📚 Tổng quan

`BaseExcelTemplateHelper` là một abstract class cung cấp các phương thức tái sử dụng để tạo Excel template với:

- ✅ Dropdown validation
- ✅ Reference sheets (mapping code ↔ text)
- ✅ INDEX-MATCH formulas tự động
- ✅ Cột ẩn chứa giá trị code

## 🎯 Lợi ích

| Trước                       | Sau                             |
| --------------------------- | ------------------------------- |
| ~170 dòng code lặp lại      | ~80 dòng code gọn gàng          |
| Khó bảo trì                 | Dễ bảo trì                      |
| Copy-paste cho mỗi template | Tái sử dụng helper methods      |
| Dễ lỗi khi thay đổi         | Thay đổi 1 chỗ, áp dụng toàn bộ |

---

## 🚀 Cách sử dụng nhanh

### Bước 1: Kế thừa BaseExcelTemplateHelper

```php
<?php

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ProductTemplateImportService extends BaseExcelTemplateHelper
{
    public function generateTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ... setup headers và data mẫu

        // Setup dropdown với 1 dòng code
        $this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);

        return $response;
    }
}
```

### Bước 2: Cấu hình dropdown

```php
$dropdownConfigs = [
    [
        'sourceColumn' => 'D',              // Cột hiển thị dropdown
        'targetColumn' => 'L',              // Cột ẩn chứa code
        'refSheetName' => 'Danh mục',       // Tên reference sheet
        'mappings' => [                     // Code => Text
            'electronics' => 'Điện tử',
            'fashion' => 'Thời trang',
            'food' => 'Thực phẩm',
        ],
        'promptTitle' => 'Chọn danh mục',
        'promptMessage' => 'Vui lòng chọn danh mục sản phẩm',
        'headerName' => 'category_code',    // Header cho cột ẩn
    ],
];

$this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);
```

**Chỉ với config trên, helper tự động:**

1. ✅ Tạo reference sheet "Danh mục" với mapping code ↔ text
2. ✅ Thêm dropdown validation cho cột D (1000 rows)
3. ✅ Tạo cột ẩn L với header "category_code"
4. ✅ Thêm INDEX-MATCH formula cho 1000 rows

---

## 📖 API Reference

### 1. setupMultipleDropdowns() - All-in-One Helper

**Mô tả:** Setup nhiều dropdown cùng lúc (khuyến nghị sử dụng)

**Cú pháp:**

```php
protected function setupMultipleDropdowns(
    Spreadsheet $spreadsheet,
    Worksheet $sheet,
    array $configs
): void
```

**Ví dụ:**

```php
$dropdownConfigs = [
    [
        'sourceColumn' => 'D',
        'targetColumn' => 'L',
        'refSheetName' => 'Trạng thái',
        'mappings' => [
            '0' => 'Không hoạt động',
            '1' => 'Hoạt động',
        ],
        'promptTitle' => 'Chọn trạng thái',
        'promptMessage' => 'Chọn trạng thái sản phẩm',
        'headerName' => 'status_code',
    ],
    // ... thêm dropdown khác
];

$this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);
```

---

### 2. setupDropdownWithMapping() - Single Dropdown

**Mô tả:** Setup 1 dropdown với auto-mapping

**Cú pháp:**

```php
protected function setupDropdownWithMapping(
    Spreadsheet $spreadsheet,
    Worksheet $sheet,
    array $config
): void
```

**Ví dụ:**

```php
$this->setupDropdownWithMapping($spreadsheet, $sheet, [
    'sourceColumn' => 'E',
    'targetColumn' => 'M',
    'refSheetName' => 'Đơn vị',
    'mappings' => [
        'kg' => 'Kilogram',
        'g' => 'Gram',
        'l' => 'Lít',
    ],
    'promptTitle' => 'Chọn đơn vị',
    'promptMessage' => 'Chọn đơn vị tính',
    'headerName' => 'unit_code',
]);
```

---

### 3. createReferenceSheet() - Tạo Reference Sheet

**Mô tả:** Tạo sheet tham chiếu với mapping code ↔ text

**Cú pháp:**

```php
protected function createReferenceSheet(
    Spreadsheet $spreadsheet,
    string $sheetName,
    array $mappings
): void
```

**Ví dụ:**

```php
// Format 1: Array associative
$this->createReferenceSheet($spreadsheet, 'Màu sắc', [
    'red' => 'Đỏ',
    'blue' => 'Xanh dương',
    'green' => 'Xanh lá',
]);

// Format 2: Array of arrays
$this->createReferenceSheet($spreadsheet, 'Size', [
    ['code' => 'S', 'text' => 'Small'],
    ['code' => 'M', 'text' => 'Medium'],
    ['code' => 'L', 'text' => 'Large'],
]);
```

**Kết quả:**
| A (Code) | B (Text) |
|----------|----------|
| red | Đỏ |
| blue | Xanh dương |
| green | Xanh lá |

---

### 4. addDropdownValidation() - Thêm Dropdown Validation

**Mô tả:** Thêm dropdown validation cho 1 cột

**Cú pháp:**

```php
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
): void
```

**Ví dụ:**

```php
$this->addDropdownValidation(
    $sheet,
    'F',                    // Cột F
    'Nhà cung cấp',         // Reference sheet
    2,                      // Bắt đầu từ row 2
    10,                     // Kết thúc ở row 10 (9 options)
    'Chọn nhà cung cấp',
    'Vui lòng chọn nhà cung cấp'
);
```

---

### 5. addIndexMatchFormula() - Thêm INDEX-MATCH Formula

**Mô tả:** Thêm công thức INDEX-MATCH để convert text → code

**Cú pháp:**

```php
protected function addIndexMatchFormula(
    Worksheet $sheet,
    string $sourceColumn,
    string $targetColumn,
    string $refSheetName,
    int $startRow = 2,
    int $endRow = 1000
): void
```

**Ví dụ:**

```php
$this->addIndexMatchFormula(
    $sheet,
    'D',                // Cột nguồn (text)
    'L',                // Cột đích (code)
    'Giới tính',        // Reference sheet
    2,                  // Bắt đầu từ row 2
    500                 // Kết thúc ở row 500
);
```

**Công thức được tạo:**

```excel
=IFERROR(INDEX('Giới tính'!A:A,MATCH(D2,'Giới tính'!B:B,0)),"")
```

---

### 6. createHiddenColumns() - Tạo Cột Ẩn

**Mô tả:** Tạo và ẩn các cột chứa code

**Cú pháp:**

```php
protected function createHiddenColumns(
    Worksheet $sheet,
    array $hiddenColumns
): void
```

**Ví dụ:**

```php
$this->createHiddenColumns($sheet, [
    'L' => 'gender_code',
    'M' => 'status_code',
    'N' => 'category_code',
]);
```

---

## 💡 Ví dụ thực tế: Product Template

```php
<?php

namespace App\Service\Excel\Template;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTemplateImportService extends BaseExcelTemplateHelper
{
    public function generateProductTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products_Import');

        // Headers
        $headers = [
            'Tên sản phẩm (*)',      // A
            'Mã SKU (*)',            // B
            'Giá (*)',               // C
            'Danh mục (*)',          // D - Dropdown
            'Trạng thái (*)',        // E - Dropdown
            'Đơn vị tính (*)',       // F - Dropdown
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Dữ liệu mẫu
        $sheet->fromArray([
            'iPhone 15 Pro',
            'IP15P-256',
            '29990000',
            'Điện tử',              // D
            'Còn hàng',             // E
            'Cái',                  // F
        ], null, 'A2');

        // Setup dropdown với auto-mapping
        $dropdownConfigs = [
            [
                'sourceColumn' => 'D',
                'targetColumn' => 'G',
                'refSheetName' => 'Danh mục',
                'mappings' => [
                    'electronics' => 'Điện tử',
                    'fashion' => 'Thời trang',
                    'food' => 'Thực phẩm',
                    'books' => 'Sách',
                ],
                'promptTitle' => 'Chọn danh mục',
                'promptMessage' => 'Chọn danh mục sản phẩm',
                'headerName' => 'category_code',
            ],
            [
                'sourceColumn' => 'E',
                'targetColumn' => 'H',
                'refSheetName' => 'Trạng thái',
                'mappings' => [
                    'in_stock' => 'Còn hàng',
                    'out_of_stock' => 'Hết hàng',
                    'pre_order' => 'Đặt trước',
                ],
                'promptTitle' => 'Chọn trạng thái',
                'promptMessage' => 'Chọn trạng thái sản phẩm',
                'headerName' => 'status_code',
            ],
            [
                'sourceColumn' => 'F',
                'targetColumn' => 'I',
                'refSheetName' => 'Đơn vị',
                'mappings' => [
                    'piece' => 'Cái',
                    'kg' => 'Kilogram',
                    'box' => 'Hộp',
                ],
                'promptTitle' => 'Chọn đơn vị',
                'promptMessage' => 'Chọn đơn vị tính',
                'headerName' => 'unit_code',
            ],
        ];

        // Tự động setup tất cả dropdown
        $this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Export
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="product_template.xlsx"');

        return $response;
    }
}
```

**Kết quả:**

- ✅ 3 dropdown với auto-mapping
- ✅ 3 reference sheets tự động
- ✅ 3 cột ẩn với INDEX-MATCH formulas
- ✅ Chỉ ~70 dòng code thay vì ~200 dòng

---

## 🔧 Cập nhật Import Service

Khi sử dụng template với cột ẩn, nhớ cập nhật Import Service để đọc từ cột ẩn:

```php
// ProductImportService.php
$data = [
    'name' => $row[0] ?? null,           // A: Tên sản phẩm
    'sku' => $row[1] ?? null,            // B: Mã SKU
    'price' => $row[2] ?? null,          // C: Giá
    'category' => $row[6] ?? null,       // G: category_code (ẨN) ✅
    'status' => $row[7] ?? null,         // H: status_code (ẨN) ✅
    'unit' => $row[8] ?? null,           // I: unit_code (ẨN) ✅
];
```

**Lưu ý:** Đọc từ index 6, 7, 8 (cột G, H, I) chứ không phải 3, 4, 5 (cột D, E, F)

---

## 📊 So sánh Before/After

### Before (Không dùng Helper)

```php
// ~170 dòng code lặp lại
$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle('Giới tính');
$refSheet->setCellValue('A2', 'male');
$refSheet->setCellValue('B2', 'Nam');
// ... 20 dòng nữa

for ($row = 2; $row <= 1000; $row++) {
    $validation = $sheet->getCell('D' . $row)->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    // ... 10 dòng nữa
}

for ($row = 2; $row <= 1000; $row++) {
    $sheet->setCellValue('L' . $row, '=IFERROR(INDEX(...)');
}

// Lặp lại cho mỗi dropdown...
```

### After (Dùng Helper)

```php
// ~10 dòng code gọn gàng
$dropdownConfigs = [
    [
        'sourceColumn' => 'D',
        'targetColumn' => 'L',
        'refSheetName' => 'Giới tính',
        'mappings' => ['male' => 'Nam', 'female' => 'Nữ'],
        'promptTitle' => 'Chọn giới tính',
        'promptMessage' => 'Chọn Nam hoặc Nữ',
        'headerName' => 'gender_code',
    ],
];

$this->setupMultipleDropdowns($spreadsheet, $sheet, $dropdownConfigs);
```

---

## ✅ Best Practices

1. **Sử dụng setupMultipleDropdowns()** cho hầu hết trường hợp
2. **Đặt tên reference sheet rõ ràng** (Giới tính, Trạng thái, Danh mục...)
3. **Đặt tên cột ẩn theo convention** (`{field}_code`)
4. **Nhớ cập nhật Import Service** để đọc từ cột ẩn
5. **Test template** trước khi deploy

---

## 🎯 Kết luận

`BaseExcelTemplateHelper` giúp:

- ✅ Giảm 60% code lặp lại
- ✅ Dễ bảo trì và mở rộng
- ✅ Tái sử dụng cho mọi template
- ✅ Giảm thiểu lỗi khi thay đổi

**Chỉ cần kế thừa và config, mọi thứ tự động!** 🚀
