<?php

namespace App\Models;

use Core\Model;

class LoginLog extends Model
{
    protected $table = 'login_logs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'admin_id', 'username', 'ip', 'user_agent', 
        'login_type', 'status', 'message'
    ];

    public function add($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }

    public function search($params = [], $page = 1, $pageSize = 10)
    {
        $where = '1=1';
        $bindParams = [];

        if (isset($params['username']) && $params['username'] !== '') {
            $where .= " AND username LIKE :username";
            $bindParams['username'] = "%{$params['username']}%";
        }

        if (isset($params['login_type']) && $params['login_type'] !== '') {
            $where .= " AND login_type = :login_type";
            $bindParams['login_type'] = $params['login_type'];
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $where .= " AND status = :status";
            $bindParams['status'] = $params['status'];
        }

        if (isset($params['start_time']) && $params['start_time'] !== '') {
            $where .= " AND created_at >= :start_time";
            $bindParams['start_time'] = $params['start_time'];
        }

        if (isset($params['end_time']) && $params['end_time'] !== '') {
            $where .= " AND created_at <= :end_time";
            $bindParams['end_time'] = $params['end_time'];
        }

        return $this->paginate($page, $pageSize, $where, $bindParams);
    }

    public function clear($days = 30)
    {
        $time = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->delete($this->table, 'created_at < :time', ['time' => $time]);
    }
}
