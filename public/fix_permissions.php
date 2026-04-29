<?php
/**
 * 权限数据修复脚本
 * 用于修复权限表中parent_id引用错误的问题
 */

header('Content-Type: text/html; charset=utf-8');

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('<h1 style="color:red;">错误：找不到配置文件</h1><p>请先配置 config/config.php</p>');
}

$config = require $configFile;
$dbConfig = $config['database'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        if ($action === 'check') {
            $stmt = $pdo->query("SELECT * FROM permissions ORDER BY id");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($permissions) === 0) {
                $message = '权限表为空，请先运行初始化脚本或点击"重新初始化权限数据"按钮';
                $messageType = 'warning';
            } else {
                $ids = array_column($permissions, 'id');
                $invalidParentIds = [];
                foreach ($permissions as $p) {
                    if ($p['parent_id'] != 0 && !in_array($p['parent_id'], $ids)) {
                        $invalidParentIds[] = "权限ID={$p['id']} ({$p['name']}) 的 parent_id={$p['parent_id']} 不存在";
                    }
                }
                
                if (count($invalidParentIds) > 0) {
                    $message = "发现 " . count($invalidParentIds) . " 处无效的parent_id引用：\n" . implode("\n", $invalidParentIds);
                    $messageType = 'error';
                } else {
                    $message = "权限数据检查通过！共 " . count($permissions) . " 条权限记录，parent_id引用全部有效。";
                    $messageType = 'success';
                }
            }
        } elseif ($action === 'fix') {
            $pdo->beginTransaction();
            
            try {
                $pdo->exec("DELETE FROM role_permissions");
                $pdo->exec("DELETE FROM permissions");
                $pdo->exec("ALTER TABLE permissions AUTO_INCREMENT = 1");
                
                $permissionsSql = "
INSERT INTO permissions (id, parent_id, name, code, type, path, icon, component, sort) VALUES 
(1, 0, '系统管理', 'system', 1, '/system', 'setting', NULL, 1),
(2, 1, '管理员管理', 'admin', 1, '/system/admin', 'user', 'system/admin/index', 1),
(3, 2, '查看管理员列表', 'admin:list', 2, NULL, NULL, NULL, 1),
(4, 2, '添加管理员', 'admin:add', 2, NULL, NULL, NULL, 2),
(5, 2, '编辑管理员', 'admin:edit', 2, NULL, NULL, NULL, 3),
(6, 2, '删除管理员', 'admin:delete', 2, NULL, NULL, NULL, 4),
(7, 1, '角色管理', 'role', 1, '/system/role', 'team', 'system/role/index', 2),
(8, 7, '查看角色列表', 'role:list', 2, NULL, NULL, NULL, 1),
(9, 7, '添加角色', 'role:add', 2, NULL, NULL, NULL, 2),
(10, 7, '编辑角色', 'role:edit', 2, NULL, NULL, NULL, 3),
(11, 7, '删除角色', 'role:delete', 2, NULL, NULL, NULL, 4),
(12, 7, '分配权限', 'role:permission', 2, NULL, NULL, NULL, 5),
(13, 1, '权限管理', 'permission', 1, '/system/permission', 'key', 'system/permission/index', 3),
(14, 13, '查看权限列表', 'permission:list', 2, NULL, NULL, NULL, 1),
(15, 13, '添加权限', 'permission:add', 2, NULL, NULL, NULL, 2),
(16, 13, '编辑权限', 'permission:edit', 2, NULL, NULL, NULL, 3),
(17, 13, '删除权限', 'permission:delete', 2, NULL, NULL, NULL, 4),
(18, 0, '日志管理', 'log', 1, '/log', 'file', NULL, 2),
(19, 18, '登录日志', 'login_log', 1, '/log/login', 'login', 'log/login/index', 1),
(20, 19, '查看登录日志', 'login_log:list', 2, NULL, NULL, NULL, 1),
(21, 19, '删除登录日志', 'login_log:delete', 2, NULL, NULL, NULL, 2),
(22, 0, '配置管理', 'config', 1, '/config', 'setting', NULL, 3),
(23, 22, '基本配置', 'basic_config', 1, '/config/basic', 'setting', 'config/basic/index', 1),
(24, 23, '查看配置', 'config:list', 2, NULL, NULL, NULL, 1),
(25, 23, '编辑配置', 'config:edit', 2, NULL, NULL, NULL, 2),
(26, 0, '用户管理', 'user_manage', 1, '/user', 'user', NULL, 4),
(27, 26, '用户列表', 'user', 1, '/user/list', 'user', 'user/index', 1),
(28, 27, '查看用户列表', 'user:list', 2, NULL, NULL, NULL, 1),
(29, 27, '添加用户', 'user:add', 2, NULL, NULL, NULL, 2),
(30, 27, '编辑用户', 'user:edit', 2, NULL, NULL, NULL, 3),
(31, 27, '删除用户', 'user:delete', 2, NULL, NULL, NULL, 4),
(32, 27, '查看用户详情', 'user:view', 2, NULL, NULL, NULL, 5)
";
                $pdo->exec($permissionsSql);
                
                $rolePermsSql = "INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions";
                $pdo->exec($rolePermsSql);
                
                $pdo->commit();
                
                $message = "权限数据修复成功！\n- 已删除旧的权限数据\n- 已重新插入32条权限记录\n- 已重新关联超级管理员角色权限\n\n请重新登录系统测试。";
                $messageType = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    } catch (PDOException $e) {
        $message = '数据库操作失败: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>权限数据修复工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e53935 0%, #d81b60 100%);
            color: #fff;
            padding: 24px;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 24px; }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 0 6px 6px 0;
        }
        .info-box h4 { margin-bottom: 8px; color: #1565c0; }
        .info-box p { font-size: 13px; line-height: 1.6; color: #333; }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ef6c00;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 0 6px 6px 0;
        }
        .warning-box h4 { margin-bottom: 8px; color: #e65100; }
        .warning-box p { font-size: 13px; line-height: 1.6; color: #333; }
        .message-box {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 13px;
        }
        .message-box.success { background: #e6f7e6; color: #2e7d32; }
        .message-box.error { background: #ffebee; color: #c62828; }
        .message-box.warning { background: #fff3e0; color: #ef6c00; }
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            font-weight: 500;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary { background: #667eea; }
        .btn-danger { background: #e53935; }
        .btn-success { background: #43a047; }
        .steps {
            background: #f9f9f9;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .steps h4 { margin-bottom: 12px; color: #555; }
        .steps ol { margin-left: 20px; line-height: 1.8; font-size: 14px; color: #555; }
        .steps li { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 权限数据修复工具</h1>
            <p>修复登录后控制台报错问题 - 权限表parent_id引用错误</p>
        </div>
        <div class="content">
            <div class="info-box">
                <h4>📋 问题说明</h4>
                <p>登录后控制台报错的常见原因是权限表中存在无效的 parent_id 引用。<br>
                当权限数据插入时没有显式指定ID，而 AUTO_INCREMENT 值不是从1开始时，<br>
                parent_id 就会指向不存在的权限ID，导致菜单树构建失败。</p>
            </div>

            <div class="steps">
                <h4>🔍 修复步骤</h4>
                <ol>
                    <li><strong>检查数据</strong>：点击"检查权限数据"按钮，查看是否存在parent_id引用问题</li>
                    <li><strong>修复数据</strong>：如果发现问题，点击"重新初始化权限数据"按钮</li>
                    <li><strong>测试登录</strong>：修复完成后，重新登录系统测试</li>
                </ol>
            </div>

            <?php if ($message): ?>
            <div class="message-box <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="warning-box">
                <h4>⚠️ 注意事项</h4>
                <p>• "重新初始化权限数据"将删除所有现有的权限数据和角色权限关联<br>
                • 超级管理员角色(ID=1)将自动关联所有新权限<br>
                • 如果你有自定义的权限，修复后需要重新配置</p>
            </div>

            <form method="post" class="btn-group">
                <button type="submit" name="action" value="check" class="btn btn-primary">
                    🔍 检查权限数据
                </button>
                <button type="submit" name="action" value="fix" class="btn btn-danger" 
                        onclick="return confirm('确定要重新初始化权限数据吗？\n\n这将删除所有现有权限并重新插入标准权限数据。')">
                    🔧 重新初始化权限数据
                </button>
            </form>
        </div>
    </div>
</body>
</html>
