<?php

use App\Class\TranslationHelper;

if (!function_exists('t')) {
    function t(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return TranslationHelper::trans($id, $parameters, $domain, $locale);
    }
}
