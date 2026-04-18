<?php
// 极梦账号管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\JimengAccountModel;

class JimengAccount extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = JimengAccountModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_jimeng_account', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/JimengAccountModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mac_address', 'MAC地址', 'text.edit'],
                ['uid', '用户ID', 'text.edit'],
                ['tip', '提示词', 'text.edit'],
                ['points', '积分', 'number'],
                ['package_start_time', '套餐开始时间'],
                ['package_end_time', '套餐结束时间'],
                ['status', '状态', 'switch'],
                ['is_login_issued', '登录状态', 'text.edit'],
                ['current_task_count', '当前任务数', 'number'],
                ['reported_task_count', '上报任务数', 'number'],
                ['login_image', '登录图片', 'img_url'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'mac_address', 'MAC地址'],
                ['text', 'uid', '用户ID']
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
            
            $r = JimengAccountModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('login_image', '登录图片', '')
            ->addFormItems([
                ['text', 'mac_address', 'MAC地址', '请输入MAC地址', '', 'required'],
                ['text', 'uid', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'points', '积分', '请输入积分', 0],
                ['datetime', 'package_start_time', '套餐开始时间'],
                ['datetime', 'package_end_time', '套餐结束时间'],
                ['select', 'status', '状态', '', [0 => '正常', 1 => '禁用'], 0],
                ['select', 'is_login_issued', '是否下发登录', '', [0 => '未下发', 1 => '已下发'], 0],
                ['number', 'current_task_count', '当前任务数', '请输入当前任务数', 0],
                ['number', 'reported_task_count', '上报任务数', '请输入上报任务数', 0]
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

            $r = JimengAccountModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = JimengAccountModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addOssImage('login_image', '登录图片', '')
            ->addFormItems([
                ['text', 'mac_address', 'MAC地址', '请输入MAC地址', '', 'required'],
                ['text', 'uid', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'points', '积分', '请输入积分'],
                ['datetime', 'package_start_time', '套餐开始时间'],
                ['datetime', 'package_end_time', '套餐结束时间'],
                ['select', 'status', '状态', '', [0 => '正常', 1 => '禁用']],
                ['select', 'is_login_issued', '是否下发登录', '', [0 => '未下发', 1 => '已下发']],
                ['number', 'current_task_count', '当前任务数', '请输入当前任务数'],
                ['number', 'reported_task_count', '上报任务数', '请输入上报任务数']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = JimengAccountModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 