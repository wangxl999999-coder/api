<?php

namespace App\Models;

use Core\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'code', 'description', 'status', 'sort'];

    public function getByCode($code)
    {
        return $this->first('code', '=', $code);
    }

    public function getPermissions($roleId)
    {
        $sql = "SELECT p.* 
                FROM permissions p 
                INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                WHERE rp.role_id = :role_id 
                ORDER BY p.sort ASC";
        return $this->db->fetchAll($sql, ['role_id' => $roleId]);
    }

    public function getPermissionIds($roleId)
    {
        $sql = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
        $results = $this->db->fetchAll($sql, ['role_id' => $roleId]);
        return array_column($results, 'permission_id');
    }

    public function assignPermissions($roleId, $permissionIds)
    {
        $this->db->beginTransaction();
        try {
            $this->db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $roleId]);

            foreach ($permissionIds as $permissionId) {
                $this->db->insert('role_permissions', [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getPermissionCodes($roleId)
    {
        $sql = "SELECT p.code 
                FROM permissions p 
                INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                WHERE rp.role_id = :role_id";
        $results = $this->db->fetchAll($sql, ['role_id' => $roleId]);
        return array_column($results, 'code');
    }
}
