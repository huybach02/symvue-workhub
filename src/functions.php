<?php

use App\Class\TranslationHelper;

if (!function_exists('t')) {
    function t(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return TranslationHelper::trans($id, $parameters, $domain, $locale);
    }
}

// Hàm format từ số giây sang chuỗi string (nếu < 60 giây thì format giây, nếu >= 60 giây thì format phút giây)
if (!function_exists('formatSeconds')) {
    function formatSeconds($seconds)
    {
        $seconds = (int) $seconds;

        if ($seconds < 60) {
            return $seconds . ' giây';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return $minutes . ' phút ' . $seconds . ' giây';
    }
}

// Hàm tạo chuỗi string ngẫu nhiên
if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

if (!function_exists('validateFilterParams')) {
    function validateFilterParams(array &$params)
    {
        // Đảm bảo các tham số có giá trị mặc định
        $params['page'] = isset($params['page']) ? (int) $params['page'] : 1;
        $params['limit'] = isset($params['limit']) ? (int) $params['limit'] : 10;
        $params['sort_direction'] = $params['sort_direction'] ?? 'desc';
        $params['sort_column'] = $params['sort_column'] ?? 'createdAt';

        // Validate filter parameters
        if (isset($params['f']) && is_array($params['f'])) {
            foreach ($params['f'] as $index => $filter) {
                if (!isset($filter['field']) || !isset($filter['operator']) || !isset($filter['value'])) {
                    unset($params['f'][$index]);
                }
            }
            // Reindex array để đảm bảo index liên tục
            $params['f'] = array_values($params['f']);
        }

        return $params;
    }
}

if (!function_exists('getProvinceByCode')) {
    function getProvinceByCode(?string $provinceCode): string
    {
        if (!$provinceCode) {
            return '';
        }

        $projectDir = dirname(__DIR__);
        $filePath = $projectDir . '/public/province.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File province.json không tồn tại');
        }
        $content = file_get_contents($filePath);
        $items = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File province.json không đúng định dạng JSON');
        }

        $filtered = array_filter($items, fn($item) => $item['code'] == $provinceCode);

        return array_values($filtered)[0]['name'] ?? '';
    }
}

if (!function_exists('getWardByCode')) {
    function getWardByCode(?string $wardCode): string
    {
        if (!$wardCode) {
            return '';
        }

        $projectDir = dirname(__DIR__);
        $filePath = $projectDir . '/public/ward.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File ward.json không tồn tại');
        }
        $content = file_get_contents($filePath);
        $items = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File ward.json không đúng định dạng JSON');
        }

        $filtered = array_filter($items, fn($item) => $item['code'] == $wardCode);

        return array_values($filtered)[0]['name'] ?? '';
    }
}

/**
 * Convert tên cột Excel (A, B, C, AA, AB...) sang index số (0, 1, 2...)
 * 
 * @param string $column Tên cột Excel (VD: "A", "B", "AA", "AB")
 * @return int Index số tương ứng (0-based)
 * 
 * @example
 * excelColumnToIndex("A") => 0
 * excelColumnToIndex("B") => 1
 * excelColumnToIndex("Z") => 25
 * excelColumnToIndex("AA") => 26
 * excelColumnToIndex("AB") => 27
 */
if (!function_exists('excelColumnToIndex')) {
    function excelColumnToIndex(string $column): int
    {
        $column = strtoupper($column);
        $length = strlen($column);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }

        return $index - 1; // Trả về 0-based index
    }
}

/**
 * Helper function để lấy giá trị từ row Excel theo tên cột
 * 
 * @param array $row Mảng dữ liệu từ Excel row
 * @param string $column Tên cột Excel (VD: "A", "B", "AA")
 * @param mixed $default Giá trị mặc định nếu không tồn tại
 * @return mixed Giá trị tại cột đó hoặc giá trị mặc định
 * 
 * @example
 * excelGetValue($row, "A") => Lấy giá trị cột A
 * excelGetValue($row, "B", "default") => Lấy giá trị cột B, nếu null thì trả về "default"
 */
if (!function_exists('excelGetValue')) {
    function excelGetValue(array $row, string $column, mixed $default = null): mixed
    {
        $index = excelColumnToIndex($column);
        return $row[$index] ?? $default;
    }
}

if (!function_exists('convertMethod')) {
    function convertMethod(string $path, string $method): string
    {
        $pathArr = explode("/", $path);

        if (count($pathArr) === 1) {
            switch ($method) {
                case "GET":
                    return "index";
                case "POST":
                    return "create";
            }
        }

        if (count($pathArr) > 1) {
            switch ($method) {
                case "GET":
                    return "show";
                case "PUT":
                case "PATCH":
                    return "edit";
                case "DELETE":
                    return "delete";
            }
        }

        return "index";
    }
}
