<?php

namespace App\Models;

use Core\Model;

class Admin extends Model
{
    protected $table = 'admins';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'nickname', 'email', 'password', 'avatar', 
        'role_id', 'status', 'is_super', 'last_login_at', 'last_login_ip'
    ];

    public function getByUsername($username)
    {
        return $this->first('username', '=', $username);
    }

    public function getWithRole($id)
    {
        $sql = "SELECT a.*, r.name as role_name, r.code as role_code 
                FROM admins a 
                LEFT JOIN roles r ON a.role_id = r.id 
                WHERE a.id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function listWithRole($page = 1, $pageSize = 10, $where = '1=1', $params = [])
    {
        $offset = ($page - 1) * $pageSize;

        $countSql = "SELECT COUNT(*) as total FROM admins a WHERE {$where}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int)$countResult['total'];

        $sql = "SELECT a.*, r.name as role_name, r.code as role_code 
                FROM admins a 
                LEFT JOIN roles r ON a.role_id = r.id 
                WHERE {$where} 
                ORDER BY a.created_at DESC 
                LIMIT {$pageSize} OFFSET {$offset}";
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
