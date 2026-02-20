<?php

declare(strict_types=1);

return [
    'app_name' => 'ClimbSched',
    'base_url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_DATABASE') ?: 'climbsched',
        'user' => getenv('DB_USERNAME') ?: 'postgres',
        'pass' => getenv('DB_PASSWORD') ?: '',
    ],
    'mail_log_path' => __DIR__ . '/../storage/mail/mail.log',
];
