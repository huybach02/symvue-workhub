<?php

declare(strict_types=1);

namespace App\Class\Request;

final class RequestConstant
{
    // Request types
    public const TYPE_LEAVE = 'leave';
    public const TYPE_STOCK_IN = 'stock:stock-in'; // Nhập kho
    public const TYPE_STOCK_OUT = 'stock:stock-out'; // Xuất kho
    public const TYPE_PRODUCTION = 'stock:production'; // Đề xuất sản xuất

    // Request status
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';

    // Request source
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SYSTEM = 'system';

    // Request event types
    public const EVENT_CREATED = 'created';
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_EDITED = 'edited';
    public const EVENT_RESUBMITTED = 'resubmitted';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_EFFECT_APPLIED = 'effect_applied';
    public const EVENT_DELETED = 'deleted';

    public static function allTypes(): array
    {
        return [
            self::TYPE_LEAVE,
            self::TYPE_STOCK_IN,
            self::TYPE_PRODUCTION,
        ];
    }

    public static function typeOptions(): array
    {
        return [
            [
                'code' => self::TYPE_LEAVE,
                'title' => 'Nghỉ phép',
                'description' => 'Tạo và gửi đề xuất nghỉ phép cho quản lý trực tiếp duyệt',
                'icon' => 'mdi-calendar-remove',
                'color' => 'warning',
            ],
            [
                'code' => self::TYPE_STOCK_IN,
                'title' => 'Nhập kho',
                'description' => 'Tạo và gửi đề xuất nhập kho',
                'icon' => 'mdi-inbox-arrow-down',
                'color' => 'warning',
            ],
            [
                'code' => self::TYPE_PRODUCTION,
                'title' => 'Sản xuất',
                'description' => 'Tạo và gửi đề xuất sản xuất thành phẩm nội bộ',
                'icon' => 'mdi-factory',
                'color' => 'info',
            ],
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     title: string,
     *     description: string,
     *     icon: string,
     *     color: string
     * }|null
     */
    public static function typeOption(string $type): ?array
    {
        foreach (self::typeOptions() as $item) {
            if ($item['code'] === $type) {
                return $item;
            }
        }

        return null;
    }

    public static function allStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_REJECTED,
            self::STATUS_APPROVED,
            self::STATUS_CANCELLED,
        ];
    }
}
