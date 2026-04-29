<?php

return [
    'app' => [
        'name' => 'API管理系统',
        'url' => 'http://biaozhu.com',
        'debug' => true,
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'api',
        'username' => 'root',
        'password' => '123123',
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => 'your-secret-key-change-this-in-production',
        'algorithm' => 'HS256',
        'expire' => 7200,
        'refresh_expire' => 604800,
    ],
];
