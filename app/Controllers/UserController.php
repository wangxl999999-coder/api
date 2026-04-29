<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $page = $this->getQuery('page', 1);
        $pageSize = $this->getQuery('page_size', 10);
        $keywords = $this->getQuery('keywords', '');
        $status = $this->getQuery('status', '');

        $userModel = new User();
        $result = $userModel->search($keywords, $status, $page, $pageSize);

        foreach ($result['items'] as &$item) {
            unset($item['password']);
        }

        return $this->success($result);
    }

    public function show($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->error(404, '用户不存在');
        }

        unset($user['password']);
        return $this->success($user);
    }

    public function store()
    {
        $input = $this->requireInput(['username', 'password', 'nickname']);

        $userModel = new User();
        
        $exists = $userModel->getByUsername($input['username']);
        if ($exists) {
            return $this->error(400, '用户名已存在');
        }

        if (isset($input['email']) && $input['email'] !== '') {
            $exists = $userModel->getByEmail($input['email']);
            if ($exists) {
                return $this->error(400, '邮箱已被使用');
            }
        }

        if (isset($input['phone']) && $input['phone'] !== '') {
            $exists = $userModel->getByPhone($input['phone']);
            if ($exists) {
                return $this->error(400, '手机号已被使用');
            }
        }

        if (strlen($input['password']) < 6) {
            return $this->error(400, '密码长度不能少于6位');
        }

        $data = [
            'username' => $input['username'],
            'password' => $this->hashPassword($input['password']),
            'nickname' => $input['nickname'],
            'email' => $input['email'] ?? '',
            'phone' => $input['phone'] ?? '',
            'avatar' => $input['avatar'] ?? '',
            'status' => $input['status'] ?? 1
        ];

        $userId = $userModel->create($data);

        return $this->success(['id' => $userId], '创建成功');
    }

    public function update($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->error(404, '用户不存在');
        }

        $input = $this->getInput();
        $data = [];

        if (isset($input['nickname'])) {
            $data['nickname'] = $input['nickname'];
        }
        if (isset($input['email'])) {
            $exists = $userModel->getByEmail($input['email']);
            if ($exists && $exists['id'] != $id) {
                return $this->error(400, '邮箱已被使用');
            }
            $data['email'] = $input['email'];
        }
        if (isset($input['phone'])) {
            $exists = $userModel->getByPhone($input['phone']);
            if ($exists && $exists['id'] != $id) {
                return $this->error(400, '手机号已被使用');
            }
            $data['phone'] = $input['phone'];
        }
        if (isset($input['avatar'])) {
            $data['avatar'] = $input['avatar'];
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

        $userModel->update($id, $data);

        return $this->success([], '更新成功');
    }

    public function destroy($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->error(404, '用户不存在');
        }

        $userModel->delete($id);

        return $this->success([], '删除成功');
    }

    public function toggleStatus($id)
    {
        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user) {
            return $this->error(404, '用户不存在');
        }

        $newStatus = $user['status'] == 1 ? 0 : 1;
        $userModel->update($id, ['status' => $newStatus]);

        return $this->success(['status' => $newStatus], '状态更新成功');
    }

    public function batchDestroy()
    {
        $input = $this->requireInput(['ids']);
        $ids = $input['ids'];

        if (!is_array($ids) || empty($ids)) {
            return $this->error(400, '参数格式错误');
        }

        $userModel = new User();
        $count = 0;

        foreach ($ids as $id) {
            if ($userModel->delete($id)) {
                $count++;
            }
        }

        return $this->success(['deleted_count' => $count], '批量删除成功');
    }

    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
