<?php

use Core\Router;

$router->get('/', function () use ($router) {
    header('Location: /admin');
    exit;
});

$router->get('/admin', function () {
    require __DIR__ . '/../public/admin/index.html';
});

$router->get('/admin/', function () {
    require __DIR__ . '/../public/admin/index.html';
});
