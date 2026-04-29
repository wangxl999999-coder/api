# API管理系统

一个功能完善的PHP API接口和管理后台系统，采用JWT鉴权方式。

## 功能特性

- 🔐 **JWT鉴权系统**：完整的JWT Token生成、验证、刷新机制
- 👥 **用户管理**：用户增删改查、状态管理
- 👨‍💼 **管理员管理**：管理员账号管理、角色分配
- 📋 **登录日志**：完整的登录记录追踪
- 🔐 **权限角色管理**：RBAC权限模型，灵活的权限分配
- ⚙️ **配置管理**：系统配置项管理
- 🖥️ **管理后台**：完整的Web管理界面

## 技术栈

- **后端**：PHP 7.4+ (原生MVC架构)
- **数据库**：MySQL 5.7+
- **前端**：原生 HTML + CSS + JavaScript
- **认证方式**：JWT (JSON Web Token)

## 目录结构

```
api/
├── config/              # 配置文件
│   └── config.php       # 系统配置
├── core/                # 核心类库
│   ├── Autoload.php     # 自动加载
│   ├── Controller.php   # 控制器基类
│   ├── Database.php     # 数据库操作类
│   ├── JWT.php          # JWT认证类
│   ├── Model.php        # 模型基类
│   └── Router.php       # 路由类
├── app/                 # 应用代码
│   ├── Controllers/     # 控制器
│   ├── Models/          # 模型
│   └── Middlewares/     # 中间件
├── routes/              # 路由配置
│   ├── api.php          # API路由
│   └── web.php          # Web路由
├── database/            # 数据库
│   └── schema.sql       # 数据库结构
├── docs/                # 文档
│   └── API.md           # API接口文档
├── public/              # 公开目录
│   ├── admin/           # 管理后台
│   ├── uploads/         # 上传文件目录
│   └── index.php        # 入口文件
└── README.md
```

## 安装部署

### 1. 环境要求

- PHP 7.4+
- MySQL 5.7+
- Apache 或 Nginx
- PDO 扩展
- JSON 扩展

### 2. 安装步骤

1. **克隆项目**
```bash
git clone <repository-url>
cd api
```

2. **导入数据库**
```bash
mysql -u root -p < database/schema.sql
```

3. **配置数据库连接**

修改 `config/config.php` 文件：

```php
return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'api_system',
        'username' => 'root',
        'password' => 'your_password', // 修改为你的密码
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => 'your-secret-key', // 修改为随机字符串
        'algorithm' => 'HS256',
        'expire' => 7200,
        'refresh_expire' => 604800,
    ],
];
```

4. **配置Web服务器**

**Apache：**

确保 `public` 目录为网站根目录，或配置 `.htaccess`：

```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php/$1 [L]
```

**Nginx：**

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. 默认账号

- 用户名：`admin`
- 密码：`admin123`

## 访问地址

- 管理后台：`http://localhost/admin`
- API接口：`http://localhost/api/*`

## API接口

### 认证相关

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/auth/login | POST | 登录获取Token |
| /api/auth/logout | POST | 退出登录 |
| /api/auth/refresh | POST | 刷新Token |
| /api/auth/user | GET | 获取当前用户信息 |

### JWT使用示例

```javascript
// 1. 登录获取Token
const loginRes = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'admin',
        password: 'admin123'
    })
});

const { token } = (await loginRes.json()).data;

// 2. 使用Token访问受保护接口
const usersRes = await fetch('/api/users', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    }
});

const users = (await usersRes.json()).data;
```

详细API文档请查看 [docs/API.md](docs/API.md)

## 功能模块

### 1. 用户管理
- 用户列表分页查询
- 新增、编辑、删除用户
- 启用/禁用用户账号
- 搜索筛选功能

### 2. 管理员管理
- 管理员列表管理
- 角色分配
- 超级管理员标识
- 登录日志记录

### 3. 角色管理
- 角色增删改查
- 权限分配
- 角色状态管理

### 4. 权限管理
- 权限树形结构
- 菜单、按钮、接口权限
- 权限关联角色

### 5. 登录日志
- 登录记录查询
- 登录成功/失败统计
- 日志清理功能

### 6. 配置管理
- 配置分组管理
- 基本配置、上传配置、邮件配置
- 配置项动态添加

## 安全特性

1. **JWT认证**：无状态Token认证机制
2. **Token刷新**：支持刷新Token延长登录有效期
3. **密码加密**：使用 bcrypt 加密存储
4. **权限控制**：基于RBAC的细粒度权限控制
5. **登录日志**：完整的登录审计记录
6. **登录限制**：可配置登录尝试次数限制

## 响应格式

所有接口统一返回JSON格式：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        // 返回数据
    }
}
```

**错误码说明：**
- `200` - 成功
- `400` - 请求参数错误
- `401` - 未授权（Token无效或过期）
- `403` - 无权限访问
- `404` - 资源不存在
- `500` - 服务器内部错误

## 更新日志

### v1.0.0 (2026-04-29)
- 初始版本发布
- 实现JWT鉴权机制
- 实现用户管理、管理员管理
- 实现权限角色管理
- 实现登录日志记录
- 实现基本配置管理
- 实现管理后台界面

## License

MIT License
