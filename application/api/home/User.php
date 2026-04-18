<?php
namespace app\api\home;

use think\Controller;
use think\Request;
use think\facade\Validate;

/**
 * 用户API控制器
 * Class User
 * @package app\api\home
 */
class User extends Controller
{
    /**
     * 默认方法，用于处理 /api/user/:id 请求
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function index($id)
    {
        // 模拟从数据库获取用户数据
        $user = [
            'id' => $id,
            'name' => '测试用户',
            'email' => 'test@example.com',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $user
        ]);
    }
    
    /**
     * 获取用户信息
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function getInfo($id)
    {
        // 模拟从数据库获取用户数据
        $user = [
            'id' => $id,
            'name' => '测试用户',
            'email' => 'test@example.com',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $user
        ]);
    }
    
    /**
     * 创建用户
     * @param Request $request
     * @return \think\response\Json
     */
    public function create(Request $request)
    {
        // 获取POST数据
        $data = $request->post();
        
        // 数据验证
        $validate = Validate::make([
            'name' => 'require|max:50',
            'email' => 'require|email',
            'password' => 'require|min:6'
        ]);
        
        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError(),
                'data' => null
            ]);
        }
        
        // 模拟创建用户操作
        // 在实际应用中，这里应该是数据库插入操作
        $userId = mt_rand(1000, 9999); // 模拟生成ID
        
        return json([
            'code' => 200,
            'message' => '创建成功',
            'data' => [
                'id' => $userId,
                'name' => $data['name'],
                'email' => $data['email'],
                'create_time' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    /**
     * 更新用户信息
     * @param Request $request
     * @param int $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        // 获取PUT数据
        $data = $request->put();
        
        // 数据验证
        $validate = Validate::make([
            'name' => 'max:50',
            'email' => 'email'
        ]);
        
        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError(),
                'data' => null
            ]);
        }
        
        // 模拟更新用户操作
        return json([
            'code' => 200,
            'message' => '更新成功',
            'data' => [
                'id' => $id,
                'name' => isset($data['name']) ? $data['name'] : '测试用户',
                'email' => isset($data['email']) ? $data['email'] : 'test@example.com',
                'update_time' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    /**
     * 删除用户
     * @param int $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        // 模拟删除用户操作
        return json([
            'code' => 200,
            'message' => '删除成功',
            'data' => [
                'id' => $id,
                'delete_time' => date('Y-m-d H:i:s')
            ]
        ]);
    }
} 