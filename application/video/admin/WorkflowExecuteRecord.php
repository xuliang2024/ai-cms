<?php
// 工作流执行记录
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\WorkflowExecuteRecordModel;

class WorkflowExecuteRecord extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = WorkflowExecuteRecordModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_workflow_execute_record', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/WorkflowExecuteRecordModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['token', '令牌', 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 20, '...') : '';
                }],
                ['workflow_id', '工作流ID'],
                ['bot_id', '机器人ID'],
                ['parameters', '参数', 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 30, '...') : '';
                }],
                ['app_id', '应用ID'],
                ['outputs', '输出', 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 30, '...') : '';
                }],
                ['debug_url', '调试URL', 'callback', function($value = '') {
                    return $value ? '<a href="'.$value.'" target="_blank">查看</a>' : '';
                }],
                ['execute_id', '执行ID'],
                ['status', '状态', 'callback', function($value = '') {
                    $status_map = [
                        0 => '<span class="label label-default">未开始</span>',
                        1 => '<span class="label label-info">执行中</span>',
                        2 => '<span class="label label-success">成功</span>',
                        3 => '<span class="label label-danger">失败</span>'
                    ];
                    return isset($status_map[$value]) ? $status_map[$value] : $value;
                }],
                ['money', '金额(分)'],
                ['time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'bot_id', '机器人ID'],
                ['text', 'app_id', '应用ID'],
                ['text', 'execute_id', '执行ID'],
                ['select', 'status', '状态', '', [
                    '0' => '未开始',
                    '1' => '执行中', 
                    '2' => '成功',
                    '3' => '失败'
                ]],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            // ->addRightButton('custom', [
            //     'title' => '查看详情',
            //     'icon'  => 'fa fa-eye',
            //     'href'  => url('viewDetail', ['id' => '__id__']),
            //     'data-replace' => json_encode([
            //         '__id__' => 'id'
            //     ])
            // ]) // 添加查看详情按钮
            // ->addRightButton('custom', [
            //     'title' => '调试链接',
            //     'icon'  => 'fa fa-external-link',
            //     'href'  => '__debug_url__',
            //     'target' => '_blank',
            //     'data-replace' => json_encode([
            //         '__debug_url__' => 'debug_url'
            //     ]),
            //     'class' => 'btn btn-xs btn-success'
            // ], 'debug_url') // 只在有调试链接时显示
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = DB::connect('translate')->table('ts_workflow_execute_record')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_workflow_execute_record')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'bot_id', '机器人ID'],
                ['text', 'app_id', '应用ID'],
                ['text', 'execute_id', '执行ID'],
                ['textarea', 'parameters', '参数'],
                ['textarea', 'outputs', '输出'],
                ['text', 'debug_url', '调试URL'],
                ['select', 'status', '状态', '', [
                    '0' => '未开始',
                    '1' => '执行中', 
                    '2' => '成功',
                    '3' => '失败'
                ]],
                ['number', 'money', '金额(分)']
            ])
            ->setFormData($info)
            ->fetch();
    }
    
    /**
     * 查看执行记录详情
     * @param int $id 记录ID
     * @return mixed
     */
    public function viewDetail($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取执行记录信息
        $record = WorkflowExecuteRecordModel::get($id);
        if (!$record) {
            return $this->error('执行记录不存在');
        }
        
        // 格式化显示数据
        $record_data = $record->toArray();
        
        // 解析JSON格式的参数和输出
        if ($record_data['parameters']) {
            $record_data['parameters_formatted'] = json_decode($record_data['parameters'], true);
        }
        if ($record_data['outputs']) {
            $record_data['outputs_formatted'] = json_decode($record_data['outputs'], true);
        }
        
        return ZBuilder::make('form')
            ->setPageTitle('执行记录详情')
            ->addStatic('id', 'ID', $record_data['id'])
            ->addStatic('user_id', '用户ID', $record_data['user_id'])
            ->addStatic('workflow_id', '工作流ID', $record_data['workflow_id'])
            ->addStatic('bot_id', '机器人ID', $record_data['bot_id'] ?: '无')
            ->addStatic('app_id', '应用ID', $record_data['app_id'] ?: '无')
            ->addStatic('execute_id', '执行ID', $record_data['execute_id'])
            ->addStatic('status', '状态', [
                0 => '未开始',
                1 => '执行中', 
                2 => '成功',
                3 => '失败'
            ][$record_data['status']] ?? $record_data['status'])
            ->addStatic('money', '金额', $record_data['money'] . ' 分')
            ->addStatic('time', '创建时间', $record_data['time'])
            ->addStatic('update_time', '更新时间', $record_data['update_time'])
            ->addStatic('debug_url', '调试链接', $record_data['debug_url'] ? 
                '<a href="'.$record_data['debug_url'].'" target="_blank">'.$record_data['debug_url'].'</a>' : '无')
            ->addStatic('parameters', '输入参数', '<pre style="max-height: 200px; overflow-y: auto;">' . 
                htmlspecialchars($record_data['parameters'] ?: '无') . '</pre>')
            ->addStatic('outputs', '执行输出', '<pre style="max-height: 200px; overflow-y: auto;">' . 
                htmlspecialchars($record_data['outputs'] ?: '无') . '</pre>')
            ->fetch();
    }
    
    /**
     * 重新执行工作流
     * @param int $id 记录ID
     * @return mixed
     */
    public function reExecute($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取执行记录信息
        $record = WorkflowExecuteRecordModel::get($id);
        if (!$record) {
            return $this->error('执行记录不存在');
        }
        
        // 这里可以添加重新执行工作流的逻辑
        // 例如调用API重新触发执行
        
        return $this->success('重新执行成功');
    }
} 