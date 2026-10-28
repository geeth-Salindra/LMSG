<?php
return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'lmsg_db',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_user' => '',
        'smtp_pass' => '',
        'from_email' => '',
        'from_name' => 'LMSG Learning Portal',
    ],
    'aws' => [
        'region' => '',
        's3_bucket' => '',
        'cloudfront_domain' => '',
        'access_key' => '',
        'secret_key' => '',
    ],
    'deepseek' => [
        'api_key' => '',
        'model' => 'deepseek-reasoner',
        'api_base' => 'https://api.deepseek.com',
    ],
    'openrouter' => [
        'api_key' => '',
        'model' => 'deepseek/deepseek-chat',
        'api_url' => 'https://openrouter.ai/api/v1/chat/completions',
    ],
];