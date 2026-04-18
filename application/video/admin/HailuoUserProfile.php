<?php
// 海螺用户资料管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\HailuoUserProfileModel;

class HailuoUserProfile extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = HailuoUserProfileModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_hailuo_user_profiles', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/HailuoUserProfileModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID', 'text.edit'],
                ['u_id', 'U_ID', 'text.edit'],
                ['avatar', '头像', 'img_url'],
                ['code', '代码', 'text.edit'],
                ['name', '姓名', 'text.edit'],
                ['real_user_id', '真实用户ID'],
                ['is_online', '在线状态', 'switch'],
                ['is_public', '公开状态', 'switch'],
                ['hl_user_id', '海螺用户ID'],
                ['is_new_user', '新用户'],
                ['concurrency_limit', '并发限制', 'text.edit'],
                ['work_count', '工作数量', 'text.edit'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'u_id', 'U_ID'],
                ['text', 'user_id', '用户ID']
                
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->fetch(); // 渲染页面
    }

    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $r = HailuoUserProfileModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('avatar', '头像', '')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'u_id', 'U_ID', '请输入U_ID', '', 'required'],
                ['text', 'code', '代码', '请输入代码'],
                ['text', 'name', '姓名', '请输入姓名'],
                ['text', 'real_user_id', '真实用户ID', '请输入真实用户ID'],
                ['select', 'is_online', '在线状态', '', [0 => '离线', 1 => '在线'], 0],
                ['select', 'is_public', '公开状态', '', [0 => '私有', 1 => '公开'], 0],
                ['text', 'hl_user_id', '海螺用户ID', '请输入海螺用户ID'],
                ['select', 'is_new_user', '新用户', '', [0 => '否', 1 => '是'], 0],
                ['textarea', 'token', 'Token', '请输入Token', '', 'required'],
                ['number', 'concurrency_limit', '并发限制', '请输入并发限制', 3],
                ['number', 'work_count', '工作数量', '请输入工作数量', 0]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['updated_at'] = date('Y-m-d H:i:s');

            $r = HailuoUserProfileModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = HailuoUserProfileModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addOssImage('avatar', '头像', '')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'u_id', 'U_ID', '请输入U_ID', '', 'required'],
                ['text', 'code', '代码', '请输入代码'],
                ['text', 'name', '姓名', '请输入姓名'],
                ['text', 'real_user_id', '真实用户ID', '请输入真实用户ID'],
                ['select', 'is_online', '在线状态', '', [0 => '离线', 1 => '在线']],
                ['select', 'is_public', '公开状态', '', [0 => '私有', 1 => '公开']],
                ['text', 'hl_user_id', '海螺用户ID', '请输入海螺用户ID'],
                ['select', 'is_new_user', '新用户', '', [0 => '否', 1 => '是']],
                ['textarea', 'token', 'Token', '请输入Token', '', 'required'],
                ['number', 'concurrency_limit', '并发限制', '请输入并发限制'],
                ['number', 'work_count', '工作数量', '请输入工作数量']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = HailuoUserProfileModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 