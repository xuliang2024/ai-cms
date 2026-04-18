<?php
// 用户任务完成状态记录管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\UserTaskRecordModel;
use app\video\model\TaskCenterModel;

class UserTaskRecord extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = UserTaskRecordModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_user_task_record', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/UserTaskRecordModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID', 'text.edit'],
                ['task_id', '任务ID', 'text.edit'],
                ['status', '任务状态', 'select', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['progress', '任务进度', 'number'],
                ['reward_status', '奖励状态', 'select', [0 => '未发放', 1 => '已发放']],
                ['reward_time', '奖励发放时间'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'task_id', '任务ID'],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['select', 'reward_status', '奖励状态', '', [0 => '未发放', 1 => '已发放']]
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
        // 获取所有任务列表
        $tasks = TaskCenterModel::column('id,name');
        $task_list = [];
        foreach ($tasks as $id => $name) {
            $task_list[$id] = $name;
        }
        
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 如果奖励状态为已发放，自动添加奖励发放时间
            if (isset($data['reward_status']) && $data['reward_status'] == 1) {
                $data['reward_time'] = date('Y-m-d H:i:s');
            }
            
            $r = UserTaskRecordModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['select', 'task_id', '任务ID', '请选择任务', $task_list, '', 'required'],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成'], 0],
                ['number', 'progress', '任务进度', '范围0-100', '', 0],
                ['select', 'reward_status', '奖励状态', '', [0 => '未发放', 1 => '已发放'], 0]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 获取所有任务列表
        $tasks = TaskCenterModel::column('id,name');
        $task_list = [];
        foreach ($tasks as $id => $name) {
            $task_list[$id] = $name;
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 获取原有记录
            $old_record = UserTaskRecordModel::where('id', $id)->find();
            
            // 如果奖励状态从未发放变为已发放，自动添加奖励发放时间
            if (isset($data['reward_status']) && $data['reward_status'] == 1 && $old_record['reward_status'] == 0) {
                $data['reward_time'] = date('Y-m-d H:i:s');
            }

            $r = UserTaskRecordModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = UserTaskRecordModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['select', 'task_id', '任务ID', '请选择任务', $task_list, '', 'required'],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['number', 'progress', '任务进度', '范围0-100'],
                ['select', 'reward_status', '奖励状态', '', [0 => '未发放', 1 => '已发放']],
                ['datetime', 'reward_time', '奖励发放时间', '自动添加，也可手动修改']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = UserTaskRecordModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 