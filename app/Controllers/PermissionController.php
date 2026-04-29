<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Permission;
use App\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $permissionModel = new Permission();
        $permissions = $permissionModel->getAll();

        $tree = $this->buildTree($permissions);
        return $this->success(['items' => $tree]);
    }

    public function tree()
    {
        $permissionModel = new Permission();
        $permissions = $permissionModel->getAll();

        $tree = $this->buildTree($permissions);
        return $this->success(['items' => $tree]);
    }

    public function menu()
    {
        $admin = $GLOBALS['current_admin'];
        $permissionModel = new Permission();
        $menus = [];

        if ($admin['is_super']) {
            $sql = "SELECT * FROM permissions WHERE type IN (1, 2) AND status = 1 ORDER BY sort ASC";
            $menuList = $this->db->fetchAll($sql);
            $menus = $this->buildTree($menuList);
        } else {
            $roleModel = new Role();
            $permissions = $roleModel->getPermissions($admin['role_id']);
            $menuList = array_filter($permissions, function ($item) {
                return in_array($item['type'], [1, 2]) && $item['status'] == 1;
            });
            $menus = $this->buildTree($menuList);
        }

        return $this->success(['items' => $menus]);
    }

    public function show($id)
    {
        $permissionModel = new Permission();
        $permission = $permissionModel->find($id);

        if (!$permission) {
            return $this->error(404, '权限不存在');
        }

        return $this->success($permission);
    }

    public function store()
    {
        $input = $this->requireInput(['name', 'code']);

        $permissionModel = new Permission();
        $exists = $permissionModel->getByCode($input['code']);

        if ($exists) {
            return $this->error(400, '权限标识已存在');
        }

        $data = [
            'parent_id' => $input['parent_id'] ?? 0,
            'name' => $input['name'],
            'code' => $input['code'],
            'type' => $input['type'] ?? 1,
            'path' => $input['path'] ?? '',
            'icon' => $input['icon'] ?? '',
            'component' => $input['component'] ?? '',
            'sort' => $input['sort'] ?? 0,
            'status' => $input['status'] ?? 1
        ];

        $permissionId = $permissionModel->create($data);

        return $this->success(['id' => $permissionId], '创建成功');
    }

    public function update($id)
    {
        $permissionModel = new Permission();
        $permission = $permissionModel->find($id);

        if (!$permission) {
            return $this->error(404, '权限不存在');
        }

        $input = $this->getInput();
        $data = [];

        $fields = ['parent_id', 'name', 'type', 'path', 'icon', 'component', 'sort', 'status'];
        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $data[$field] = $input[$field];
            }
        }

        if (isset($input['code']) && $input['code'] !== $permission['code']) {
            $exists = $permissionModel->getByCode($input['code']);
            if ($exists) {
                return $this->error(400, '权限标识已存在');
            }
            $data['code'] = $input['code'];
        }

        if (empty($data)) {
            return $this->error(400, '没有需要更新的数据');
        }

        $permissionModel->update($id, $data);

        return $this->success([], '更新成功');
    }

    public function destroy($id)
    {
        $permissionModel = new Permission();
        $permission = $permissionModel->find($id);

        if (!$permission) {
            return $this->error(404, '权限不存在');
        }

        $children = $permissionModel->where('parent_id', '=', $id);
        if (!empty($children)) {
            return $this->error(400, '请先删除子权限');
        }

        $this->db->delete('role_permissions', 'permission_id = :permission_id', ['permission_id' => $id]);
        $permissionModel->delete($id);

        return $this->success([], '删除成功');
    }

    public function options()
    {
        $permissionModel = new Permission();
        $permissions = $permissionModel->getAll();

        $options = [['id' => 0, 'name' => '顶级菜单']];
        $menus = array_filter($permissions, function ($item) {
            return $item['type'] == 1;
        });

        $tree = $this->buildTreeForOptions($menus, 0);
        $options = array_merge($options, $tree);

        return $this->success(['items' => $options]);
    }

    private function buildTree($items, $parentId = 0)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    private function buildTreeForOptions($items, $parentId = 0, $level = 0)
    {
        $tree = [];
        $prefix = str_repeat('　', $level * 2);
        if ($level > 0) {
            $prefix .= '├─ ';
        }

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $tree[] = [
                    'id' => $item['id'],
                    'name' => $prefix . $item['name']
                ];
                $children = $this->buildTreeForOptions($items, $item['id'], $level + 1);
                $tree = array_merge($tree, $children);
            }
        }
        return $tree;
    }
}
