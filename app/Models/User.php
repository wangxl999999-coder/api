<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'nickname', 'email', 'phone', 'password', 
        'avatar', 'status', 'last_login_at', 'last_login_ip'
    ];

    public function getByUsername($username)
    {
        return $this->first('username', '=', $username);
    }

    public function getByEmail($email)
    {
        return $this->first('email', '=', $email);
    }

    public function getByPhone($phone)
    {
        return $this->first('phone', '=', $phone);
    }

    public function search($keywords = '', $status = null, $page = 1, $pageSize = 10)
    {
        $where = '1=1';
        $params = [];

        if ($keywords !== '') {
            $where .= " AND (username LIKE :keywords OR nickname LIKE :keywords OR email LIKE :keywords OR phone LIKE :keywords)";
            $params['keywords'] = "%{$keywords}%";
        }

        if ($status !== null && $status !== '') {
            $where .= " AND status = :status";
            $params['status'] = $status;
        }

        return $this->paginate($page, $pageSize, $where, $params);
    }
}
