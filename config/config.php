<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'سامانه سخت‌افزار دانشگاه آزاد اسلامی واحد یزد',
        'base_url' => rtrim((string)(getenv('IHW_BASE_URL') ?: ''), '/'),
        'timezone' => 'Asia/Tehran',
    ],
    'db' => [
        'dsn' => getenv('IHW_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=personnel;charset=utf8mb4',
        'user' => getenv('IHW_DB_USER') ?: 'root',
        'pass' => getenv('IHW_DB_PASS') ?: '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'security' => [
        'session_name' => 'ihw_session',
        'upload_max_mb' => 10,
        'allowed_extensions' => ['csv', 'txt'],
    ],
];
