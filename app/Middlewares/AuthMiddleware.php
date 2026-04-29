<?php

namespace App\Middlewares;

use Core\JWT;
use Core\Database;

class AuthMiddleware
{
    private function getAuthorizationHeader()
    {
        $header = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
            } elseif (isset($headers['authorization'])) {
                $header = $headers['authorization'];
            }
        }

        if (empty($header) && isset($_SERVER['PHP_AUTH_USER'])) {
            $header = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . ($_SERVER['PHP_AUTH_PW'] ?? ''));
        }

        return $header;
    }

    public function handle()
    {
        $authHeader = $this->getAuthorizationHeader();

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->errorResponse(401, '未授权，请先登录');
            return false;
        }

        $token = $matches[1];
        $payload = JWT::decode($token);

        if (!$payload) {
            $this->errorResponse(401, 'Token无效或已过期');
            return false;
        }

        if (!isset($payload['sub']) || !isset($payload['type']) || $payload['type'] !== 'access') {
            $this->errorResponse(401, 'Token类型无效');
            return false;
        }

        $adminId = $payload['sub'];
        $db = Database::getInstance();
        
        $admin = $db->fetch("SELECT * FROM admins WHERE id = :id", ['id' => $adminId]);
        if (!$admin) {
            $this->errorResponse(401, '用户不存在');
            return false;
        }

        $GLOBALS['current_admin'] = $admin;
        return true;
    }

    private function errorResponse($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code' => $code,
            'message' => $message,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
