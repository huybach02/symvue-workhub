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
