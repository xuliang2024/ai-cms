<?php
// 域名授权管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DomainAuthModel;

class DomainAuth extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DomainAuthModel::where($map)
            ->order('created_at desc')
            ->paginate();

        cookie('ts_domain_auth', $map);
        
        // 格式化授权状态显示
        $now = date('Y-m-d H:i:s');
        foreach ($data_list as &$item) {
            // 计算授权有效性
            $item['auth_status'] = '有效';
            if ($item['status'] == DomainAuthModel::STATUS_DISABLED) {
                $item['auth_status'] = '已禁用';
            } elseif ($item['start_time'] && $item['start_time'] > $now) {
                $item['auth_status'] = '未开始';
            } elseif ($item['end_time'] && $item['end_time'] < $now) {
                $item['auth_status'] = '已过期';
            }
        }
        
        return ZBuilder::make('table')
            ->setTableName('video/DomainAuthModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['domain', '授权域名', 'text'],
                ['status', '状态', 'status', '', DomainAuthModel::getStatusList()],
                ['auth_status', '授权状态'],
                ['start_time', '授权开始时间'],
                ['end_time', '授权结束时间'],
                ['remark', '备注'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'domain', '授权域名'],
                ['select', 'status', '状态', '', DomainAuthModel::getStatusList()],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'end_time', '授权结束时间', '', '', ['format' => 'YYYY-MM-DD']],
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
            if (empty($data['domain'])) {
                $this->error('授权域名不能为空');
            }
            
            // 检查域名是否已存在
            $exists = DomainAuthModel::where('domain', $data['domain'])->find();
            if ($exists) {
                $this->error('该域名已存在');
            }
            
            // 设置默认值
            if (!isset($data['status'])) {
                $data['status'] = DomainAuthModel::STATUS_ENABLED;
            }
            
            $r = DB::connect('translate')->table('ts_domain_auth')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增域名授权')
            ->addFormItems([
                ['text', 'domain', '授权域名', '请输入授权域名，如: example.com'],
                ['select', 'status', '状态', '', DomainAuthModel::getStatusList(), DomainAuthModel::STATUS_ENABLED],
                ['datetime', 'start_time', '授权开始时间', '留空表示立即生效'],
                ['datetime', 'end_time', '授权结束时间', '留空表示永久有效'],
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
            if (empty($data['domain'])) {
                $this->error('授权域名不能为空');
            }
            
            // 检查域名是否已存在（排除当前记录）
            $exists = DomainAuthModel::where('domain', $data['domain'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                $this->error('该域名已存在');
            }

            // 更新数据
            $r = DomainAuthModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = DomainAuthModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑域名授权') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['text', 'domain', '授权域名', '请输入授权域名，如: example.com'],
                ['select', 'status', '状态', '', DomainAuthModel::getStatusList()],
                ['datetime', 'start_time', '授权开始时间', '留空表示立即生效'],
                ['datetime', 'end_time', '授权结束时间', '留空表示永久有效'],
                ['textarea', 'remark', '备注', '请输入备注信息'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}

