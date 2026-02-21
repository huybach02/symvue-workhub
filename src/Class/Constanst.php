<?php

namespace App\Class;

final class Constanst
{
    const THOI_GIAN_LAM_VIEC = [
        "Thứ 2" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 3" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 4" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 5" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 6" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 7" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Chủ nhật" => [
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
    ];

    const CHO_PHEP_NGOAI_GIO = [
        "CHO_PHEP" => 1,
        "KHONG_CHO_PHEP" => 0,
    ];

    const CHECK_THOI_GIAN_LAM_VIEC = [
        "KICH_HOAT" => 1,
        "KHONG_KICH_HOAT" => 0,
    ];

    const CHECK_XAC_THUC_OTP = [
        "KICH_HOAT" => 1,
        "KHONG_KICH_HOAT" => 0,
    ];

    const CONVERT_DATE_TIME = [
        "Monday" => "Thứ 2",
        "Tuesday" => "Thứ 3",
        "Wednesday" => "Thứ 4",
        "Thursday" => "Thứ 5",
        "Friday" => "Thứ 6",
        "Saturday" => "Thứ 7",
        "Sunday" => "Chủ nhật",
    ];

    /**
     * Map URL path prefix → tên module trong hệ thống permission.
     * Key: prefix của URL route (phải khớp với đầu path, dùng str_starts_with)
     * Value: tên module tương ứng trong UserPermission->phanQuyen[]['name']
     * Cần cập nhật khi thêm module mới vào hệ thống.
     */
    const ROUTE_PERMISSION_MAP = [
        '/cau-hinh-chung'       => 'cau-hinh-chung',
        '/thoi-gian-lam-viec'   => 'thoi-gian-lam-viec',
        '/user'                 => 'nguoi-dung',
        '/bo-phan'              => 'bo-phan',
    ];

    /**
     * Map HTTP method → tên action trong permission.
     * GET /list       → index
     * GET /{id}       → show  (được xác định khi path có segment là số)
     * POST            → create
     * PUT / PATCH     → edit
     * DELETE          → delete
     */
    const METHOD_ACTION_MAP = [
        'POST'   => 'create',
        'PUT'    => 'edit',
        'PATCH'  => 'edit',
        'DELETE' => 'delete',
    ];
}
