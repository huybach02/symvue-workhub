<?php

namespace App\Class;

use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationHelper
{
    private static ?TranslatorInterface $translator = null;

    public static function setTranslator(TranslatorInterface $translator): void
    {
        self::$translator = $translator;
    }

    public static function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if (self::$translator === null) {
            return $id; // Fallback nếu chưa khởi tạo
        }
        return self::$translator->trans($id, $parameters, $domain, $locale);
    }
}
