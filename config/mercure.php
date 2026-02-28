<?php

return [
    "subscribeTopics" => [
        "https://app.com/test",
        'https://app.com/thong-bao-he-thong',
        'https://app.com/thong-bao-ca-nhan/{+path}',
    ],
    "topics" => [
        "test" => "https://app.com/test",
        "thong-bao-he-thong" => "https://app.com/thong-bao-he-thong",
        "thong-bao-ca-nhan" => "https://app.com/thong-bao-ca-nhan/:userId",
    ]
];
