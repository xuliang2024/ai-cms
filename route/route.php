<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

// API接口路由示例
Route::group('api', function () {
    // 获取用户信息API - GET请求
    Route::get('user/:id', 'api/user/getInfo');
    
    // 创建用户API - POST请求
    Route::post('user', 'api/user/create');
    
    // 更新用户API - PUT请求
    Route::put('user/:id', 'api/user/update');
    
    // 删除用户API - DELETE请求
    Route::delete('user/:id', 'api/user/delete');
});

return [

];
