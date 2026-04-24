<?php

declare(strict_types=1);

return [
    'database' => [
        'adapter'  => 'Mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'username' => getenv('DB_USER') ?: 'shop',
        'password' => getenv('DB_PASSWORD') ?: 'shop',
        'dbname'   => getenv('DB_NAME') ?: 'shop',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'env' => getenv('APP_ENV') ?: 'prod',
    ],
    'application' => [
        'migrationsDir' => 'db/migrations',
    ],
];
