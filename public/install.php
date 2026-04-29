<?php
header('Content-Type: text/html; charset=utf-8');

$errors = [];
$successes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_connection') {
        $config = require __DIR__ . '/../config/config.php';
        $dbConfig = $config['database'];
        
        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $successes[] = '数据库连接成功！';
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbConfig['database']} DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $successes[] = "数据库 `{$dbConfig['database']}` 检查/创建成功！";
            
            $pdo->exec("USE {$dbConfig['database']}");
            $successes[] = '已切换到目标数据库';
            
            $sqlFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $statements = array_filter(array_map('trim', explode(";\n", $sql)));
                
                $imported = 0;
                foreach ($statements as $stmt) {
                    if (!empty($stmt) && strpos($stmt, '--') !== 0) {
                        try {
                            $pdo->exec($stmt);
                            $imported++;
                        } catch (PDOException $e) {
                            $errors[] = "执行SQL出错: " . $e->getMessage();
                        }
                    }
                }
                $successes[] = "数据库结构导入完成，执行了 {$imported} 条语句";
            } else {
                $errors[] = "找不到数据库结构文件: {$sqlFile}";
            }
            
        } catch (PDOException $e) {
            $errors[] = '数据库连接失败: ' . $e->getMessage();
        }
    }
    
    if ($action === 'update_password') {
        $config = require __DIR__ . '/../config/config.php';
        $dbConfig = $config['database'];
        
        $newPassword = $_POST['new_password'] ?? 'admin123';
        
        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $successes[] = "生成的密码哈希: {$hash}";
            
            $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
            $result = $stmt->execute(['password' => $hash]);
            
            if ($result) {
                $successes[] = "管理员密码已更新为: {$newPassword}";
            } else {
                $errors[] = '密码更新失败，可能是管理员不存在';
            }
            
        } catch (PDOException $e) {
            $errors[] = '操作失败: ' . $e->getMessage();
        }
    }
    
    if ($action === 'test_api') {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $successes[] = "当前服务器地址: {$baseUrl}";
        $successes[] = "API基础路径: {$baseUrl}/api";
        $successes[] = "管理后台: {$baseUrl}/admin";
        
        $testPaths = [
            '/api/auth/login' => 'POST (需要username和password)',
            '/api/users' => 'GET (需要登录)',
            '/api/admins' => 'GET (需要登录)',
            '/api/configs/all' => 'GET (需要登录)',
        ];
        
        $successes[] = '<br><strong>可用API接口:</strong>';
        foreach ($testPaths as $path => $desc) {
            $successes[] = "- {$path} - {$desc}";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装修复工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee; }
        .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .section-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
        }
        .btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
        }
        .message-list { margin-top: 16px; }
        .message-item {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .message-item.success { background: rgba(17, 153, 142, 0.1); color: #11998e; }
        .message-item.error { background: rgba(235, 51, 73, 0.1); color: #eb3349; }
        .info-box {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .info-box h4 { margin-bottom: 8px; color: #333; }
        .info-box p { font-size: 13px; color: #666; line-height: 1.6; }
        .steps { margin-bottom: 20px; }
        .steps ol { padding-left: 20px; }
        .steps li { margin-bottom: 10px; font-size: 14px; color: #555; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 系统安装修复工具</h1>
            <p>用于修复登录密码问题和API 404问题</p>
        </div>
        <div class="content">
            <?php if (!empty($successes)): ?>
                <div class="message-list">
                    <?php foreach ($successes as $msg): ?>
                        <div class="message-item success"><?= $msg ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="message-list">
                    <?php foreach ($errors as $msg): ?>
                        <div class="message-item error"><?= $msg ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="info-box">
                    <h4>📋 问题诊断</h4>
                    <p><strong>问题1：</strong>默认密码哈希 `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` 对应的明文是 `password`，而非 `admin123`。<br>
                    <strong>问题2：</strong>API接口404可能是因为缺少 .htaccess URL重写配置或Web服务器配置问题。</p>
                </div>
                <div class="info-box">
                    <h4>✅ 已自动处理</h4>
                    <p>- 已在 `public/.htaccess` 创建URL重写配置文件<br>
                    - 请按照以下步骤手动修复密码问题</p>
                </div>
            </div>

            <div class="section">
                <h3 class="section-title">步骤1：检查数据库连接并导入结构</h3>
                <p style="font-size:13px;color:#666;margin-bottom:16px;">确保 `config/config.php` 中的数据库配置正确，然后点击测试：</p>
                <form method="post">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="btn btn-primary">测试数据库连接并导入结构</button>
                </form>
            </div>

            <div class="section">
                <h3 class="section-title">步骤2：更新管理员密码</h3>
                <p style="font-size:13px;color:#666;margin-bottom:16px;">设置管理员账号的新密码：</p>
                <form method="post">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group">
                        <label>新密码</label>
                        <input type="text" name="new_password" value="admin123" placeholder="请输入新密码">
                    </div>
                    <button type="submit" class="btn btn-success">更新密码为 admin123</button>
                </form>
            </div>

            <div class="section">
                <h3 class="section-title">步骤3：验证API路径</h3>
                <form method="post">
                    <input type="hidden" name="action" value="test_api">
                    <button type="submit" class="btn btn-info">检查API路径配置</button>
                </form>
            </div>

            <div class="section">
                <h3 class="section-title">📖 手动修复指南</h3>
                <div class="steps">
                    <ol>
                        <li><strong>导入数据库结构：</strong><br>
                        在MySQL中执行 `database/schema.sql` 文件</li>
                        <li><strong>修改默认密码：</strong><br>
                        执行以下SQL更新密码：<br>
                        <code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;">UPDATE admins SET password = '$2y$10$your_hash_here' WHERE username = 'admin';</code><br>
                        或使用本页面的"更新密码"功能</li>
                        <li><strong>配置Web服务器：</strong><br>
                        - Apache: 确保 `public/.htaccess` 生效，`mod_rewrite` 已启用<br>
                        - Nginx: 需要配置URL重写（参考README.md）</li>
                        <li><strong>测试：</strong><br>
                        访问 <code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;">/admin</code> 使用 admin / admin123 登录</li>
                    </ol>
                </div>
            </div>

            <div class="info-box" style="background:rgba(102,126,234,0.05);border-left:4px solid #667eea;">
                <h4>💡 快速测试</h4>
                <p>配置完成后，请测试：<br>
                1. 管理后台: <code>http://your-domain/admin</code><br>
                2. API登录: <code>POST http://your-domain/api/auth/login</code><br>
                &nbsp;&nbsp;&nbsp;Body: <code>{"username":"admin","password":"admin123"}</code></p>
            </div>
        </div>
    </div>
</body>
</html>
