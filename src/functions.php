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
