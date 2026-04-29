-- 数据库: api_system
-- 创建时间: 2026-04-29

CREATE DATABASE IF NOT EXISTS api_system DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE api_system;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    nickname VARCHAR(100) NOT NULL COMMENT '昵称',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    phone VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    password VARCHAR(255) NOT NULL COMMENT '密码',
    avatar VARCHAR(255) DEFAULT NULL COMMENT '头像',
    status TINYINT DEFAULT 1 COMMENT '状态: 1正常 0禁用',
    last_login_at DATETIME DEFAULT NULL COMMENT '最后登录时间',
    last_login_ip VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_username (username),
    KEY idx_email (email),
    KEY idx_phone (phone),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 管理员表
CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    nickname VARCHAR(100) NOT NULL COMMENT '昵称',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    password VARCHAR(255) NOT NULL COMMENT '密码',
    avatar VARCHAR(255) DEFAULT NULL COMMENT '头像',
    role_id BIGINT UNSIGNED DEFAULT NULL COMMENT '角色ID',
    status TINYINT DEFAULT 1 COMMENT '状态: 1正常 0禁用',
    is_super TINYINT DEFAULT 0 COMMENT '是否超级管理员: 1是 0否',
    last_login_at DATETIME DEFAULT NULL COMMENT '最后登录时间',
    last_login_ip VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_username (username),
    KEY idx_role_id (role_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 角色表
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '角色名称',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    description VARCHAR(255) DEFAULT NULL COMMENT '角色描述',
    status TINYINT DEFAULT 1 COMMENT '状态: 1正常 0禁用',
    sort INT DEFAULT 0 COMMENT '排序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_code (code),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

-- 权限表
CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED DEFAULT 0 COMMENT '父级ID',
    name VARCHAR(100) NOT NULL COMMENT '权限名称',
    code VARCHAR(100) NOT NULL COMMENT '权限标识',
    type TINYINT DEFAULT 1 COMMENT '类型: 1菜单 2按钮 3接口',
    path VARCHAR(255) DEFAULT NULL COMMENT '路由路径',
    icon VARCHAR(100) DEFAULT NULL COMMENT '图标',
    component VARCHAR(255) DEFAULT NULL COMMENT '组件路径',
    sort INT DEFAULT 0 COMMENT '排序',
    status TINYINT DEFAULT 1 COMMENT '状态: 1显示 0隐藏',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_parent_id (parent_id),
    KEY idx_code (code),
    KEY idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
    permission_id BIGINT UNSIGNED NOT NULL COMMENT '权限ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_role_permission (role_id, permission_id),
    KEY idx_role_id (role_id),
    KEY idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED DEFAULT NULL COMMENT '管理员ID',
    username VARCHAR(50) NOT NULL COMMENT '登录用户名',
    ip VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT '用户代理',
    login_type TINYINT DEFAULT 1 COMMENT '登录类型: 1管理员 2用户',
    status TINYINT DEFAULT 1 COMMENT '状态: 1成功 0失败',
    message VARCHAR(255) DEFAULT NULL COMMENT '登录消息',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_id (admin_id),
    KEY idx_username (username),
    KEY idx_login_type (login_type),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

-- 配置表
CREATE TABLE IF NOT EXISTS configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(50) NOT NULL DEFAULT 'basic' COMMENT '配置分组',
    key_name VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键名',
    value TEXT COMMENT '配置值',
    name VARCHAR(100) NOT NULL COMMENT '配置名称',
    description VARCHAR(255) DEFAULT NULL COMMENT '配置描述',
    type VARCHAR(20) DEFAULT 'text' COMMENT '类型: text, number, select, checkbox, textarea, image',
    options TEXT DEFAULT NULL COMMENT '选项值(JSON格式)',
    sort INT DEFAULT 0 COMMENT '排序',
    status TINYINT DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_group_name (group_name),
    KEY idx_key_name (key_name),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='配置表';

-- 初始化数据

-- 创建默认角色
INSERT INTO roles (name, code, description, sort) VALUES 
('超级管理员', 'super_admin', '拥有系统所有权限', 1),
('普通管理员', 'admin', '拥有部分管理权限', 2),
('运营管理员', 'operator', '运营相关权限', 3);

-- 创建默认超级管理员 (密码: admin123)
INSERT INTO admins (username, nickname, password, role_id, is_super, status) VALUES 
('admin', '超级管理员', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1);

-- 创建权限数据 (显式指定ID以确保parent_id引用正确)
INSERT INTO permissions (id, parent_id, name, code, type, path, icon, component, sort) VALUES 
-- 系统管理 (id=1)
(1, 0, '系统管理', 'system', 1, '/system', 'setting', NULL, 1),
-- 管理员管理 (id=2, parent_id=1)
(2, 1, '管理员管理', 'admin', 1, '/system/admin', 'user', 'system/admin/index', 1),
(3, 2, '查看管理员列表', 'admin:list', 2, NULL, NULL, NULL, 1),
(4, 2, '添加管理员', 'admin:add', 2, NULL, NULL, NULL, 2),
(5, 2, '编辑管理员', 'admin:edit', 2, NULL, NULL, NULL, 3),
(6, 2, '删除管理员', 'admin:delete', 2, NULL, NULL, NULL, 4),
-- 角色管理 (id=7, parent_id=1)
(7, 1, '角色管理', 'role', 1, '/system/role', 'team', 'system/role/index', 2),
(8, 7, '查看角色列表', 'role:list', 2, NULL, NULL, NULL, 1),
(9, 7, '添加角色', 'role:add', 2, NULL, NULL, NULL, 2),
(10, 7, '编辑角色', 'role:edit', 2, NULL, NULL, NULL, 3),
(11, 7, '删除角色', 'role:delete', 2, NULL, NULL, NULL, 4),
(12, 7, '分配权限', 'role:permission', 2, NULL, NULL, NULL, 5),
-- 权限管理 (id=13, parent_id=1)
(13, 1, '权限管理', 'permission', 1, '/system/permission', 'key', 'system/permission/index', 3),
(14, 13, '查看权限列表', 'permission:list', 2, NULL, NULL, NULL, 1),
(15, 13, '添加权限', 'permission:add', 2, NULL, NULL, NULL, 2),
(16, 13, '编辑权限', 'permission:edit', 2, NULL, NULL, NULL, 3),
(17, 13, '删除权限', 'permission:delete', 2, NULL, NULL, NULL, 4),
-- 日志管理 (id=18)
(18, 0, '日志管理', 'log', 1, '/log', 'file', NULL, 2),
(19, 18, '登录日志', 'login_log', 1, '/log/login', 'login', 'log/login/index', 1),
(20, 19, '查看登录日志', 'login_log:list', 2, NULL, NULL, NULL, 1),
(21, 19, '删除登录日志', 'login_log:delete', 2, NULL, NULL, NULL, 2),
-- 配置管理 (id=22)
(22, 0, '配置管理', 'config', 1, '/config', 'setting', NULL, 3),
(23, 22, '基本配置', 'basic_config', 1, '/config/basic', 'setting', 'config/basic/index', 1),
(24, 23, '查看配置', 'config:list', 2, NULL, NULL, NULL, 1),
(25, 23, '编辑配置', 'config:edit', 2, NULL, NULL, NULL, 2),
-- 用户管理 (id=26)
(26, 0, '用户管理', 'user_manage', 1, '/user', 'user', NULL, 4),
(27, 26, '用户列表', 'user', 1, '/user/list', 'user', 'user/index', 1),
(28, 27, '查看用户列表', 'user:list', 2, NULL, NULL, NULL, 1),
(29, 27, '添加用户', 'user:add', 2, NULL, NULL, NULL, 2),
(30, 27, '编辑用户', 'user:edit', 2, NULL, NULL, NULL, 3),
(31, 27, '删除用户', 'user:delete', 2, NULL, NULL, NULL, 4),
(32, 27, '查看用户详情', 'user:view', 2, NULL, NULL, NULL, 5);

-- 超级管理员角色关联权限 (全部权限)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- 初始化配置数据
INSERT INTO configs (group_name, key_name, value, name, description, type, sort) VALUES 
('basic', 'site_name', 'API管理系统', '网站名称', '网站显示名称', 'text', 1),
('basic', 'site_logo', '', '网站Logo', '网站Logo图片地址', 'image', 2),
('basic', 'site_description', '一个功能完善的API接口和管理后台系统', '网站描述', '网站简介描述', 'textarea', 3),
('basic', 'register_enabled', '1', '开启注册', '是否允许用户注册', 'switch', 4),
('basic', 'max_login_attempts', '5', '最大登录尝试次数', '密码错误次数限制', 'number', 5),
('basic', 'login_lock_duration', '30', '登录锁定时间(分钟)', '登录失败后锁定时间', 'number', 6),
('upload', 'upload_max_size', '10485760', '最大上传大小', '单个文件最大上传大小(字节)', 'number', 1),
('upload', 'upload_allowed_types', 'jpg,jpeg,png,gif,doc,docx,xls,xlsx,pdf', '允许上传类型', '允许的文件扩展名', 'text', 2),
('email', 'email_smtp_host', '', 'SMTP服务器', '邮件发送SMTP服务器地址', 'text', 1),
('email', 'email_smtp_port', '465', 'SMTP端口', 'SMTP服务器端口', 'number', 2),
('email', 'email_smtp_secure', 'ssl', 'SMTP加密', '加密方式: ssl/tls', 'select', 3),
('email', 'email_smtp_username', '', 'SMTP用户名', '邮件发送账号', 'text', 4),
('email', 'email_smtp_password', '', 'SMTP密码', '邮件发送密码/授权码', 'text', 5),
('email', 'email_from_address', '', '发件人邮箱', '发件人邮箱地址', 'text', 6),
('email', 'email_from_name', '', '发件人名称', '发件人显示名称', 'text', 7);
