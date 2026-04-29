# API 接口文档

## 概述

本文档详细说明了API管理系统的所有接口，包括JWT鉴权方式和接口使用示例。

### 基础URL
```
http://localhost
```

### 响应格式
所有接口返回JSON格式：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {}
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | 状态码，200表示成功，其他表示失败 |
| message | string | 响应消息 |
| data | object/array | 返回数据 |

---

## JWT 鉴权说明

### 1. 获取Token

**接口：** `POST /api/auth/login`

**请求参数：**
```json
{
    "username": "admin",
    "password": "admin123"
}
```

**响应示例：**
```json
{
    "code": 200,
    "message": "登录成功",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 7200,
        "user_info": {
            "id": 1,
            "username": "admin",
            "nickname": "超级管理员",
            "avatar": "",
            "email": "",
            "is_super": 1,
            "role_id": 1
        }
    }
}
```

### 2. 使用Token访问受保护接口

在请求Header中添加Authorization字段：

```
Authorization: Bearer {token}
```

**示例（使用curl）：**
```bash
curl -X GET "http://localhost/api/users" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json"
```

**示例（使用JavaScript Fetch）：**
```javascript
const token = localStorage.getItem('token');
fetch('/api/users', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});
```

### 3. 刷新Token

当Access Token过期时，使用Refresh Token获取新的Token：

**接口：** `POST /api/auth/refresh`

**请求参数：**
```json
{
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**响应示例：**
```json
{
    "code": 200,
    "message": "令牌刷新成功",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 7200
    }
}
```

### 4. JWT Token 结构说明

Token由三部分组成，通过`.`分隔：

```
Header.Payload.Signature
```

**Header（头部）：**
```json
{
    "typ": "JWT",
    "alg": "HS256"
}
```

**Payload（载荷）：**
```json
{
    "iss": "http://localhost",
    "aud": "http://localhost",
    "iat": 1714395600,
    "exp": 1714402800,
    "sub": 1,
    "type": "access"
}
```

| 字段 | 说明 |
|------|------|
| iss | 签发者 |
| aud | 接收者 |
| iat | 签发时间 |
| exp | 过期时间 |
| sub | 用户ID |
| type | Token类型：access/refresh |

**Signature（签名）：**
使用HS256算法对Header和Payload进行签名，确保Token不被篡改。

---

## 接口列表

### 认证接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/auth/login | POST | 登录获取Token | 否 |
| /api/auth/logout | POST | 退出登录 | 是 |
| /api/auth/refresh | POST | 刷新Token | 否 |
| /api/auth/user | GET | 获取当前用户信息 | 是 |
| /api/auth/password | PUT | 修改密码 | 是 |
| /api/auth/profile | PUT | 更新个人信息 | 是 |

---

### 管理员接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/admins | GET | 获取管理员列表 | 是 |
| /api/admins/options | GET | 获取角色选项 | 是 |
| /api/admins/{id} | GET | 获取管理员详情 | 是 |
| /api/admins | POST | 创建管理员 | 是 |
| /api/admins/{id} | PUT | 更新管理员 | 是 |
| /api/admins/{id} | DELETE | 删除管理员 | 是 |
| /api/admins/{id}/toggle-status | PUT | 切换管理员状态 | 是 |

---

### 用户接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/users | GET | 获取用户列表 | 是 |
| /api/users/{id} | GET | 获取用户详情 | 是 |
| /api/users | POST | 创建用户 | 是 |
| /api/users/{id} | PUT | 更新用户 | 是 |
| /api/users/{id} | DELETE | 删除用户 | 是 |
| /api/users/batch-delete | POST | 批量删除用户 | 是 |
| /api/users/{id}/toggle-status | PUT | 切换用户状态 | 是 |

---

### 角色接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/roles | GET | 获取角色列表 | 是 |
| /api/roles/all | GET | 获取所有角色（下拉框用） | 是 |
| /api/roles/{id} | GET | 获取角色详情 | 是 |
| /api/roles | POST | 创建角色 | 是 |
| /api/roles/{id} | PUT | 更新角色 | 是 |
| /api/roles/{id} | DELETE | 删除角色 | 是 |
| /api/roles/{id}/toggle-status | PUT | 切换角色状态 | 是 |

---

### 权限接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/permissions | GET | 获取权限列表 | 是 |
| /api/permissions/tree | GET | 获取权限树形结构 | 是 |
| /api/permissions/menu | GET | 获取当前用户菜单 | 是 |
| /api/permissions/options | GET | 获取权限选项 | 是 |
| /api/permissions/{id} | GET | 获取权限详情 | 是 |
| /api/permissions | POST | 创建权限 | 是 |
| /api/permissions/{id} | PUT | 更新权限 | 是 |
| /api/permissions/{id} | DELETE | 删除权限 | 是 |

---

### 登录日志接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/login-logs | GET | 获取登录日志列表 | 是 |
| /api/login-logs/statistics | GET | 获取日志统计 | 是 |
| /api/login-logs/chart | GET | 获取图表数据 | 是 |
| /api/login-logs/{id} | GET | 获取日志详情 | 是 |
| /api/login-logs/{id} | DELETE | 删除日志 | 是 |
| /api/login-logs/batch-delete | POST | 批量删除日志 | 是 |
| /api/login-logs/clear | POST | 清理过期日志 | 是 |

---

### 配置接口

| 接口 | 方法 | 说明 | 是否需要鉴权 |
|------|------|------|-------------|
| /api/configs | GET | 获取配置列表 | 是 |
| /api/configs/all | GET | 获取所有配置键值对 | 是 |
| /api/configs/groups | GET | 获取配置分组 | 是 |
| /api/configs/group/{groupName} | GET | 按分组获取配置 | 是 |
| /api/configs/{keyName} | GET | 获取配置详情 | 是 |
| /api/configs | POST | 创建配置 | 是 |
| /api/configs/batch | PUT | 批量更新配置 | 是 |
| /api/configs/{keyName} | PUT | 更新配置 | 是 |
| /api/configs/{keyName} | DELETE | 删除配置 | 是 |
| /api/configs/upload | POST | 上传文件 | 是 |

---

## 接口使用示例

### 示例1：完整的登录流程

```javascript
// 1. 登录获取Token
async function login(username, password) {
    const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    });
    const result = await response.json();
    
    if (result.code === 200) {
        // 保存Token
        localStorage.setItem('token', result.data.token);
        localStorage.setItem('refresh_token', result.data.refresh_token);
        return result.data;
    } else {
        throw new Error(result.message);
    }
}

// 2. 使用Token访问受保护接口
async function getUsers() {
    const token = localStorage.getItem('token');
    const response = await fetch('/api/users', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });
    return await response.json();
}

// 3. 刷新Token
async function refreshToken() {
    const refreshToken = localStorage.getItem('refresh_token');
    const response = await fetch('/api/auth/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: refreshToken })
    });
    const result = await response.json();
    
    if (result.code === 200) {
        localStorage.setItem('token', result.data.token);
        localStorage.setItem('refresh_token', result.data.refresh_token);
        return true;
    }
    return false;
}

// 4. 封装带自动刷新Token的请求
async function request(url, options = {}) {
    const token = localStorage.getItem('token');
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    let response = await fetch(url, {
        ...options,
        headers
    });

    let result = await response.json();

    // 如果Token过期，尝试刷新
    if (response.status === 401 && result.code === 401) {
        const refreshed = await refreshToken();
        if (refreshed) {
            // 使用新Token重新请求
            return request(url, options);
        } else {
            // 刷新失败，跳转到登录页
            localStorage.removeItem('token');
            localStorage.removeItem('refresh_token');
            window.location.href = '/login';
        }
    }

    return result;
}
```

### 示例2：分页查询用户

```javascript
async function getUsers(page = 1, pageSize = 10, keywords = '', status = '') {
    const params = new URLSearchParams({
        page,
        page_size: pageSize
    });

    if (keywords) {
        params.append('keywords', keywords);
    }
    if (status !== '') {
        params.append('status', status);
    }

    return await request(`/api/users?${params.toString()}`);
}

// 使用示例
getUsers(1, 10, '张三', '1').then(result => {
    console.log('用户列表:', result.data.items);
    console.log('分页信息:', result.data.pagination);
});
```

### 示例3：创建管理员

```javascript
async function createAdmin(data) {
    return await request('/api/admins', {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

// 使用示例
createAdmin({
    username: 'newadmin',
    password: 'password123',
    nickname: '新管理员',
    email: 'admin@example.com',
    role_id: 2,
    status: 1
}).then(result => {
    if (result.code === 200) {
        console.log('创建成功，ID:', result.data.id);
    }
});
```

### 示例4：更新角色权限

```javascript
async function updateRolePermissions(roleId, permissionIds) {
    return await request(`/api/roles/${roleId}`, {
        method: 'PUT',
        body: JSON.stringify({
            permission_ids: permissionIds
        })
    });
}

// 使用示例
updateRolePermissions(2, [1, 2, 3, 7, 8]).then(result => {
    if (result.code === 200) {
        console.log('权限更新成功');
    }
});
```

### 示例5：批量更新配置

```javascript
async function updateConfigs(configs) {
    return await request('/api/configs/batch', {
        method: 'PUT',
        body: JSON.stringify(configs)
    });
}

// 使用示例
updateConfigs({
    site_name: '新的网站名称',
    register_enabled: '1',
    max_login_attempts: '5'
}).then(result => {
    if (result.code === 200) {
        console.log('配置更新成功');
    }
});
```

---

## 常见错误码

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权（Token无效或过期） |
| 403 | 无权限访问 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

---

## 项目结构

```
api/
├── config/
│   └── config.php          # 配置文件
├── core/
│   ├── Autoload.php        # 自动加载
│   ├── Controller.php      # 控制器基类
│   ├── Database.php        # 数据库类
│   ├── JWT.php             # JWT认证类
│   ├── Model.php           # 模型基类
│   └── Router.php          # 路由类
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php       # 认证控制器
│   │   ├── AdminController.php      # 管理员控制器
│   │   ├── UserController.php       # 用户控制器
│   │   ├── RoleController.php       # 角色控制器
│   │   ├── PermissionController.php # 权限控制器
│   │   ├── LoginLogController.php   # 登录日志控制器
│   │   └── ConfigController.php     # 配置控制器
│   ├── Models/
│   │   ├── Admin.php        # 管理员模型
│   │   ├── User.php         # 用户模型
│   │   ├── Role.php         # 角色模型
│   │   ├── Permission.php   # 权限模型
│   │   ├── LoginLog.php     # 登录日志模型
│   │   └── Config.php       # 配置模型
│   └── Middlewares/
│       └── AuthMiddleware.php # 认证中间件
├── routes/
│   ├── api.php              # API路由
│   └── web.php              # Web路由
├── database/
│   └── schema.sql           # 数据库结构
├── docs/
│   └── API.md               # API文档
├── public/
│   ├── admin/               # 管理后台
│   │   ├── index.html
│   │   ├── css/
│   │   │   └── style.css
│   │   └── js/
│   │       └── app.js
│   ├── uploads/             # 上传文件目录
│   └── index.php            # 入口文件
└── README.md
```

---

## 安装说明

### 1. 环境要求
- PHP 7.4+
- MySQL 5.7+
- Apache 或 Nginx
- PDO扩展
- JSON扩展

### 2. 数据库配置

1. 导入数据库结构：
```bash
mysql -u root -p < database/schema.sql
```

2. 修改配置文件 `config/config.php` 中的数据库连接信息：
```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'api_system',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
],
```

### 3. Web服务器配置

**Apache (.htaccess)：**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php/$1 [L]
```

**Nginx：**
```nginx
location / {
    try_files $uri $uri/ /public/index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 4. 默认账号

- 用户名：`admin`
- 密码：`admin123`

---

## 安全建议

1. **修改JWT密钥**：修改 `config/config.php` 中的 `jwt.secret` 为随机字符串
2. **开启HTTPS**：生产环境建议使用HTTPS
3. **修改默认密码**：首次登录后修改默认管理员密码
4. **限制登录次数**：可配置 `max_login_attempts` 参数限制登录尝试次数
5. **定期清理日志**：建议定期清理登录日志，避免数据库过大

---

## 更新日志

### v1.0.0 (2026-04-29)
- 初始版本发布
- 实现JWT鉴权机制
- 实现用户管理、管理员管理
- 实现权限角色管理
- 实现登录日志记录
- 实现基本配置管理
- 实现管理后台界面
