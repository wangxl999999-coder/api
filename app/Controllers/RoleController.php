<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Role;
use App\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $page = $this->getQuery('page', 1);
        $pageSize = $this->getQuery('page_size', 10);
        $keywords = $this->getQuery('keywords', '');
        $status = $this->getQuery('status', '');

        $where = '1=1';
        $params = [];

        if ($keywords !== '') {
            $where .= " AND (name LIKE :keywords OR code LIKE :keywords OR description LIKE :keywords)";
            $params['keywords'] = "%{$keywords}%";
        }

        if ($status !== '') {
            $where .= " AND status = :status";
            $params['status'] = $status;
        }

        $roleModel = new Role();
        $result = $roleModel->paginate($page, $pageSize, $where, $params);

        return $this->success($result);
    }

    public function all()
    {
        $roleModel = new Role();
        $roles = $roleModel->where('status', '=', 1);

        return $this->success(['items' => $roles]);
    }

    public function show($id)
    {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            return $this->error(404, '角色不存在');
        }

        $permissionIds = $roleModel->getPermissionIds($id);
        $role['permission_ids'] = $permissionIds;

        return $this->success($role);
    }

    public function store()
    {
        $input = $this->requireInput(['name', 'code']);

        $roleModel = new Role();
        $exists = $roleModel->getByCode($input['code']);

        if ($exists) {
            return $this->error(400, '角色标识已存在');
        }

        $data = [
            'name' => $input['name'],
            'code' => $input['code'],
            'description' => $input['description'] ?? '',
            'status' => $input['status'] ?? 1,
            'sort' => $input['sort'] ?? 0
        ];

        $roleId = $roleModel->create($data);

        if (isset($input['permission_ids']) && is_array($input['permission_ids'])) {
            $roleModel->assignPermissions($roleId, $input['permission_ids']);
        }

        return $this->success(['id' => $roleId], '创建成功');
    }

    public function update($id)
    {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            return $this->error(404, '角色不存在');
        }

        if ($role['code'] === 'super_admin') {
            return $this->error(403, '超级管理员角色不可修改');
        }

        $input = $this->getInput();
        $data = [];

        if (isset($input['name'])) {
            $data['name'] = $input['name'];
        }
        if (isset($input['description'])) {
            $data['description'] = $input['description'];
        }
        if (isset($input['status'])) {
            $data['status'] = $input['status'];
        }
        if (isset($input['sort'])) {
            $data['sort'] = $input['sort'];
        }

        if (isset($input['code']) && $input['code'] !== $role['code']) {
            $exists = $roleModel->getByCode($input['code']);
            if ($exists) {
                return $this->error(400, '角色标识已存在');
            }
            $data['code'] = $input['code'];
        }

        if (!empty($data)) {
            $roleModel->update($id, $data);
        }

        if (isset($input['permission_ids']) && is_array($input['permission_ids'])) {
            $roleModel->assignPermissions($id, $input['permission_ids']);
        }

        return $this->success([], '更新成功');
    }

    public function destroy($id)
    {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            return $this->error(404, '角色不存在');
        }

        if ($role['code'] === 'super_admin') {
            return $this->error(403, '超级管理员角色不可删除');
        }

        $sql = "SELECT COUNT(*) as count FROM admins WHERE role_id = :role_id";
        $result = $this->db->fetch($sql, ['role_id' => $id]);
        if ($result['count'] > 0) {
            return $this->error(400, '该角色下还有管理员，无法删除');
        }

        $this->db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $id]);
        $roleModel->delete($id);

        return $this->success([], '删除成功');
    }

    public function toggleStatus($id)
    {
        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            return $this->error(404, '角色不存在');
        }

        if ($role['code'] === 'super_admin') {
            return $this->error(403, '超级管理员角色不可修改状态');
        }

        $newStatus = $role['status'] == 1 ? 0 : 1;
        $roleModel->update($id, ['status' => $newStatus]);

        return $this->success(['status' => $newStatus], '状态更新成功');
    }
}
