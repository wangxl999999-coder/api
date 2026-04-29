<?php
/**
 * 系统诊断脚本
 * 用于排查登录后控制台报错问题
 */

header('Content-Type: text/html; charset=utf-8');

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('<h1 style="color:red;">错误：找不到配置文件</h1><p>请先配置 config/config.php</p>');
}

$config = require $configFile;
$dbConfig = $config['database'];

$diagnosticResults = [];

echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统诊断</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 24px;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 24px; }
        .section { margin-bottom: 24px; }
        .section h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .test-item .name { font-weight: 500; }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status.ok { background: #e6f7e6; color: #2e7d32; }
        .status.warn { background: #fff3e0; color: #ef6c00; }
        .status.error { background: #ffebee; color: #c62828; }
        .detail-box {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 16px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 0 6px 6px 0;
        }
        .info-box h4 { margin-bottom: 8px; color: #1565c0; }
        .info-box p { font-size: 13px; line-height: 1.6; color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #fafafa;
            font-weight: 600;
        }
        tr:hover { background: #f9f9f9; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-danger { background: #e53935; }
        .btn-success { background: #43a047; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 系统诊断工具</h1>
            <p>帮助排查登录后控制台报错问题</p>
        </div>
        <div class="content">
';

// 1. 数据库连接测试
echo '<div class="section">
    <h3>1. 数据库连接测试</h3>';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '<div class="test-item">
        <span class="name">数据库连接</span>
        <span class="status ok">✓ 连接成功</span>
    </div>';
    
    // 检查表是否存在
    $tables = ['admins', 'users', 'roles', 'permissions', 'role_permissions', 'login_logs', 'configs'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
            $count = $pdo->query("SELECT COUNT(*) as c FROM `{$table}`")->fetch(PDO::FETCH_ASSOC)['c'];
            echo '<div class="test-item">
                <span class="name">表 ' . $table . ' (' . $count . ' 条记录)</span>
                <span class="status ok">✓ 存在</span>
            </div>';
        } catch (PDOException $e) {
            echo '<div class="test-item">
                <span class="name">表 ' . $table . '</span>
                <span class="status error">✗ 不存在</span>
            </div>';
        }
    }
    
} catch (PDOException $e) {
    echo '<div class="test-item">
        <span class="name">数据库连接</span>
        <span class="status error">✗ 失败: ' . htmlspecialchars($e->getMessage()) . '</span>
    </div>';
    echo '</div>';
    echo '</div></body></html>';
    exit;
}

echo '</div>';

// 2. 检查管理员数据
echo '<div class="section">
    <h3>2. 管理员数据检查</h3>';

$stmt = $pdo->query("SELECT * FROM admins LIMIT 5");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($admins) > 0) {
    echo '<table>
        <tr><th>ID</th><th>用户名</th><th>昵称</th><th>是否超级管理员</th><th>状态</th><th>角色ID</th></tr>';
    foreach ($admins as $admin) {
        $isSuper = $admin['is_super'] ? '是' : '否';
        $status = $admin['status'] == 1 ? '正常' : '禁用';
        echo '<tr>
            <td>' . htmlspecialchars($admin['id']) . '</td>
            <td>' . htmlspecialchars($admin['username']) . '</td>
            <td>' . htmlspecialchars($admin['nickname']) . '</td>
            <td>' . $isSuper . '</td>
            <td>' . $status . '</td>
            <td>' . htmlspecialchars($admin['role_id'] ?? 'NULL') . '</td>
        </tr>';
    }
    echo '</table>';
    
    // 检查密码哈希
    $admin = $admins[0];
    echo '<div class="info-box" style="margin-top:16px;">
        <h4>⚠️ 密码检查</h4>
        <p>当前管理员密码哈希: <code>' . htmlspecialchars(substr($admin['password'], 0, 50) . '...') . '</code></p>
        <p>测试密码 "admin123" 是否匹配当前哈希: ';
    
    if (password_verify('admin123', $admin['password'])) {
        echo '<span style="color:green;">✓ 匹配</span>';
    } else {
        echo '<span style="color:red;">✗ 不匹配</span>';
        echo '<br>测试密码 "password" 是否匹配: ';
        if (password_verify('password', $admin['password'])) {
            echo '<span style="color:green;">✓ 匹配 (当前实际密码是 "password")</span>';
        } else {
            echo '<span style="color:red;">✗ 不匹配</span>';
        }
    }
    echo '</p></div>';
} else {
    echo '<div class="test-item">
        <span class="name">管理员数据</span>
        <span class="status error">✗ 没有管理员数据</span>
    </div>';
}

echo '</div>';

// 3. 检查权限数据
echo '<div class="section">
    <h3>3. 权限数据检查</h3>';

$stmt = $pdo->query("SELECT * FROM permissions ORDER BY id");
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($permissions) > 0) {
    echo '<div class="test-item">
        <span class="name">权限记录数</span>
        <span class="status ok">✓ ' . count($permissions) . ' 条</span>
    </div>';
    
    // 检查parent_id引用是否有效
    $ids = array_column($permissions, 'id');
    $invalidParentIds = [];
    foreach ($permissions as $p) {
        if ($p['parent_id'] != 0 && !in_array($p['parent_id'], $ids)) {
            $invalidParentIds[] = "权限ID={$p['id']} 的 parent_id={$p['parent_id']} 不存在";
        }
    }
    
    if (count($invalidParentIds) > 0) {
        echo '<div class="test-item">
            <span class="name">权限parent_id引用</span>
            <span class="status error">✗ 发现无效引用</span>
        </div>';
        echo '<div class="detail-box">' . implode("\n", $invalidParentIds) . '</div>';
    } else {
        echo '<div class="test-item">
            <span class="name">权限parent_id引用</span>
            <span class="status ok">✓ 全部有效</span>
        </div>';
    }
    
    // 显示树形结构
    echo '<div style="margin-top:16px;">
        <h4 style="margin-bottom:12px;color:#555;">权限树形结构:</h4>
        <div class="detail-box">';
    
    $permissionMap = [];
    foreach ($permissions as $p) {
        $permissionMap[$p['id']] = array_merge($p, ['children' => []]);
    }
    
    $tree = [];
    foreach ($permissions as $p) {
        if ($p['parent_id'] == 0) {
            $tree[] = &$permissionMap[$p['id']];
        } else if (isset($permissionMap[$p['parent_id']])) {
            $permissionMap[$p['parent_id']]['children'][] = &$permissionMap[$p['id']];
        }
    }
    
    function printTree($items, $level = 0) {
        $result = '';
        $indent = str_repeat('  ', $level);
        foreach ($items as $item) {
            $typeText = $item['type'] == 1 ? '菜单' : ($item['type'] == 2 ? '子菜单' : '权限');
            $result .= "{$indent}{$item['id']}. [{$typeText}] {$item['name']} ({$item['code']})\n";
            if (!empty($item['children'])) {
                $result .= printTree($item['children'], $level + 1);
            }
        }
        return $result;
    }
    
    echo htmlspecialchars(printTree($tree));
    echo '</div></div>';
    
} else {
    echo '<div class="test-item">
        <span class="name">权限数据</span>
        <span class="status error">✗ 没有权限数据</span>
    </div>';
}

echo '</div>';

// 4. 检查角色数据
echo '<div class="section">
    <h3>4. 角色和权限关联检查</h3>';

$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($roles) > 0) {
    echo '<table>
        <tr><th>ID</th><th>角色名</th><th>标识</th><th>状态</th><th>权限数量</th></tr>';
    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$role['id']]);
        $permCount = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
        $status = $role['status'] == 1 ? '正常' : '禁用';
        echo '<tr>
            <td>' . htmlspecialchars($role['id']) . '</td>
            <td>' . htmlspecialchars($role['name']) . '</td>
            <td>' . htmlspecialchars($role['code']) . '</td>
            <td>' . $status . '</td>
            <td>' . $permCount . '</td>
        </tr>';
    }
    echo '</table>';
} else {
    echo '<div class="test-item">
        <span class="name">角色数据</span>
        <span class="status error">✗ 没有角色数据</span>
    </div>';
}

echo '</div>';

// 5. 常见问题和解决方案
echo '<div class="section">
    <h3>5. 常见问题与解决方案</h3>
    
    <div class="info-box">
        <h4>问题1：登录提示"用户名或密码错误"</h4>
        <p><strong>原因：</strong>数据库中默认密码哈希对应的明文是 "password" 而非 "admin123"。</p>
        <p><strong>解决：</strong></p>
        <ol style="margin-left:20px;margin-top:8px;">
            <li>临时使用用户名 <code>admin</code> / 密码 <code>password</code> 登录</li>
            <li>或者访问 <a href="reset_password.php">reset_password.php</a> 重置密码为 admin123</li>
        </ol>
    </div>
    
    <div class="info-box">
        <h4>问题2：登录后控制台报错/空白</h4>
        <p><strong>可能原因：</strong></p>
        <ol style="margin-left:20px;margin-top:8px;">
            <li>权限表中 parent_id 引用无效</li>
            <li>缺少必要的数据表或数据</li>
            <li>JWT Token 解析失败</li>
            <li>PHP错误被抑制</li>
        </ol>
        <p><strong>调试方法：</strong></p>
        <div class="code-block">// 在 public/index.php 开头启用错误显示
error_reporting(E_ALL);
ini_set("display_errors", 1);

// 或在浏览器开发者工具中查看 Network 标签的响应</div>
    </div>
    
    <div class="info-box">
        <h4>问题3：API返回404</h4>
        <p><strong>解决：</strong></p>
        <ol style="margin-left:20px;margin-top:8px;">
            <li>确保Apache启用了 <code>mod_rewrite</code></li>
            <li>确保 <code>.htaccess</code> 文件存在且内容正确</li>
            <li>确保网站根目录指向 <code>public</code> 目录</li>
        </ol>
    </div>
</div>';

// 6. 快速操作按钮
echo '<div class="section">
    <h3>6. 快速操作</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="reset_password.php" class="btn">🔐 重置管理员密码</a>
        <a href="install.php" class="btn btn-success">📦 重新安装/导入数据</a>
        <a href="admin/" class="btn">🖥️ 进入管理后台</a>
    </div>
    
    <div style="margin-top:20px;padding:16px;background:#fff3e0;border-radius:6px;">
        <h4 style="margin-bottom:8px;color:#ef6c00;">⚠️ 安全提示</h4>
        <p style="font-size:13px;line-height:1.6;color:#555;">
            诊断完成后，请删除以下安全敏感文件：
            <code style="background:#ffcc80;padding:2px 6px;border-radius:4px;">diagnose.php</code>, 
            <code style="background:#ffcc80;padding:2px 6px;border-radius:4px;">reset_password.php</code>, 
            <code style="background:#ffcc80;padding:2px 6px;border-radius:4px;">install.php</code>
        </p>
    </div>
</div>';

echo '</div></div></body></html>';
