<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../core/Autoload.php';

use Core\Router;

$router = new Router();

require __DIR__ . '/../routes/api.php';
require __DIR__ . '/../routes/web.php';

$router->dispatch();
