<?php

declare(strict_types=1);

use App\Entity\Request;

return [
    [
        'pattern' => '#^stock-transfer(?:/context)?$#',
        'methods' => ['GET', 'POST'],
        'action' => 'create',
        'permission' => [
            'type' => 'static',
            'name' => 'requests:stock:stock-transfer',
        ],
    ],
    // Vi du 1 - hierarchical:
    // Khi permission cha/con dung chung 1 API, can suy ra permission con tu context.
    // GET /api/requests?type=leave
    // => action = index
    // => permission name = requests:leave
    //
    // POST /api/requests voi body {"type":"stock:stock-in"}
    // => action = create
    // => permission name = requests:stock:stock-in
    //
    // [
    //     'pattern' => '#^requests$#',
    //     'methods' => ['GET'],
    //     'action' => 'index',
    //     'permission' => [
    //         'type' => 'hierarchical',
    //         'base' => 'requests',
    //         'source' => 'query',
    //         'key' => 'type',
    //     ],
    // ],
    // [
    //     'pattern' => '#^requests$#',
    //     'methods' => ['POST'],
    //     'action' => 'create',
    //     'permission' => [
    //         'type' => 'hierarchical',
    //         'base' => 'requests',
    //         'source' => 'body',
    //         'key' => 'type',
    //     ],
    // ],
    //
    // Vi du 2 - static:
    // Khi permission cha/con da tach thanh API rieng, co the map thang route -> permission.
    // GET /api/leave-requests
    // => action = index
    // => permission name = requests:leave
    //
    // [
    //     'pattern' => '#^leave-requests$#',
    //     'methods' => ['GET'],
    //     'action' => 'index',
    //     'permission' => [
    //         'type' => 'static',
    //         'name' => 'requests:leave',
    //     ],
    // ],
    [
        // GET /api/requests?type=leave
        'pattern' => '#^requests$#',
        'methods' => ['GET'],
        'action' => 'index',
        'permission' => [
            'type' => 'hierarchical', // Permission phân cấp, cần ghép base + suffix để ra name thực tế
            'base' => 'requests', // Module cha dùng làm prefix của permission name
            'source' => 'query', // Nguồn lấy suffix: query string
            'key' => 'type', // Tên param trong query chứa suffix permission, ví dụ leave
        ],
    ],
    [
        // POST /api/requests với body {"type":"stock:stock-in"}
        'pattern' => '#^requests$#',
        'methods' => ['POST'],
        'action' => 'create',
        'permission' => [
            'type' => 'hierarchical', // Permission phân cấp, cần ghép base + suffix để ra name thực tế
            'base' => 'requests', // Module cha dùng làm prefix của permission name
            'source' => 'body', // Nguồn lấy suffix: request body
            'key' => 'type', // Tên field trong body chứa suffix permission, ví dụ stock:stock-in
        ],
    ],
    [
        // GET /api/requests/15, PUT /api/requests/15, DELETE /api/requests/15
        // Vi du:
        // - GET /api/requests/15
        // - Request#15 trong DB co type = leave
        // => action = show
        // => permission name = requests:leave
        //
        // Khong dung query/body o route {id} vi type phai lay tu entity thuc te trong DB.
        // Neu client gui ?type=stock:stock-in nhung Request#15 thuc te la leave
        // thi van phai check requests:leave thay vi requests:stock:stock-in.
        'pattern' => '#^requests/\d+$#',
        'methods' => ['GET', 'PUT', 'DELETE'],
        'action_map' => [
            'GET' => 'show',
            'PUT' => 'edit',
            'DELETE' => 'delete',
        ],
        'permission' => [
            'type' => 'hierarchical', // Permission phân cấp, cần ghép base + suffix để ra name thực tế
            'base' => 'requests', // Module cha dùng làm prefix của permission name
            'source' => 'entity', // Nguồn lấy suffix: load entity từ DB
            'entity' => Request::class, // Entity cần load để đọc ra giá trị suffix
            'field' => 'type', // Field/getter trên entity trả về suffix permission
            'id_param' => 'id', // Tên route param chứa id của entity cần load
        ],
    ],
    [
        // PATCH /api/requests/15/approve, /reject, /cancel
        // Dung action_from_match vi approve/reject/cancel cung dung chung method PATCH,
        // nen action_map khong du de phan biet action neu gom chung trong 1 rule.
        'pattern' => '#^requests/\d+/(approve|reject|cancel)$#',
        'methods' => ['PATCH'],
        'action_from_match' => 1,
        'permission' => [
            'type' => 'hierarchical', // Permission phân cấp, cần ghép base + suffix để ra name thực tế
            'base' => 'requests', // Module cha dùng làm prefix của permission name
            'source' => 'entity', // Nguồn lấy suffix: load entity từ DB
            'entity' => Request::class, // Entity cần load để đọc ra giá trị suffix
            'field' => 'type', // Field/getter trên entity trả về suffix permission
            'id_param' => 'id', // Tên route param chứa id của entity cần load
        ],
    ],
    [
        // GET /api/requests/15/timeline
        'pattern' => '#^requests/\d+/timeline$#',
        'methods' => ['GET'],
        'action' => 'show',
        'permission' => [
            'type' => 'hierarchical', // Permission phân cấp, cần ghép base + suffix để ra name thực tế
            'base' => 'requests', // Module cha dùng làm prefix của permission name
            'source' => 'entity', // Nguồn lấy suffix: load entity từ DB
            'entity' => Request::class, // Entity cần load để đọc ra giá trị suffix
            'field' => 'type', // Field/getter trên entity trả về suffix permission
            'id_param' => 'id', // Tên route param chứa id của entity cần load
        ],
    ],
];
