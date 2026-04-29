-- 修复管理员密码
-- 使用方法：
-- 1. 先运行 php -r "echo password_hash('admin123', PASSWORD_BCRYPT);" 生成哈希
-- 2. 将生成的哈希替换下面的 'your_password_hash_here'
-- 3. 执行此SQL文件

-- 示例：admin123 的bcrypt哈希 (每次生成不同)
-- UPDATE admins SET password = '$2y$10$...' WHERE username = 'admin';

-- 注意：由于bcrypt每次生成的哈希不同，请使用以下方法之一：

-- 方法1：使用PHP生成哈希并更新
-- 运行：
-- <?php
-- $password = 'admin123';
-- $hash = password_hash($password, PASSWORD_BCRYPT);
-- echo "UPDATE admins SET password = '$hash' WHERE username = 'admin';\n";
-- ?>

-- 方法2：使用已生成的示例哈希 (password 'admin123')
-- 以下是 admin123 的几个示例哈希 (任选其一):
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi 对应 'password' (错误！)
-- 请使用 install.php 页面来正确设置密码

-- 临时使用 (请务必替换！)：
-- 先使用 'password' 登录，然后在管理后台修改密码

-- 或者直接执行（将 YOUR_HASH_HERE 替换为实际哈希）：
-- UPDATE admins SET password = 'YOUR_HASH_HERE' WHERE username = 'admin';

-- 查看当前管理员
SELECT id, username, password FROM admins WHERE username = 'admin';
