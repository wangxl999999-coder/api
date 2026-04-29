<?php

namespace Core;

class Controller
{
    protected $db;
    protected $jwt;
    protected $user;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwt = new JWT();
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function success($data = [], $message = '操作成功')
    {
        return $this->json([
            'code' => 200,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function error($code = 400, $message = '操作失败', $data = [])
    {
        return $this->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function getInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }

    protected function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    protected function requireInput($keys)
    {
        $input = $this->getInput();
        $missing = [];

        foreach ($keys as $key) {
            if (!isset($input[$key]) || $input[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $this->error(400, '缺少必要参数: ' . implode(', ', $missing));
        }

        return $input;
    }

    protected function pagination($table, $where = '1=1', $params = [], $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;

        $countSql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int)$countResult['total'];

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'pagination' => [
                'page' => (int)$page,
                'page_size' => (int)$pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize)
            ]
        ];
    }
}
