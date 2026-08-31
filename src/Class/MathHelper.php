<?php

declare(strict_types=1);

namespace App\Class;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Class hỗ trợ các phép toán số học độ chính xác cao bằng brick/math
 */
class MathHelper
{
    /**
     * Trả về số 0 theo đúng scale yêu cầu.
     */
    public static function zero(int $scale = 6): string
    {
        return (string) BigDecimal::zero()->toScale($scale);
    }

    /**
     * Phép nhân: $a * $b (Thay thế bcmul)
     */
    public static function mul(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        return (string) BigDecimal::of((string) $a)
            ->multipliedBy((string) $b)
            ->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Phép cộng: $a + $b (Thay thế bcadd)
     */
    public static function add(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        return (string) BigDecimal::of((string) $a)
            ->plus((string) $b)
            ->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Phép trừ: $a - $b (Thay thế bcsub)
     */
    public static function sub(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        return (string) BigDecimal::of((string) $a)
            ->minus((string) $b)
            ->toScale($scale, RoundingMode::HALF_UP);
    }

    /**
     * Phép chia: $a / $b (Thay thế bcdiv)
     */
    public static function div(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        $bDecimal = BigDecimal::of((string) $b);
        if ($bDecimal->isZero()) {
            return "0";
        }

        return (string) BigDecimal::of((string) $a)
            ->dividedBy($bDecimal, $scale, RoundingMode::HALF_UP);
    }

    /**
     * So sánh 2 số (Thay thế bccomp)
     * Trả về: -1 nếu $a < $b, 0 nếu $a == $b, 1 nếu $a > $b
     */
    public static function comp(string|int|float $a, string|int|float $b, int $scale = 6): int
    {
        $bigA = BigDecimal::of((string) $a)->toScale($scale, RoundingMode::HALF_UP);
        $bigB = BigDecimal::of((string) $b)->toScale($scale, RoundingMode::HALF_UP);

        return $bigA->compareTo($bigB);
    }
}
