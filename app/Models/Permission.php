<?php

namespace App\Models;

use Core\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'parent_id', 'name', 'code', 'type', 'path', 
        'icon', 'component', 'sort', 'status'
    ];

    public function getAll()
    {
        $sql = "SELECT * FROM permissions ORDER BY sort ASC, id ASC";
        return $this->db->fetchAll($sql);
    }

    public function getTree($permissions = null)
    {
        if ($permissions === null) {
            $permissions = $this->getAll();
        }

        $map = [];
        foreach ($permissions as $permission) {
            $map[$permission['id']] = $permission;
            $map[$permission['id']]['children'] = [];
        }

        $tree = [];
        foreach ($permissions as $permission) {
            if ($permission['parent_id'] == 0) {
                $tree[] = &$map[$permission['id']];
            } else if (isset($map[$permission['parent_id']])) {
                $map[$permission['parent_id']]['children'][] = &$map[$permission['id']];
            }
        }

        return $tree;
    }

    public function getByCode($code)
    {
        return $this->first('code', '=', $code);
    }

    public function getByRoleIds($roleIds)
    {
        if (empty($roleIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $sql = "SELECT DISTINCT p.* 
                FROM permissions p 
                INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                WHERE rp.role_id IN ({$placeholders}) 
                ORDER BY p.sort ASC";
        
        return $this->db->fetchAll($sql, $roleIds);
    }

    public function getMenusByRoleIds($roleIds)
    {
        if (empty($roleIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $sql = "SELECT DISTINCT p.* 
                FROM permissions p 
                INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                WHERE rp.role_id IN ({$placeholders}) AND p.type IN (1, 2)
                ORDER BY p.sort ASC";
        
        $permissions = $this->db->fetchAll($sql, $roleIds);
        return $this->getTree($permissions);
    }
}
