<?php
// 任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\RnhubTaskModel;

class RnhubTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = RnhubTaskModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_rnhub_tasks', $map);
        
        // 格式化耗时显示（毫秒转秒）
        foreach ($data_list as &$item) {
            if ($item['cost_time'] > 0) {
                $item['cost_time_format'] = round($item['cost_time'] / 1000, 2) . '秒';
            } else {
                $item['cost_time_format'] = '-';
            }
        }
        
        return ZBuilder::make('table')
            ->setTableName('video/RnhubTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['task_id', '任务标识', 'text'],
                ['rn_task_id', '任务ID', 'text'],
                ['webapp_id', 'Web应用ID'],
                ['client_id', '客户端ID'],
                ['workflow_id', '工作流ID'],
                ['api_key', 'API密钥', 'text'],
                ['status', '状态', 'status', '', RnhubTaskModel::getStatusList()],
                ['cost_time_format', '任务耗时'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['completed_at', '完成时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'task_id', '任务标识'],
                ['text', 'rn_task_id', '任务ID'],
                ['text', 'api_key', 'API密钥'],
                ['select', 'status', '状态', '', RnhubTaskModel::getStatusList()],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add', ['title'=>'新增']) // 添加新增按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
    
    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            // 添加创建时间和更新时间
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            
            // 验证数据
            if (empty($data['task_id'])) {
                $this->error('任务标识不能为空');
            }
            if (empty($data['user_id'])) {
                $this->error('用户ID不能为空');
            }
            
            // 设置默认值
            if (!isset($data['cost_time'])) {
                $data['cost_time'] = 0;
            }
            if (empty($data['status'])) {
                $data['status'] = RnhubTaskModel::STATUS_WAITING;
            }
            
            $r = DB::connect('translate')->table('ts_rnhub_tasks')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增任务')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID'],
                ['text', 'task_id', '任务标识', '请输入任务标识(UUID)'],
                ['text', 'rn_task_id', 'RunningHub任务ID', '请输入RunningHub任务ID'],
                ['number', 'webapp_id', 'Web应用ID', '请输入Web应用ID'],
                ['text', 'client_id', '客户端ID', '请输入客户端ID'],
                ['number', 'workflow_id', '工作流ID', '请输入工作流ID'],
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['select', 'status', '状态', '', RnhubTaskModel::getStatusList(), RnhubTaskModel::STATUS_WAITING],
                ['textarea', 'prompt_tips', '提示信息', '请输入提示信息'],
                ['textarea', 'msg', '错误信息', '请输入错误信息'],
                ['number', 'cost_time', '任务耗时(毫秒)', '请输入任务耗时', 0],
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
            
            // 更新时间
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 如果状态改为完成，记录完成时间
            if (isset($data['status']) && $data['status'] == RnhubTaskModel::STATUS_COMPLETED) {
                if (empty($data['completed_at'])) {
                    $data['completed_at'] = date('Y-m-d H:i:s');
                }
            }

            // 验证数据
            if (empty($data['task_id'])) {
                $this->error('任务标识不能为空');
            }

            // 更新数据
            $r = RnhubTaskModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = RnhubTaskModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑任务') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID'],
                ['text', 'task_id', '任务标识', '请输入任务标识(UUID)'],
                ['text', 'rn_task_id', 'RunningHub任务ID', '请输入RunningHub任务ID'],
                ['number', 'webapp_id', 'Web应用ID', '请输入Web应用ID'],
                ['text', 'client_id', '客户端ID', '请输入客户端ID'],
                ['number', 'workflow_id', '工作流ID', '请输入工作流ID'],
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['select', 'status', '状态', '', RnhubTaskModel::getStatusList()],
                ['textarea', 'prompt_tips', '提示信息', '请输入提示信息'],
                ['textarea', 'msg', '错误信息', '请输入错误信息'],
                ['datetime', 'completed_at', '完成时间', '请选择完成时间'],
                ['number', 'cost_time', '任务耗时(毫秒)', '请输入任务耗时'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}

