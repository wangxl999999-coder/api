const App = {
    token: localStorage.getItem('token') || '',
    refreshToken: localStorage.getItem('refresh_token') || '',
    user: null,
    menus: [],
    currentPage: 'dashboard',
    configGroups: [],
    currentConfigGroup: 'basic',

    init() {
        this.bindEvents();
        if (this.token) {
            this.getUserInfo();
        } else {
            this.showPage('login');
        }
    },

    bindEvents() {
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.login();
        });

        document.getElementById('logout-btn').addEventListener('click', () => {
            this.logout();
        });

        document.getElementById('modal-close').addEventListener('click', () => {
            this.closeModal();
        });

        document.querySelector('.modal-overlay').addEventListener('click', () => {
            this.closeModal();
        });

        document.getElementById('config-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveConfigs();
        });
    },

    showPage(page) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.content-page').forEach(p => p.classList.remove('active'));

        if (page === 'login') {
            document.getElementById('login-page').classList.add('active');
        } else {
            document.getElementById('main-page').classList.add('active');
            const pageElement = document.getElementById(`${page}-page`);
            if (pageElement) {
                pageElement.classList.add('active');
            }
        }

        this.currentPage = page;
    },

    navigate(page) {
        this.showPage(page);
        this.updatePageTitle(page);
        this.loadPageData(page);
    },

    updatePageTitle(page) {
        const titles = {
            dashboard: '控制台',
            users: '用户管理',
            admins: '管理员管理',
            roles: '角色管理',
            permissions: '权限管理',
            'login-logs': '登录日志',
            configs: '系统配置'
        };
        document.getElementById('page-title').textContent = titles[page] || page;
    },

    async login() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        if (!username || !password) {
            this.toast('请输入用户名和密码', 'error');
            return;
        }

        try {
            const res = await this.request('/api/auth/login', 'POST', { username, password });
            
            if (res.code === 200) {
                this.token = res.data.token;
                this.refreshToken = res.data.refresh_token;
                this.user = res.data.user_info;

                localStorage.setItem('token', this.token);
                localStorage.setItem('refresh_token', this.refreshToken);

                this.toast('登录成功', 'success');
                this.getUserInfo();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('登录失败: ' + err.message, 'error');
        }
    },

    async logout() {
        try {
            await this.request('/api/auth/logout', 'POST');
        } catch (err) {
            console.error('Logout error:', err);
        }

        this.token = '';
        this.refreshToken = '';
        this.user = null;
        localStorage.removeItem('token');
        localStorage.removeItem('refresh_token');

        this.showPage('login');
        this.toast('已退出登录', 'success');
    },

    async getUserInfo() {
        try {
            const res = await this.request('/api/auth/user', 'GET');
            
            if (res.code === 200) {
                this.user = res.data;
                this.menus = res.data.menus || [];

                document.getElementById('user-name').textContent = this.user.nickname || this.user.username;

                this.renderSidebar();
                this.showPage('dashboard');
                this.loadDashboard();
            } else {
                this.token = '';
                localStorage.removeItem('token');
                this.showPage('login');
            }
        } catch (err) {
            this.token = '';
            localStorage.removeItem('token');
            this.showPage('login');
        }
    },

    renderSidebar() {
        const menu = document.getElementById('sidebar-menu');
        const defaultMenus = [
            { id: 1, name: '控制台', code: 'dashboard', path: '/dashboard', type: 1, icon: '📊' },
            { id: 2, name: '用户管理', code: 'user_manage', type: 1, icon: '👥', children: [
                { id: 3, name: '用户列表', code: 'user', path: '/users', type: 2, icon: '•' }
            ]},
            { id: 4, name: '系统管理', code: 'system', type: 1, icon: '⚙️', children: [
                { id: 5, name: '管理员管理', code: 'admin', path: '/admins', type: 2, icon: '•' },
                { id: 6, name: '角色管理', code: 'role', path: '/roles', type: 2, icon: '•' },
                { id: 7, name: '权限管理', code: 'permission', path: '/permissions', type: 2, icon: '•' }
            ]},
            { id: 8, name: '日志管理', code: 'log', type: 1, icon: '📋', children: [
                { id: 9, name: '登录日志', code: 'login_log', path: '/login-logs', type: 2, icon: '•' }
            ]},
            { id: 10, name: '配置管理', code: 'config', type: 1, icon: '🔧', children: [
                { id: 11, name: '系统配置', code: 'basic_config', path: '/configs', type: 2, icon: '•' }
            ]}
        ];

        menu.innerHTML = this.renderMenuItems(defaultMenus);

        menu.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const path = item.dataset.path;
                const hasSubmenu = item.querySelector('.submenu');

                if (hasSubmenu) {
                    e.preventDefault();
                    hasSubmenu.classList.toggle('active');
                } else if (path) {
                    const pageName = path.replace('/', '').replace('/', '-');
                    this.navigate(pageName);
                    
                    menu.querySelectorAll('li').forEach(li => li.classList.remove('active'));
                    item.parentElement.classList.add('active');
                }
            });
        });
    },

    renderMenuItems(items, level = 0) {
        return items.map(item => {
            const hasChildren = item.children && item.children.length > 0;
            const pageName = item.path ? item.path.replace('/', '').replace('/', '-') : '';
            
            let html = `<li class="menu-item-container" data-code="${item.code}">
                <a href="#" class="menu-item" data-path="${item.path || ''}" data-page="${pageName}">
                    <span class="menu-icon">${item.icon || '•'}</span>
                    <span>${item.name}</span>
                </a>`;
            
            if (hasChildren) {
                html += `<ul class="submenu">${this.renderMenuItems(item.children, level + 1)}</ul>`;
            }
            
            html += '</li>';
            return html;
        }).join('');
    },

    async loadPageData(page) {
        switch (page) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'users':
                this.loadUsers();
                break;
            case 'admins':
                this.loadAdmins();
                break;
            case 'roles':
                this.loadRoles();
                break;
            case 'permissions':
                this.loadPermissions();
                break;
            case 'login-logs':
                this.loadLoginLogs();
                break;
            case 'configs':
                this.loadConfigs();
                break;
        }
    },

    async loadDashboard() {
        document.getElementById('php-version').textContent = '7.4+';
        document.getElementById('server-software').textContent = 'Apache/Nginx';
        document.getElementById('current-time').textContent = new Date().toLocaleString('zh-CN');

        try {
            const [usersRes, adminsRes, logsRes, configsRes] = await Promise.all([
                this.request('/api/users?page_size=1', 'GET'),
                this.request('/api/admins?page_size=1', 'GET'),
                this.request('/api/login-logs?page_size=1', 'GET'),
                this.request('/api/configs/all', 'GET')
            ]);

            if (usersRes.code === 200) {
                document.getElementById('stat-users').textContent = usersRes.data.pagination?.total || 0;
            }
            if (adminsRes.code === 200) {
                document.getElementById('stat-admins').textContent = adminsRes.data.pagination?.total || 0;
            }
            if (logsRes.code === 200) {
                document.getElementById('stat-logs').textContent = logsRes.data.pagination?.total || 0;
            }
            if (configsRes.code === 200) {
                document.getElementById('stat-configs').textContent = Object.keys(configsRes.data).length || 0;
            }
        } catch (err) {
            console.error('Load dashboard error:', err);
        }
    },

    userPage: 1,
    userPageSize: 10,
    userSearchKeyword: '',
    userStatus: '',

    async loadUsers() {
        const params = new URLSearchParams({
            page: this.userPage,
            page_size: this.userPageSize
        });

        if (this.userSearchKeyword) {
            params.append('keywords', this.userSearchKeyword);
        }
        if (this.userStatus !== '') {
            params.append('status', this.userStatus);
        }

        try {
            const res = await this.request(`/api/users?${params.toString()}`, 'GET');
            
            if (res.code === 200) {
                this.renderUserTable(res.data.items);
                this.renderPagination('user-pagination', res.data.pagination, (page) => {
                    this.userPage = page;
                    this.loadUsers();
                });
            }
        } catch (err) {
            this.toast('加载用户列表失败', 'error');
        }

        document.getElementById('user-search-btn').onclick = () => {
            this.userSearchKeyword = document.getElementById('user-search').value;
            this.userStatus = document.getElementById('user-status').value;
            this.userPage = 1;
            this.loadUsers();
        };

        document.getElementById('user-reset-btn').onclick = () => {
            document.getElementById('user-search').value = '';
            document.getElementById('user-status').value = '';
            this.userSearchKeyword = '';
            this.userStatus = '';
            this.userPage = 1;
            this.loadUsers();
        };

        document.getElementById('add-user-btn').onclick = () => {
            this.showUserForm();
        };
    },

    renderUserTable(users) {
        const tbody = document.getElementById('user-table-body');
        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username}</td>
                <td>${user.nickname}</td>
                <td>${user.email || '-'}</td>
                <td>${user.phone || '-'}</td>
                <td><span class="status-badge ${user.status == 1 ? 'active' : 'inactive'}">${user.status == 1 ? '正常' : '禁用'}</span></td>
                <td>${user.last_login_at || '-'}</td>
                <td>${user.created_at}</td>
                <td class="actions">
                    <button class="btn btn-primary btn-sm" onclick="App.editUser(${user.id})">编辑</button>
                    <button class="btn btn-danger btn-sm" onclick="App.deleteUser(${user.id})">删除</button>
                </td>
            </tr>
        `).join('');
    },

    async showUserForm(userId = null) {
        let title = '新增用户';
        let user = {};

        if (userId) {
            title = '编辑用户';
            const res = await this.request(`/api/users/${userId}`, 'GET');
            if (res.code === 200) {
                user = res.data;
            }
        }

        this.showModal(title, `
            <div class="form-group">
                <label>用户名 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-username" value="${user.username || ''}" placeholder="请输入用户名" ${userId ? 'readonly' : ''}>
            </div>
            ${!userId ? `
            <div class="form-group">
                <label>密码 <span style="color:red">*</span></label>
                <input type="password" class="form-input" id="form-password" placeholder="请输入密码">
            </div>
            <div class="form-group">
                <label>确认密码 <span style="color:red">*</span></label>
                <input type="password" class="form-input" id="form-confirm-password" placeholder="请确认密码">
            </div>
            ` : `
            <div class="form-group">
                <label>新密码 (留空则不修改)</label>
                <input type="password" class="form-input" id="form-password" placeholder="请输入新密码">
            </div>
            `}
            <div class="form-group">
                <label>昵称 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-nickname" value="${user.nickname || ''}" placeholder="请输入昵称">
            </div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" class="form-input" id="form-email" value="${user.email || ''}" placeholder="请输入邮箱">
            </div>
            <div class="form-group">
                <label>手机号</label>
                <input type="text" class="form-input" id="form-phone" value="${user.phone || ''}" placeholder="请输入手机号">
            </div>
            <div class="form-group">
                <label>状态</label>
                <select class="form-select" id="form-status">
                    <option value="1" ${user.status == 1 ? 'selected' : ''}>正常</option>
                    <option value="0" ${user.status == 0 ? 'selected' : ''}>禁用</option>
                </select>
            </div>
        `, [
            { text: '取消', class: 'btn-default', action: () => this.closeModal() },
            { text: '保存', class: 'btn-primary', action: async () => {
                await this.saveUser(userId);
            }}
        ]);
    },

    async editUser(userId) {
        this.showUserForm(userId);
    },

    async saveUser(userId) {
        const username = document.getElementById('form-username').value.trim();
        const password = document.getElementById('form-password').value;
        const nickname = document.getElementById('form-nickname').value.trim();
        const email = document.getElementById('form-email').value.trim();
        const phone = document.getElementById('form-phone').value.trim();
        const status = document.getElementById('form-status').value;

        if (!username || !nickname) {
            this.toast('请填写必填项', 'error');
            return;
        }

        if (!userId && !password) {
            this.toast('请输入密码', 'error');
            return;
        }

        const confirmPassword = document.getElementById('form-confirm-password');
        if (confirmPassword && password !== confirmPassword.value) {
            this.toast('两次密码输入不一致', 'error');
            return;
        }

        const data = { username, nickname, email, phone, status: parseInt(status) };
        if (password) {
            data.password = password;
        }

        try {
            let res;
            if (userId) {
                res = await this.request(`/api/users/${userId}`, 'PUT', data);
            } else {
                res = await this.request('/api/users', 'POST', data);
            }

            if (res.code === 200) {
                this.toast(userId ? '更新成功' : '创建成功', 'success');
                this.closeModal();
                this.loadUsers();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('操作失败', 'error');
        }
    },

    async deleteUser(userId) {
        if (!confirm('确定要删除该用户吗？')) {
            return;
        }

        try {
            const res = await this.request(`/api/users/${userId}`, 'DELETE');
            if (res.code === 200) {
                this.toast('删除成功', 'success');
                this.loadUsers();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('删除失败', 'error');
        }
    },

    adminPage: 1,
    adminPageSize: 10,
    adminSearchKeyword: '',
    adminStatus: '',

    async loadAdmins() {
        const params = new URLSearchParams({
            page: this.adminPage,
            page_size: this.adminPageSize
        });

        if (this.adminSearchKeyword) {
            params.append('keywords', this.adminSearchKeyword);
        }
        if (this.adminStatus !== '') {
            params.append('status', this.adminStatus);
        }

        try {
            const res = await this.request(`/api/admins?${params.toString()}`, 'GET');
            
            if (res.code === 200) {
                this.renderAdminTable(res.data.items);
                this.renderPagination('admin-pagination', res.data.pagination, (page) => {
                    this.adminPage = page;
                    this.loadAdmins();
                });
            }
        } catch (err) {
            this.toast('加载管理员列表失败', 'error');
        }

        document.getElementById('admin-search-btn').onclick = () => {
            this.adminSearchKeyword = document.getElementById('admin-search').value;
            this.adminStatus = document.getElementById('admin-status').value;
            this.adminPage = 1;
            this.loadAdmins();
        };

        document.getElementById('admin-reset-btn').onclick = () => {
            document.getElementById('admin-search').value = '';
            document.getElementById('admin-status').value = '';
            this.adminSearchKeyword = '';
            this.adminStatus = '';
            this.adminPage = 1;
            this.loadAdmins();
        };

        document.getElementById('add-admin-btn').onclick = () => {
            this.showAdminForm();
        };
    },

    renderAdminTable(admins) {
        const tbody = document.getElementById('admin-table-body');
        tbody.innerHTML = admins.map(admin => `
            <tr>
                <td>${admin.id}</td>
                <td>${admin.username}</td>
                <td>${admin.nickname}</td>
                <td>${admin.email || '-'}</td>
                <td>${admin.role_name || '-'}</td>
                <td>${admin.is_super ? '<span class="super-badge">超级管理员</span>' : '-'}</td>
                <td><span class="status-badge ${admin.status == 1 ? 'active' : 'inactive'}">${admin.status == 1 ? '正常' : '禁用'}</span></td>
                <td>${admin.last_login_at || '-'}</td>
                <td>${admin.created_at}</td>
                <td class="actions">
                    <button class="btn btn-primary btn-sm" onclick="App.editAdmin(${admin.id})">编辑</button>
                    ${!admin.is_super ? `<button class="btn btn-danger btn-sm" onclick="App.deleteAdmin(${admin.id})">删除</button>` : ''}
                </td>
            </tr>
        `).join('');
    },

    async showAdminForm(adminId = null) {
        let title = '新增管理员';
        let admin = {};
        let roles = [];

        try {
            const rolesRes = await this.request('/api/roles/all', 'GET');
            if (rolesRes.code === 200) {
                roles = rolesRes.data.items;
            }
        } catch (err) {
            console.error('Load roles error:', err);
        }

        if (adminId) {
            title = '编辑管理员';
            const res = await this.request(`/api/admins/${adminId}`, 'GET');
            if (res.code === 200) {
                admin = res.data;
            }
        }

        this.showModal(title, `
            <div class="form-group">
                <label>用户名 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-admin-username" value="${admin.username || ''}" placeholder="请输入用户名" ${adminId ? 'readonly' : ''}>
            </div>
            ${!adminId ? `
            <div class="form-group">
                <label>密码 <span style="color:red">*</span></label>
                <input type="password" class="form-input" id="form-admin-password" placeholder="请输入密码">
            </div>
            <div class="form-group">
                <label>确认密码 <span style="color:red">*</span></label>
                <input type="password" class="form-input" id="form-admin-confirm-password" placeholder="请确认密码">
            </div>
            ` : `
            <div class="form-group">
                <label>新密码 (留空则不修改)</label>
                <input type="password" class="form-input" id="form-admin-password" placeholder="请输入新密码">
            </div>
            `}
            <div class="form-group">
                <label>昵称 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-admin-nickname" value="${admin.nickname || ''}" placeholder="请输入昵称">
            </div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" class="form-input" id="form-admin-email" value="${admin.email || ''}" placeholder="请输入邮箱">
            </div>
            <div class="form-group">
                <label>角色</label>
                <select class="form-select" id="form-admin-role">
                    <option value="">请选择角色</option>
                    ${roles.map(role => `<option value="${role.id}" ${admin.role_id == role.id ? 'selected' : ''}>${role.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>状态</label>
                <select class="form-select" id="form-admin-status">
                    <option value="1" ${admin.status == 1 ? 'selected' : ''}>正常</option>
                    <option value="0" ${admin.status == 0 ? 'selected' : ''}>禁用</option>
                </select>
            </div>
        `, [
            { text: '取消', class: 'btn-default', action: () => this.closeModal() },
            { text: '保存', class: 'btn-primary', action: async () => {
                await this.saveAdmin(adminId);
            }}
        ]);
    },

    async editAdmin(adminId) {
        this.showAdminForm(adminId);
    },

    async saveAdmin(adminId) {
        const username = document.getElementById('form-admin-username').value.trim();
        const password = document.getElementById('form-admin-password').value;
        const nickname = document.getElementById('form-admin-nickname').value.trim();
        const email = document.getElementById('form-admin-email').value.trim();
        const roleId = document.getElementById('form-admin-role').value;
        const status = document.getElementById('form-admin-status').value;

        if (!username || !nickname) {
            this.toast('请填写必填项', 'error');
            return;
        }

        if (!adminId && !password) {
            this.toast('请输入密码', 'error');
            return;
        }

        const confirmPassword = document.getElementById('form-admin-confirm-password');
        if (confirmPassword && password !== confirmPassword.value) {
            this.toast('两次密码输入不一致', 'error');
            return;
        }

        const data = { 
            username, 
            nickname, 
            email, 
            status: parseInt(status)
        };
        if (roleId) {
            data.role_id = parseInt(roleId);
        }
        if (password) {
            data.password = password;
        }

        try {
            let res;
            if (adminId) {
                res = await this.request(`/api/admins/${adminId}`, 'PUT', data);
            } else {
                res = await this.request('/api/admins', 'POST', data);
            }

            if (res.code === 200) {
                this.toast(adminId ? '更新成功' : '创建成功', 'success');
                this.closeModal();
                this.loadAdmins();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('操作失败', 'error');
        }
    },

    async deleteAdmin(adminId) {
        if (!confirm('确定要删除该管理员吗？')) {
            return;
        }

        try {
            const res = await this.request(`/api/admins/${adminId}`, 'DELETE');
            if (res.code === 200) {
                this.toast('删除成功', 'success');
                this.loadAdmins();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('删除失败', 'error');
        }
    },

    rolePage: 1,
    rolePageSize: 10,

    async loadRoles() {
        try {
            const res = await this.request(`/api/roles?page=${this.rolePage}&page_size=${this.rolePageSize}`, 'GET');
            
            if (res.code === 200) {
                this.renderRoleTable(res.data.items);
                this.renderPagination('role-pagination', res.data.pagination, (page) => {
                    this.rolePage = page;
                    this.loadRoles();
                });
            }
        } catch (err) {
            this.toast('加载角色列表失败', 'error');
        }

        document.getElementById('add-role-btn').onclick = () => {
            this.showRoleForm();
        };
    },

    renderRoleTable(roles) {
        const tbody = document.getElementById('role-table-body');
        tbody.innerHTML = roles.map(role => `
            <tr>
                <td>${role.id}</td>
                <td>${role.name}</td>
                <td><code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;">${role.code}</code></td>
                <td>${role.description || '-'}</td>
                <td>${role.sort}</td>
                <td><span class="status-badge ${role.status == 1 ? 'active' : 'inactive'}">${role.status == 1 ? '正常' : '禁用'}</span></td>
                <td>${role.created_at}</td>
                <td class="actions">
                    <button class="btn btn-primary btn-sm" onclick="App.editRole(${role.id})">编辑</button>
                    <button class="btn btn-success btn-sm" onclick="App.editRolePermission(${role.id})">权限</button>
                    ${role.code !== 'super_admin' ? `<button class="btn btn-danger btn-sm" onclick="App.deleteRole(${role.id})">删除</button>` : ''}
                </td>
            </tr>
        `).join('');
    },

    async showRoleForm(roleId = null) {
        let title = '新增角色';
        let role = {};

        if (roleId) {
            title = '编辑角色';
            const res = await this.request(`/api/roles/${roleId}`, 'GET');
            if (res.code === 200) {
                role = res.data;
            }
        }

        this.showModal(title, `
            <div class="form-group">
                <label>角色名称 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-role-name" value="${role.name || ''}" placeholder="请输入角色名称">
            </div>
            <div class="form-group">
                <label>角色标识 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-role-code" value="${role.code || ''}" placeholder="请输入角色标识 (如: admin)" ${roleId && role.code === 'super_admin' ? 'readonly' : ''}>
            </div>
            <div class="form-group">
                <label>描述</label>
                <textarea class="form-input" id="form-role-description" placeholder="请输入角色描述" style="height:80px;">${role.description || ''}</textarea>
            </div>
            <div class="form-group">
                <label>排序</label>
                <input type="number" class="form-input" id="form-role-sort" value="${role.sort || 0}" placeholder="数字越小越靠前">
            </div>
            <div class="form-group">
                <label>状态</label>
                <select class="form-select" id="form-role-status">
                    <option value="1" ${role.status == 1 ? 'selected' : ''}>正常</option>
                    <option value="0" ${role.status == 0 ? 'selected' : ''}>禁用</option>
                </select>
            </div>
        `, [
            { text: '取消', class: 'btn-default', action: () => this.closeModal() },
            { text: '保存', class: 'btn-primary', action: async () => {
                await this.saveRole(roleId);
            }}
        ]);
    },

    async editRole(roleId) {
        this.showRoleForm(roleId);
    },

    async saveRole(roleId) {
        const name = document.getElementById('form-role-name').value.trim();
        const code = document.getElementById('form-role-code').value.trim();
        const description = document.getElementById('form-role-description').value.trim();
        const sort = document.getElementById('form-role-sort').value;
        const status = document.getElementById('form-role-status').value;

        if (!name || !code) {
            this.toast('请填写必填项', 'error');
            return;
        }

        const data = { 
            name, 
            code, 
            description, 
            sort: parseInt(sort) || 0,
            status: parseInt(status)
        };

        try {
            let res;
            if (roleId) {
                res = await this.request(`/api/roles/${roleId}`, 'PUT', data);
            } else {
                res = await this.request('/api/roles', 'POST', data);
            }

            if (res.code === 200) {
                this.toast(roleId ? '更新成功' : '创建成功', 'success');
                this.closeModal();
                this.loadRoles();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('操作失败', 'error');
        }
    },

    async deleteRole(roleId) {
        if (!confirm('确定要删除该角色吗？')) {
            return;
        }

        try {
            const res = await this.request(`/api/roles/${roleId}`, 'DELETE');
            if (res.code === 200) {
                this.toast('删除成功', 'success');
                this.loadRoles();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('删除失败', 'error');
        }
    },

    async editRolePermission(roleId) {
        let role = {};
        let permissions = [];

        try {
            const [roleRes, permRes] = await Promise.all([
                this.request(`/api/roles/${roleId}`, 'GET'),
                this.request('/api/permissions/tree', 'GET')
            ]);

            if (roleRes.code === 200) {
                role = roleRes.data;
            }
            if (permRes.code === 200) {
                permissions = permRes.data.items;
            }
        } catch (err) {
            this.toast('加载数据失败', 'error');
            return;
        }

        const selectedIds = role.permission_ids || [];

        const renderPermTree = (items, level = 0) => {
            return items.map(item => {
                const hasChildren = item.children && item.children.length > 0;
                const indent = '　'.repeat(level * 2);
                const prefix = level > 0 ? '├─ ' : '';
                const isChecked = selectedIds.includes(item.id);

                let html = `<div style="padding:4px 0;">
                    <label style="display:flex;align-items:center;cursor:pointer;">
                        <input type="checkbox" value="${item.id}" class="perm-checkbox" ${isChecked ? 'checked' : ''} style="margin-right:8px;">
                        <span>${indent}${prefix}${item.name}</span>
                    </label>`;
                
                if (hasChildren) {
                    html += `<div style="margin-left:20px;">${renderPermTree(item.children, level + 1)}</div>`;
                }
                
                html += '</div>';
                return html;
            }).join('');
        };

        this.showModal(`分配权限 - ${role.name}`, `
            <div style="max-height:400px;overflow-y:auto;">
                ${renderPermTree(permissions)}
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f0f0f0;">
                <label style="cursor:pointer;">
                    <input type="checkbox" id="select-all-perms" style="margin-right:8px;">
                    全选/取消全选
                </label>
            </div>
        `, [
            { text: '取消', class: 'btn-default', action: () => this.closeModal() },
            { text: '保存', class: 'btn-primary', action: async () => {
                const checkboxes = document.querySelectorAll('.perm-checkbox:checked');
                const permissionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
                
                try {
                    const res = await this.request(`/api/roles/${roleId}`, 'PUT', { permission_ids: permissionIds });
                    if (res.code === 200) {
                        this.toast('权限分配成功', 'success');
                        this.closeModal();
                    } else {
                        this.toast(res.message, 'error');
                    }
                } catch (err) {
                    this.toast('保存失败', 'error');
                }
            }}
        ]);

        document.getElementById('select-all-perms').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.perm-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    },

    async loadPermissions() {
        try {
            const res = await this.request('/api/permissions/tree', 'GET');
            
            if (res.code === 200) {
                this.renderPermissionTable(res.data.items);
            }
        } catch (err) {
            this.toast('加载权限列表失败', 'error');
        }

        document.getElementById('add-permission-btn').onclick = () => {
            this.showPermissionForm();
        };
    },

    renderPermissionTable(permissions) {
        const renderRows = (items, level = 0) => {
            return items.map(item => {
                const hasChildren = item.children && item.children.length > 0;
                const indent = '　'.repeat(level * 2);
                const prefix = level > 0 ? '├─ ' : '';
                const typeText = { 1: '菜单', 2: '按钮', 3: '接口' }[item.type] || item.type;

                let html = `<tr>
                    <td>${indent}${prefix}${item.name}</td>
                    <td><code style="background:#f5f5f5;padding:2px 8px;border-radius:4px;">${item.code}</code></td>
                    <td>${typeText}</td>
                    <td>${item.path || '-'}</td>
                    <td>${item.sort}</td>
                    <td><span class="status-badge ${item.status == 1 ? 'active' : 'inactive'}">${item.status == 1 ? '显示' : '隐藏'}</span></td>
                    <td class="actions">
                        <button class="btn btn-primary btn-sm" onclick="App.editPermission(${item.id})">编辑</button>
                        ${!hasChildren ? `<button class="btn btn-danger btn-sm" onclick="App.deletePermission(${item.id})">删除</button>` : ''}
                    </td>
                </tr>`;
                
                if (hasChildren) {
                    html += renderRows(item.children, level + 1);
                }
                
                return html;
            }).join('');
        };

        document.getElementById('permission-table-body').innerHTML = renderRows(permissions);
    },

    async showPermissionForm(permId = null) {
        let title = '新增权限';
        let permission = {};
        let options = [{ id: 0, name: '顶级菜单' }];

        try {
            const optRes = await this.request('/api/permissions/options', 'GET');
            if (optRes.code === 200) {
                options = optRes.data.items;
            }
        } catch (err) {
            console.error('Load options error:', err);
        }

        if (permId) {
            title = '编辑权限';
            const res = await this.request(`/api/permissions/${permId}`, 'GET');
            if (res.code === 200) {
                permission = res.data;
            }
        }

        this.showModal(title, `
            <div class="form-group">
                <label>权限名称 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-perm-name" value="${permission.name || ''}" placeholder="请输入权限名称">
            </div>
            <div class="form-group">
                <label>权限标识 <span style="color:red">*</span></label>
                <input type="text" class="form-input" id="form-perm-code" value="${permission.code || ''}" placeholder="请输入权限标识 (如: user:list)">
            </div>
            <div class="form-group">
                <label>上级权限</label>
                <select class="form-select" id="form-perm-parent">
                    ${options.map(opt => `<option value="${opt.id}" ${permission.parent_id == opt.id ? 'selected' : ''}>${opt.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>类型</label>
                <select class="form-select" id="form-perm-type">
                    <option value="1" ${permission.type == 1 ? 'selected' : ''}>菜单</option>
                    <option value="2" ${permission.type == 2 ? 'selected' : ''}>按钮</option>
                    <option value="3" ${permission.type == 3 ? 'selected' : ''}>接口</option>
                </select>
            </div>
            <div class="form-group">
                <label>路由路径</label>
                <input type="text" class="form-input" id="form-perm-path" value="${permission.path || ''}" placeholder="请输入路由路径">
            </div>
            <div class="form-group">
                <label>图标</label>
                <input type="text" class="form-input" id="form-perm-icon" value="${permission.icon || ''}" placeholder="emoji或图标名称">
            </div>
            <div class="form-group">
                <label>组件路径</label>
                <input type="text" class="form-input" id="form-perm-component" value="${permission.component || ''}" placeholder="Vue组件路径">
            </div>
            <div class="form-group">
                <label>排序</label>
                <input type="number" class="form-input" id="form-perm-sort" value="${permission.sort || 0}" placeholder="数字越小越靠前">
            </div>
            <div class="form-group">
                <label>状态</label>
                <select class="form-select" id="form-perm-status">
                    <option value="1" ${permission.status == 1 ? 'selected' : ''}>显示</option>
                    <option value="0" ${permission.status == 0 ? 'selected' : ''}>隐藏</option>
                </select>
            </div>
        `, [
            { text: '取消', class: 'btn-default', action: () => this.closeModal() },
            { text: '保存', class: 'btn-primary', action: async () => {
                await this.savePermission(permId);
            }}
        ]);
    },

    async editPermission(permId) {
        this.showPermissionForm(permId);
    },

    async savePermission(permId) {
        const name = document.getElementById('form-perm-name').value.trim();
        const code = document.getElementById('form-perm-code').value.trim();
        const parentId = document.getElementById('form-perm-parent').value;
        const type = document.getElementById('form-perm-type').value;
        const path = document.getElementById('form-perm-path').value.trim();
        const icon = document.getElementById('form-perm-icon').value.trim();
        const component = document.getElementById('form-perm-component').value.trim();
        const sort = document.getElementById('form-perm-sort').value;
        const status = document.getElementById('form-perm-status').value;

        if (!name || !code) {
            this.toast('请填写必填项', 'error');
            return;
        }

        const data = { 
            name, 
            code, 
            parent_id: parseInt(parentId) || 0,
            type: parseInt(type),
            path,
            icon,
            component,
            sort: parseInt(sort) || 0,
            status: parseInt(status)
        };

        try {
            let res;
            if (permId) {
                res = await this.request(`/api/permissions/${permId}`, 'PUT', data);
            } else {
                res = await this.request('/api/permissions', 'POST', data);
            }

            if (res.code === 200) {
                this.toast(permId ? '更新成功' : '创建成功', 'success');
                this.closeModal();
                this.loadPermissions();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('操作失败', 'error');
        }
    },

    async deletePermission(permId) {
        if (!confirm('确定要删除该权限吗？')) {
            return;
        }

        try {
            const res = await this.request(`/api/permissions/${permId}`, 'DELETE');
            if (res.code === 200) {
                this.toast('删除成功', 'success');
                this.loadPermissions();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('删除失败', 'error');
        }
    },

    logPage: 1,
    logPageSize: 10,
    logUsername: '',
    logStatus: '',
    logType: '',

    async loadLoginLogs() {
        const params = new URLSearchParams({
            page: this.logPage,
            page_size: this.logPageSize
        });

        if (this.logUsername) {
            params.append('username', this.logUsername);
        }
        if (this.logStatus !== '') {
            params.append('status', this.logStatus);
        }
        if (this.logType !== '') {
            params.append('login_type', this.logType);
        }

        try {
            const res = await this.request(`/api/login-logs?${params.toString()}`, 'GET');
            
            if (res.code === 200) {
                this.renderLogTable(res.data.items);
                this.renderPagination('log-pagination', res.data.pagination, (page) => {
                    this.logPage = page;
                    this.loadLoginLogs();
                });
            }
        } catch (err) {
            this.toast('加载登录日志失败', 'error');
        }

        document.getElementById('log-search-btn').onclick = () => {
            this.logUsername = document.getElementById('log-username').value;
            this.logStatus = document.getElementById('log-status').value;
            this.logType = document.getElementById('log-type').value;
            this.logPage = 1;
            this.loadLoginLogs();
        };

        document.getElementById('log-reset-btn').onclick = () => {
            document.getElementById('log-username').value = '';
            document.getElementById('log-status').value = '';
            document.getElementById('log-type').value = '';
            this.logUsername = '';
            this.logStatus = '';
            this.logType = '';
            this.logPage = 1;
            this.loadLoginLogs();
        };

        document.getElementById('clear-logs-btn').onclick = () => {
            if (confirm('确定要清理30天前的日志吗？')) {
                this.clearLogs();
            }
        };
    },

    renderLogTable(logs) {
        const tbody = document.getElementById('log-table-body');
        tbody.innerHTML = logs.map(log => `
            <tr>
                <td>${log.id}</td>
                <td>${log.username}</td>
                <td>${log.ip || '-'}</td>
                <td>${log.login_type == 1 ? '管理员' : '用户'}</td>
                <td><span class="status-badge ${log.status == 1 ? 'success' : 'fail'}">${log.status == 1 ? '成功' : '失败'}</span></td>
                <td>${log.message || '-'}</td>
                <td>${log.created_at}</td>
                <td class="actions">
                    <button class="btn btn-danger btn-sm" onclick="App.deleteLog(${log.id})">删除</button>
                </td>
            </tr>
        `).join('');
    },

    async deleteLog(logId) {
        if (!confirm('确定要删除该日志吗？')) {
            return;
        }

        try {
            const res = await this.request(`/api/login-logs/${logId}`, 'DELETE');
            if (res.code === 200) {
                this.toast('删除成功', 'success');
                this.loadLoginLogs();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('删除失败', 'error');
        }
    },

    async clearLogs() {
        try {
            const res = await this.request('/api/login-logs/clear', 'POST');
            if (res.code === 200) {
                this.toast('清理成功', 'success');
                this.loadLoginLogs();
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('清理失败', 'error');
        }
    },

    async loadConfigs() {
        try {
            const [groupsRes, configsRes] = await Promise.all([
                this.request('/api/configs/groups', 'GET'),
                this.request(`/api/configs/group/${this.currentConfigGroup}`, 'GET')
            ]);

            if (groupsRes.code === 200) {
                this.configGroups = groupsRes.data.items;
                this.renderConfigTabs();
            }

            if (configsRes.code === 200) {
                this.renderConfigFields(configsRes.data);
            }
        } catch (err) {
            this.toast('加载配置失败', 'error');
        }
    },

    renderConfigTabs() {
        const tabs = document.getElementById('config-tabs');
        tabs.innerHTML = this.configGroups.map(group => `
            <button class="${group.group_name === this.currentConfigGroup ? 'active' : ''}" 
                    data-group="${group.group_name}">${this.getGroupName(group.group_name)}</button>
        `).join('');

        tabs.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentConfigGroup = btn.dataset.group;
                this.loadConfigs();
            });
        });
    },

    getGroupName(code) {
        const names = {
            basic: '基本配置',
            upload: '上传配置',
            email: '邮件配置'
        };
        return names[code] || code;
    },

    renderConfigFields(configs) {
        const container = document.getElementById('config-fields');
        container.innerHTML = Object.entries(configs).map(([key, config]) => {
            const value = config.value || '';
            let inputHtml = '';

            switch (config.type) {
                case 'textarea':
                    inputHtml = `<textarea name="${key}" class="form-input" style="height:100px;">${value}</textarea>`;
                    break;
                case 'switch':
                    inputHtml = `
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="${key}" value="1" ${value == '1' ? 'checked' : ''}>
                            启用
                        </label>`;
                    break;
                case 'select':
                    const options = config.options || [];
                    inputHtml = `<select name="${key}" class="form-select">
                        ${options.map(opt => `<option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>${opt.label}</option>`).join('')}
                    </select>`;
                    break;
                case 'number':
                    inputHtml = `<input type="number" name="${key}" value="${value}" class="form-input">`;
                    break;
                default:
                    inputHtml = `<input type="text" name="${key}" value="${value}" class="form-input">`;
            }

            return `
                <div class="config-field">
                    <label>${config.name}</label>
                    ${config.description ? `<div class="field-desc">${config.description}</div>` : ''}
                    ${inputHtml}
                </div>
            `;
        }).join('');
    },

    async saveConfigs() {
        const form = document.getElementById('config-form');
        const data = {};

        form.querySelectorAll('[name]').forEach(input => {
            if (input.type === 'checkbox') {
                data[input.name] = input.checked ? '1' : '0';
            } else {
                data[input.name] = input.value;
            }
        });

        try {
            const res = await this.request('/api/configs/batch', 'PUT', data);
            if (res.code === 200) {
                this.toast('保存成功', 'success');
            } else {
                this.toast(res.message, 'error');
            }
        } catch (err) {
            this.toast('保存失败', 'error');
        }
    },

    renderPagination(containerId, pagination, onPageChange) {
        const container = document.getElementById(containerId);
        const { page, page_size, total, total_pages } = pagination;

        if (total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = `
            <button ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}">上一页</button>
        `;

        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(total_pages, page + 2);

        if (startPage > 1) {
            html += `<button data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span style="padding:0 8px;">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="${i === page ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < total_pages) {
            if (endPage < total_pages - 1) {
                html += `<span style="padding:0 8px;">...</span>`;
            }
            html += `<button data-page="${total_pages}">${total_pages}</button>`;
        }

        html += `
            <button ${page >= total_pages ? 'disabled' : ''} data-page="${page + 1}">下一页</button>
            <span class="page-info">共 ${total} 条，第 ${page}/${total_pages} 页</span>
        `;

        container.innerHTML = html;

        container.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const newPage = parseInt(btn.dataset.page);
                if (newPage >= 1 && newPage <= total_pages && newPage !== page) {
                    onPageChange(newPage);
                }
            });
        });
    },

    showModal(title, body, actions = []) {
        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-body').innerHTML = body;

        const footer = document.getElementById('modal-footer');
        footer.innerHTML = actions.map(action => `
            <button class="btn ${action.class} modal-action-btn">${action.text}</button>
        `).join('');

        footer.querySelectorAll('.modal-action-btn').forEach((btn, index) => {
            btn.addEventListener('click', actions[index].action);
        });

        document.getElementById('modal').classList.add('active');
    },

    closeModal() {
        document.getElementById('modal').classList.remove('active');
    },

    async request(url, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (this.token) {
            options.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();

            if (response.status === 401 && result.code === 401) {
                if (await this.refreshAccessToken()) {
                    return this.request(url, method, data);
                } else {
                    this.token = '';
                    this.refreshToken = '';
                    localStorage.removeItem('token');
                    localStorage.removeItem('refresh_token');
                    this.showPage('login');
                    throw new Error('认证失败');
                }
            }

            return result;
        } catch (err) {
            console.error('Request error:', err);
            throw err;
        }
    },

    async refreshAccessToken() {
        if (!this.refreshToken) {
            return false;
        }

        try {
            const response = await fetch('/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ refresh_token: this.refreshToken })
            });

            const result = await response.json();

            if (result.code === 200) {
                this.token = result.data.token;
                this.refreshToken = result.data.refresh_token;
                localStorage.setItem('token', this.token);
                localStorage.setItem('refresh_token', this.refreshToken);
                return true;
            }

            return false;
        } catch (err) {
            return false;
        }
    },

    toast(message, type = 'info') {
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };

        const toast = document.createElement('div');
        toast.className = `toast-item ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || 'ℹ'}</span>
            <span class="toast-message">${message}</span>
        `;

        document.getElementById('toast').appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100px)';
            toast.style.transition = 'all 0.3s';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
