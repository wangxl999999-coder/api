<?php
/**
 * 管理员密码重置脚本
 * 使用方法：
 * 1. 确保数据库配置正确
 * 2. 访问此页面：http://your-domain/reset_password.php
 * 3. 输入新密码并提交
 */

header('Content-Type: text/html; charset=utf-8');

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die('错误：找不到配置文件，请先配置 config/config.php');
}

$config = require $configFile;
$dbConfig = $config['database'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = '请输入密码和确认密码';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = '两次输入的密码不一致';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = '密码长度至少6位';
        $messageType = 'error';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
            $stmt->execute(['password' => $hash]);
            
            if ($stmt->rowCount() > 0) {
                $message = "密码已成功更新为：{$newPassword}";
                $messageType = 'success';
            } else {
                $message = '没有找到管理员账号，可能是数据库未初始化';
                $messageType = 'error';
            }
            
        } catch (PDOException $e) {
            $message = '数据库错误：' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置管理员密码</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 500px;
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
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .message.success { background: rgba(17, 153, 142, 0.1); color: #11998e; border-left: 4px solid #11998e; }
        .message.error { background: rgba(235, 51, 73, 0.1); color: #eb3349; border-left: 4px solid #eb3349; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
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
            width: 100%;
            padding: 14px 24px;
            font-size: 16px;
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
        .info-box {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-box h4 { margin-bottom: 8px; color: #333; font-size: 14px; }
        .info-box p { font-size: 13px; color: #666; line-height: 1.6; }
        .info-box code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            font-size: 13px;
            color: #666;
        }
        .footer a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 重置管理员密码</h1>
            <p>为管理员账号设置新密码</p>
        </div>
        <div class="content">
            <div class="info-box">
                <h4>📋 当前状态</h4>
                <p>数据库配置：<code><?php echo $dbConfig['host']; ?>:<?php echo $dbConfig['port']; ?></code><br>
                数据库名：<code><?php echo $dbConfig['database']; ?></code><br>
                用户名：<code><?php echo $dbConfig['username']; ?></code></p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>新密码</label>
                    <input type="text" name="new_password" placeholder="请输入新密码 (默认: admin123)" value="admin123">
                </div>
                <div class="form-group">
                    <label>确认密码</label>
                    <input type="text" name="confirm_password" placeholder="请再次输入新密码" value="admin123">
                </div>
                <button type="submit" class="btn btn-primary">更新密码为 admin123</button>
            </form>
        </div>
        <div class="footer">
            <p>重置完成后，请：</p>
            <p>1. 删除此安全文件: <code>reset_password.php</code></p>
            <p>2. 访问 <a href="/admin">/admin</a> 使用新密码登录</p>
        </div>
    </div>
</body>
</html>
