<?php
return [
    'name' => 'Система учёта заявок пользователей',
    'statuses' => ['new' => 'Новая', 'in_progress' => 'В работе', 'done' => 'Решена'],
    'priorities' => ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий', 'critical' => 'Критический'],
    'debug' => getenv('APP_DEBUG') === '1',
    'cache' => [
        'enabled' => getenv('CACHE_ENABLED') !== '0',
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('REDIS_PORT') ?: 6379),
        'prefix' => 'support:lab10:', 'timeout' => 0.3,
        'jitter' => true,
    ],
    'token_ttl' => 7200,
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'supportdb', 'user' => getenv('DB_USER') ?: 'lab9',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
