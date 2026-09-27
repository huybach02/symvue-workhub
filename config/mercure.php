<?php

return [
    "subscribeTopics" => [
        "https://app.com/test",
        'https://app.com/attendance',
        'https://app.com/thong-bao-he-thong',
        'https://app.com/thong-bao-ca-nhan/{+path}',
        'https://app.com/message/{+path}',
        'https://app.com/presence',
        'https://app.com/request/{+path}',
        'https://app.com/sale-order',
        'https://app.com/dining-table',
    ],
    "topics" => [
        "test" => "https://app.com/test",
        "attendance" => "https://app.com/attendance",
        "thong-bao-he-thong" => "https://app.com/thong-bao-he-thong",
        "thong-bao-ca-nhan" => "https://app.com/thong-bao-ca-nhan/:userId",
        "message" => "https://app.com/message/:userId",
        "presence" => "https://app.com/presence",
        "request" => "https://app.com/request/:userId",
        "sale-order" => "https://app.com/sale-order",
        "dining-table" => "https://app.com/dining-table",
    ]
];
