<?php
// 任务中心管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\TaskCenterModel;

class TaskCenter extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = TaskCenterModel::where($map)
        ->order('sort_order desc')
        ->paginate();

        cookie('ts_task_center', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/TaskCenterModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '任务名称', 'text.edit'],
                ['description', '任务描述', 'text.edit'],
                ['progress', '任务进度', 'number'],
                ['status', '任务状态', 'select', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['reward_type', '奖励类型', 'select', [1 => '现金', 2 => '会员']],
                ['cash_amount', '现金奖励(分)', 'number'],
                ['vip_days', '会员天数', 'number'],
                ['goto_url', '跳转链接', 'text.edit'],
                ['sort_order', '排序权重', 'number'],
                ['is_show', '是否显示', 'switch'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '任务名称'],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['select', 'reward_type', '奖励类型', '', [1 => '现金', 2 => '会员']]
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
            
            $r = TaskCenterModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '任务名称', '请输入任务名称', '', 'required'],
                ['textarea', 'description', '任务描述'],
                ['number', 'progress', '任务进度', '范围0-100', '', 0],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成'], 0],
                ['select', 'reward_type', '奖励类型', '', [1 => '现金', 2 => '会员'], 1],
                ['number', 'cash_amount', '现金奖励(分)', '奖励类型为现金时填写', '', 0],
                ['number', 'vip_days', '会员天数', '奖励类型为会员时填写', '', 0],
                ['text', 'goto_url', '跳转链接', '完成任务后跳转的URL'],
                ['number', 'sort_order', '排序权重', '数值越大排序越靠前', '', 0],
                ['switch', 'is_show', '是否显示', '', ['0' => '否', '1' => '是'], 1]
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

            $r = TaskCenterModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = TaskCenterModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '任务名称', '请输入任务名称', '', 'required'],
                ['textarea', 'description', '任务描述'],
                ['number', 'progress', '任务进度', '范围0-100'],
                ['select', 'status', '任务状态', '', [0 => '未开始', 1 => '进行中', 2 => '完成']],
                ['select', 'reward_type', '奖励类型', '', [1 => '现金', 2 => '会员']],
                ['number', 'cash_amount', '现金奖励(分)', '奖励类型为现金时填写'],
                ['number', 'vip_days', '会员天数', '奖励类型为会员时填写'],
                ['text', 'goto_url', '跳转链接', '完成任务后跳转的URL'],
                ['number', 'sort_order', '排序权重', '数值越大排序越靠前'],
                ['switch', 'is_show', '是否显示', '', ['0' => '否', '1' => '是']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = TaskCenterModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 