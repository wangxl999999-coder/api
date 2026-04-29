<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Admin;
use App\Models\Role;

class AdminController extends Controller
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
            $where .= " AND (a.username LIKE :keywords OR a.nickname LIKE :keywords OR a.email LIKE :keywords)";
            $params['keywords'] = "%{$keywords}%";
        }

        if ($status !== '') {
            $where .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $adminModel = new Admin();
        $result = $adminModel->listWithRole($page, $pageSize, $where, $params);

        return $this->success($result);
    }

    public function show($id)
    {
        $adminModel = new Admin();
        $admin = $adminModel->getWithRole($id);

        if (!$admin) {
            return $this->error(404, '管理员不存在');
        }

        unset($admin['password']);
        return $this->success($admin);
    }

    public function store()
    {
        $input = $this->requireInput(['username', 'password', 'nickname']);

        $adminModel = new Admin();
        $exists = $adminModel->getByUsername($input['username']);

        if ($exists) {
            return $this->error(400, '用户名已存在');
        }

        if (strlen($input['password']) < 6) {
            return $this->error(400, '密码长度不能少于6位');
        }

        $data = [
            'username' => $input['username'],
            'password' => $this->hashPassword($input['password']),
            'nickname' => $input['nickname'],
            'email' => $input['email'] ?? '',
            'role_id' => $input['role_id'] ?? null,
            'status' => $input['status'] ?? 1,
            'is_super' => $input['is_super'] ?? 0
        ];

        $adminId = $adminModel->create($data);

        return $this->success(['id' => $adminId], '创建成功');
    }

    public function update($id)
    {
        $currentAdmin = $GLOBALS['current_admin'];
        
        $adminModel = new Admin();
        $admin = $adminModel->find($id);

        if (!$admin) {
            return $this->error(404, '管理员不存在');
        }

        if ($admin['is_super'] && !$currentAdmin['is_super']) {
            return $this->error(403, '无权限修改超级管理员');
        }

        $input = $this->getInput();
        $data = [];

        if (isset($input['nickname'])) {
            $data['nickname'] = $input['nickname'];
        }
        if (isset($input['email'])) {
            $data['email'] = $input['email'];
        }
        if (isset($input['avatar'])) {
            $data['avatar'] = $input['avatar'];
        }
        if (isset($input['role_id'])) {
            $data['role_id'] = $input['role_id'];
        }
        if (isset($input['status'])) {
            $data['status'] = $input['status'];
        }

        if (isset($input['password']) && !empty($input['password'])) {
            if (strlen($input['password']) < 6) {
                return $this->error(400, '密码长度不能少于6位');
            }
            $data['password'] = $this->hashPassword($input['password']);
        }

        if (empty($data)) {
            return $this->error(400, '没有需要更新的数据');
        }

        $adminModel->update($id, $data);

        return $this->success([], '更新成功');
    }

    public function destroy($id)
    {
        $currentAdmin = $GLOBALS['current_admin'];
        
        if ($id == $currentAdmin['id']) {
            return $this->error(400, '不能删除自己');
        }

        $adminModel = new Admin();
        $admin = $adminModel->find($id);

        if (!$admin) {
            return $this->error(404, '管理员不存在');
        }

        if ($admin['is_super']) {
            return $this->error(403, '不能删除超级管理员');
        }

        $adminModel->delete($id);

        return $this->success([], '删除成功');
    }

    public function toggleStatus($id)
    {
        $currentAdmin = $GLOBALS['current_admin'];
        
        $adminModel = new Admin();
        $admin = $adminModel->find($id);

        if (!$admin) {
            return $this->error(404, '管理员不存在');
        }

        if ($admin['is_super'] && !$currentAdmin['is_super']) {
            return $this->error(403, '无权限修改超级管理员状态');
        }

        $newStatus = $admin['status'] == 1 ? 0 : 1;
        $adminModel->update($id, ['status' => $newStatus]);

        return $this->success(['status' => $newStatus], '状态更新成功');
    }

    public function options()
    {
        $roleModel = new Role();
        $roles = $roleModel->where('status', '=', 1);

        return $this->success([
            'roles' => $roles
        ]);
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
