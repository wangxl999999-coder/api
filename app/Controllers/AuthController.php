<?php

namespace App\Controllers;

use Core\Controller;
use Core\JWT;
use App\Models\Admin;
use App\Models\LoginLog;
use App\Models\Role;

class AuthController extends Controller
{
    public function login()
    {
        $input = $this->requireInput(['username', 'password']);
        $username = $input['username'];
        $password = $input['password'];

        $adminModel = new Admin();
        $admin = $adminModel->getByUsername($username);

        $logModel = new LoginLog();
        $logData = [
            'username' => $username,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'login_type' => 1,
            'status' => 0,
            'message' => ''
        ];

        if (!$admin) {
            $logData['message'] = '用户不存在';
            $logModel->add($logData);
            return $this->error(401, '用户名或密码错误');
        }

        if ($admin['status'] != 1) {
            $logData['message'] = '账号已被禁用';
            $logModel->add($logData);
            return $this->error(403, '账号已被禁用');
        }

        if (!$this->verifyPassword($password, $admin['password'])) {
            $logData['message'] = '密码错误';
            $logModel->add($logData);
            return $this->error(401, '用户名或密码错误');
        }

        $tokens = JWT::generateTokens($admin['id']);

        $adminModel->update($admin['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->getClientIP()
        ]);

        $logData['admin_id'] = $admin['id'];
        $logData['status'] = 1;
        $logData['message'] = '登录成功';
        $logModel->add($logData);

        return $this->success([
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'user_info' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname'],
                'avatar' => $admin['avatar'],
                'email' => $admin['email'],
                'is_super' => $admin['is_super'],
                'role_id' => $admin['role_id']
            ]
        ], '登录成功');
    }

    public function logout()
    {
        return $this->success([], '退出登录成功');
    }

    public function refresh()
    {
        $input = $this->requireInput(['refresh_token']);
        $refreshToken = $input['refresh_token'];

        $payload = JWT::decode($refreshToken);

        if (!$payload || !isset($payload['sub']) || !isset($payload['type']) || $payload['type'] !== 'refresh') {
            return $this->error(401, '无效的刷新令牌');
        }

        $adminId = $payload['sub'];
        $adminModel = new Admin();
        $admin = $adminModel->find($adminId);

        if (!$admin || $admin['status'] != 1) {
            return $this->error(401, '用户不存在或已被禁用');
        }

        $tokens = JWT::generateTokens($adminId);

        return $this->success([
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in']
        ], '令牌刷新成功');
    }

    public function userInfo()
    {
        $admin = $GLOBALS['current_admin'];
        
        $roleModel = new Role();
        $permissions = [];
        $menus = [];

        if ($admin['is_super']) {
            $sql = "SELECT code FROM permissions";
            $results = $this->db->fetchAll($sql);
            $permissions = array_column($results, 'code');

            $sql = "SELECT * FROM permissions WHERE type IN (1, 2) ORDER BY sort ASC";
            $menuList = $this->db->fetchAll($sql);
            $menus = $this->buildMenuTree($menuList);
        } else {
            $permissions = $roleModel->getPermissionCodes($admin['role_id']);
            $menuList = $roleModel->getPermissions($admin['role_id']);
            $menuList = array_filter($menuList, function ($item) {
                return in_array($item['type'], [1, 2]);
            });
            $menus = $this->buildMenuTree($menuList);
        }

        return $this->success([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'],
            'avatar' => $admin['avatar'],
            'email' => $admin['email'],
            'is_super' => $admin['is_super'],
            'role_id' => $admin['role_id'],
            'permissions' => $permissions,
            'menus' => $menus
        ]);
    }

    public function changePassword()
    {
        $admin = $GLOBALS['current_admin'];
        $input = $this->requireInput(['old_password', 'new_password', 'confirm_password']);

        if ($input['new_password'] !== $input['confirm_password']) {
            return $this->error(400, '两次输入的新密码不一致');
        }

        if (strlen($input['new_password']) < 6) {
            return $this->error(400, '新密码长度不能少于6位');
        }

        $adminModel = new Admin();
        $adminData = $adminModel->find($admin['id']);

        if (!$this->verifyPassword($input['old_password'], $adminData['password'])) {
            return $this->error(400, '原密码错误');
        }

        $adminModel->update($admin['id'], [
            'password' => $this->hashPassword($input['new_password'])
        ]);

        return $this->success([], '密码修改成功');
    }

    public function updateProfile()
    {
        $admin = $GLOBALS['current_admin'];
        $input = $this->getInput();

        $allowedFields = ['nickname', 'avatar', 'email'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (empty($updateData)) {
            return $this->error(400, '没有需要更新的数据');
        }

        $adminModel = new Admin();
        $adminModel->update($admin['id'], $updateData);

        return $this->success($updateData, '个人信息更新成功');
    }

    private function buildMenuTree($menuList)
    {
        $map = [];
        foreach ($menuList as $menu) {
            $map[$menu['id']] = $menu;
            $map[$menu['id']]['children'] = [];
        }

        $tree = [];
        foreach ($menuList as $menu) {
            if ($menu['parent_id'] == 0) {
                $tree[] = &$map[$menu['id']];
            } else if (isset($map[$menu['parent_id']])) {
                $map[$menu['parent_id']]['children'][] = &$map[$menu['id']];
            }
        }

        return $tree;
    }

    private function getClientIP()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ?: $ip;
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
