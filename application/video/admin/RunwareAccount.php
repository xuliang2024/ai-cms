<?php
// Runware账号管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\RunwareAccountModel;

class RunwareAccount extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = RunwareAccountModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_runware_account', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/RunwareAccountModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '账号名称'],
                ['api_key', 'API密钥', 'text'],
                ['balance', '余额', 'text'],
                ['total_cost', '总消费', 'text'],
                ['use_cnt', '使用次数', 'text'],
                ['error_cnt', '错误次数', 'text'],
                ['test_cnt', '测试次数', 'text'],
                ['status', '状态', 'switch'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '账号名称'],
                ['text', 'api_key', 'API密钥'],
                ['select', 'status', '状态', '', RunwareAccountModel::getStatusList()],
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
            if (empty($data['name'])) {
                $this->error('账号名称不能为空');
            }
            if (empty($data['api_key'])) {
                $this->error('API密钥不能为空');
            }
            
            // 检查API密钥是否已存在
            $exists = DB::connect('translate')->table('ts_runware_account')
                ->where('api_key', $data['api_key'])
                ->find();
            if ($exists) {
                $this->error('该API密钥已存在');
            }
            
            // 设置默认值
            if (!isset($data['balance'])) {
                $data['balance'] = 0.0;
            }
            if (!isset($data['total_cost'])) {
                $data['total_cost'] = 0.0;
            }
            if (!isset($data['use_cnt'])) {
                $data['use_cnt'] = 0;
            }
            if (!isset($data['error_cnt'])) {
                $data['error_cnt'] = 0;
            }
            if (!isset($data['test_cnt'])) {
                $data['test_cnt'] = 0;
            }
            
            $r = DB::connect('translate')->table('ts_runware_account')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增Runware账号')
            ->addFormItems([
                ['text', 'name', '账号名称', '请输入账号名称'],
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['number', 'balance', '余额', '请输入余额', 0, 'step="0.01"'],
                ['number', 'total_cost', '总消费', '请输入总消费', 0, 'step="0.01"'],
                ['number', 'use_cnt', '使用次数', '请输入使用次数', 0],
                ['number', 'error_cnt', '错误次数', '请输入错误次数', 0],
                ['number', 'test_cnt', '测试次数', '请输入测试次数', 0],
                ['radio', 'status', '状态', '', ['禁用', '正常'], 1],
                ['textarea', 'remark', '备注', '请输入备注信息'],
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
            if (empty($data['name'])) {
                $this->error('账号名称不能为空');
            }
            if (empty($data['api_key'])) {
                $this->error('API密钥不能为空');
            }
            
            // 检查API密钥是否被其他记录使用
            $exists = RunwareAccountModel::where('api_key', $data['api_key'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                $this->error('该API密钥已被其他账号使用');
            }

            // 更新数据
            $r = RunwareAccountModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = RunwareAccountModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑Runware账号') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['text', 'name', '账号名称', '请输入账号名称'],
                ['text', 'api_key', 'API密钥', '请输入API密钥'],
                ['number', 'balance', '余额', '请输入余额', '', 'step="0.01"'],
                ['number', 'total_cost', '总消费', '请输入总消费', '', 'step="0.01"'],
                ['number', 'use_cnt', '使用次数', '请输入使用次数'],
                ['number', 'error_cnt', '错误次数', '请输入错误次数'],
                ['number', 'test_cnt', '测试次数', '请输入测试次数'],
                ['radio', 'status', '状态', '', ['禁用', '正常']],
                ['textarea', 'remark', '备注', '请输入备注信息'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}

