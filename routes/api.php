<?php

use Core\Router;

$router->group('/api', function (Router $router) {
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/logout', 'AuthController@logout');
    $router->post('/auth/refresh', 'AuthController@refresh');

    $router->group('/auth', function (Router $router) {
        $router->get('/user', 'AuthController@userInfo');
        $router->put('/password', 'AuthController@changePassword');
        $router->put('/profile', 'AuthController@updateProfile');
    }, ['AuthMiddleware']);

    $router->group('/admins', function (Router $router) {
        $router->get('/', 'AdminController@index');
        $router->get('/options', 'AdminController@options');
        $router->get('/{id}', 'AdminController@show');
        $router->post('/', 'AdminController@store');
        $router->put('/{id}', 'AdminController@update');
        $router->delete('/{id}', 'AdminController@destroy');
        $router->put('/{id}/toggle-status', 'AdminController@toggleStatus');
    }, ['AuthMiddleware']);

    $router->group('/users', function (Router $router) {
        $router->get('/', 'UserController@index');
        $router->get('/{id}', 'UserController@show');
        $router->post('/', 'UserController@store');
        $router->put('/{id}', 'UserController@update');
        $router->delete('/{id}', 'UserController@destroy');
        $router->post('/batch-delete', 'UserController@batchDestroy');
        $router->put('/{id}/toggle-status', 'UserController@toggleStatus');
    }, ['AuthMiddleware']);

    $router->group('/roles', function (Router $router) {
        $router->get('/', 'RoleController@index');
        $router->get('/all', 'RoleController@all');
        $router->get('/{id}', 'RoleController@show');
        $router->post('/', 'RoleController@store');
        $router->put('/{id}', 'RoleController@update');
        $router->delete('/{id}', 'RoleController@destroy');
        $router->put('/{id}/toggle-status', 'RoleController@toggleStatus');
    }, ['AuthMiddleware']);

    $router->group('/permissions', function (Router $router) {
        $router->get('/', 'PermissionController@index');
        $router->get('/tree', 'PermissionController@tree');
        $router->get('/menu', 'PermissionController@menu');
        $router->get('/options', 'PermissionController@options');
        $router->get('/{id}', 'PermissionController@show');
        $router->post('/', 'PermissionController@store');
        $router->put('/{id}', 'PermissionController@update');
        $router->delete('/{id}', 'PermissionController@destroy');
    }, ['AuthMiddleware']);

    $router->group('/login-logs', function (Router $router) {
        $router->get('/', 'LoginLogController@index');
        $router->get('/statistics', 'LoginLogController@statistics');
        $router->get('/chart', 'LoginLogController@chartData');
        $router->get('/{id}', 'LoginLogController@show');
        $router->delete('/{id}', 'LoginLogController@destroy');
        $router->post('/batch-delete', 'LoginLogController@batchDestroy');
        $router->post('/clear', 'LoginLogController@clear');
    }, ['AuthMiddleware']);

    $router->group('/configs', function (Router $router) {
        $router->get('/', 'ConfigController@index');
        $router->get('/all', 'ConfigController@all');
        $router->get('/groups', 'ConfigController@groups');
        $router->get('/group/{groupName}', 'ConfigController@getByGroup');
        $router->get('/{keyName}', 'ConfigController@show');
        $router->post('/', 'ConfigController@store');
        $router->put('/batch', 'ConfigController@batchUpdate');
        $router->put('/{keyName}', 'ConfigController@update');
        $router->delete('/{keyName}', 'ConfigController@destroy');
        $router->post('/upload', 'ConfigController@upload');
    }, ['AuthMiddleware']);

    $router->group('/examples', function (Router $router) {
        $router->get('/jwt-example', function () use ($router) {
            $router->successResponse([
                'description' => '这是一个JWT鉴权示例接口',
                'tips' => [
                    '1. 登录获取Token: POST /api/auth/login',
                    '2. 请求时在Header中添加: Authorization: Bearer {token}',
                    '3. Token过期后使用刷新令牌: POST /api/auth/refresh'
                ],
                'current_user' => $GLOBALS['current_admin'] ?? null
            ]);
        });
    }, ['AuthMiddleware']);

}, []);
