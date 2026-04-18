<?php
// API密钥管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\RnhubApiKeyModel;

class RnhubApiKey extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = RnhubApiKeyModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_rnhub_api_keys', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/RnhubApiKeyModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['api_key', 'API密钥'],
                ['remark', '备注信息'],
                ['remain_coins', '剩余积分'],
                ['max_concurrent_tasks', '最大并发任务数','text.edit'],
                ['current_running_tasks', '当前运行任务数','text.edit'],
                ['total_tasks', '总任务数','text.edit'],
                ['is_active', '状态', 'switch'],
                ['is_public','是否公开','switch'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['last_used_at', '最后使用时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'api_key', 'API密钥'],
                ['text', 'remark', '备注信息'],
                ['select', 'is_active', '状态', '', [0 => '禁用', 1 => '启用']],
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
            if (empty($data['api_key'])) {
                $this->error('API密钥不能为空');
            }
            
            $r = DB::connect('translate')->table('ts_rnhub_api_keys')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增API密钥')
            ->addFormItems([
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['text', 'remark', '备注信息', '请输入备注信息'],
                ['number', 'remain_coins', '剩余积分', '请输入剩余积分', 0],
                ['number', 'max_concurrent_tasks', '最大并发任务数', '请输入最大并发任务数', 3],
                ['number', 'current_running_tasks', '当前运行任务数', '当前运行任务数', 0],
                ['number', 'total_tasks', '总任务数', '请输入总任务数', 0],
                ['radio', 'is_active', '状态', '', ['禁用', '启用'], 1]
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

            // 验证数据
            if (empty($data['api_key'])) {
                $this->error('API密钥不能为空');
            }

            // 更新数据
            $r = RnhubApiKeyModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = RnhubApiKeyModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑API密钥') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['text', 'remark', '备注信息', '请输入备注信息'],
                ['number', 'remain_coins', '剩余积分', '请输入剩余积分'],
                ['number', 'max_concurrent_tasks', '最大并发任务数', '请输入最大并发任务数'],
                ['number', 'current_running_tasks', '当前运行任务数', '请输入当前运行任务数'],
                ['number', 'total_tasks', '总任务数', '请输入总任务数'],
                ['radio', 'is_active', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
} 