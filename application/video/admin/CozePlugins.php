<?php
// Coze插件管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CozePluginsModel;

class CozePlugins extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用模型进行查询
        $data_list = CozePluginsModel::where($map)
        ->order('sort_order asc, id desc')
        ->paginate();

        cookie('ts_coze_plugins', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CozePluginsModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['category_name', '插件类别'],
                ['category_desc', '插件类别描述'],
                ['name', '插件名称'],
                ['description', '插件描述'],
                ['icon', '图标', 'img_url'],
                ['resource_points', '资源点数/次','text.edit'],
                ['price', '标准价格','text.edit'],
                ['vip_price', 'VIP价格','text.edit'],
                ['svip_price', 'SVIP价格','text.edit'],
                ['status', '状态', 'switch'],
                ['sort_order', '排序','text.edit'],
                ['created_at', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '插件名称'],
                ['text', 'category_name', '插件类别'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']],
                ['select', 'is_sub_method', '是否子方法', '', [0 => '否', 1 => '是']],
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
            
            // 设置创建和更新时间
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证数据
            if (empty($data['name'])) {
                $this->error('插件名称不能为空');
            }
            
            $r = CozePluginsModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增插件')
            ->addOssImage('icon', '插件图标', '')
            ->addFormItems([
                ['text', 'category_name', '插件类别', '请输入插件类别名称'],
                ['text', 'category_desc', '插件类别描述', '请输入插件类别描述'],
                ['text', 'name', '插件名称', '请输入插件名称'],
                ['textarea', 'description', '插件描述', '请输入插件描述'],
                ['radio', 'is_sub_method', '是否子方法', '', ['否', '是'], 0],
                ['text', 'resource_points', '资源点数/次', '请输入消耗的资源点数'],
                ['text', 'price', '标准价格', '请输入标准价格'],
                ['text', 'vip_price', 'VIP会员价格', '请输入VIP会员价格'],
                ['text', 'svip_price', 'SVIP会员价格', '请输入SVIP会员价格'],
                ['number', 'sort_order', '排序顺序', '数字越小越靠前', 0],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
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
                $this->error('插件名称不能为空');
            }

            // 更新数据
            $r = CozePluginsModel::where('id', $id)->update($data);
            
            if ($r !== false) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = CozePluginsModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑插件') // 设置页面标题
            ->addOssImage('icon', '插件图标', '')
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['text', 'category_name', '插件类别', '请输入插件类别名称'],
                ['text', 'category_desc', '插件类别描述', '请输入插件类别描述'],
                ['text', 'name', '插件名称', '请输入插件名称'],
                ['textarea', 'description', '插件描述', '请输入插件描述'],
                ['radio', 'is_sub_method', '是否子方法', '', ['否', '是']],
                ['text', 'resource_points', '资源点数/次', '请输入消耗的资源点数'],
                ['text', 'price', '标准价格', '请输入标准价格'],
                ['text', 'vip_price', 'VIP会员价格', '请输入VIP会员价格'],
                ['text', 'svip_price', 'SVIP会员价格', '请输入SVIP会员价格'],
                ['number', 'sort_order', '排序顺序', '数字越小越靠前'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
} 