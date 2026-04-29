<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Config;

class ConfigController extends Controller
{
    public function index()
    {
        $groupName = $this->getQuery('group_name', 'basic');

        $configModel = new Config();
        $configs = $configModel->getByGroup($groupName);

        return $this->success(['items' => $configs]);
    }

    public function all()
    {
        $configModel = new Config();
        $configs = $configModel->getAllKeyValue();

        return $this->success($configs);
    }

    public function groups()
    {
        $configModel = new Config();
        $groups = $configModel->getAllGroups();

        return $this->success(['items' => $groups]);
    }

    public function show($keyName)
    {
        $configModel = new Config();
        $config = $configModel->getByKey($keyName);

        if (!$config) {
            return $this->error(404, '配置不存在');
        }

        return $this->success($config);
    }

    public function store()
    {
        $input = $this->requireInput(['key_name', 'name']);

        $configModel = new Config();
        $exists = $configModel->getByKey($input['key_name']);

        if ($exists) {
            return $this->error(400, '配置键名已存在');
        }

        $data = [
            'group_name' => $input['group_name'] ?? 'basic',
            'key_name' => $input['key_name'],
            'value' => $input['value'] ?? '',
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'type' => $input['type'] ?? 'text',
            'options' => isset($input['options']) ? json_encode($input['options'], JSON_UNESCAPED_UNICODE) : null,
            'sort' => $input['sort'] ?? 0,
            'status' => $input['status'] ?? 1
        ];

        $configId = $configModel->create($data);

        return $this->success(['id' => $configId], '创建成功');
    }

    public function update($keyName)
    {
        $configModel = new Config();
        $config = $configModel->getByKey($keyName);

        if (!$config) {
            return $this->error(404, '配置不存在');
        }

        $input = $this->getInput();
        $data = [];

        if (isset($input['value'])) {
            $data['value'] = $input['value'];
        }
        if (isset($input['name'])) {
            $data['name'] = $input['name'];
        }
        if (isset($input['description'])) {
            $data['description'] = $input['description'];
        }
        if (isset($input['type'])) {
            $data['type'] = $input['type'];
        }
        if (isset($input['options'])) {
            $data['options'] = json_encode($input['options'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($input['sort'])) {
            $data['sort'] = $input['sort'];
        }
        if (isset($input['status'])) {
            $data['status'] = $input['status'];
        }

        if (empty($data)) {
            return $this->error(400, '没有需要更新的数据');
        }

        $configModel->updateByKey($keyName, $data['value'] ?? $config['value']);

        if (count($data) > 1 || !isset($data['value'])) {
            $configModel->update($config['id'], $data);
        }

        return $this->success([], '更新成功');
    }

    public function destroy($keyName)
    {
        $configModel = new Config();
        $config = $configModel->getByKey($keyName);

        if (!$config) {
            return $this->error(404, '配置不存在');
        }

        $configModel->delete($config['id']);

        return $this->success([], '删除成功');
    }

    public function batchUpdate()
    {
        $input = $this->getInput();

        if (empty($input)) {
            return $this->error(400, '没有需要更新的数据');
        }

        $configModel = new Config();
        $configModel->batchUpdate($input);

        return $this->success([], '批量更新成功');
    }

    public function getByGroup($groupName)
    {
        $configModel = new Config();
        $configs = $configModel->getByGroup($groupName);

        $result = [];
        foreach ($configs as $config) {
            $result[$config['key_name']] = [
                'value' => $config['value'],
                'name' => $config['name'],
                'type' => $config['type'],
                'options' => $config['options'] ? json_decode($config['options'], true) : null
            ];
        }

        return $this->success($result);
    }

    public function upload()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->error(400, '文件上传失败');
        }

        $file = $_FILES['file'];
        $configModel = new Config();
        
        $maxSize = (int)$configModel->getValue('upload_max_size', 10485760);
        $allowedTypes = $configModel->getValue('upload_allowed_types', 'jpg,jpeg,png,gif,doc,docx,xls,xlsx,pdf');
        $allowedTypes = explode(',', $allowedTypes);
        $allowedTypes = array_map('trim', $allowedTypes);

        if ($file['size'] > $maxSize) {
            return $this->error(400, '文件大小超出限制');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            return $this->error(400, '不支持的文件类型');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return $this->error(500, '文件保存失败');
        }

        $config = require __DIR__ . '/../../config/config.php';
        $fileUrl = $config['app']['url'] . '/uploads/' . $fileName;

        return $this->success([
            'url' => $fileUrl,
            'name' => $file['name'],
            'size' => $file['size'],
            'ext' => $ext
        ], '上传成功');
    }
}
