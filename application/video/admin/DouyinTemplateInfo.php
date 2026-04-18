<?php
// 抖音模版信息管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DouyinTemplateInfoModel;

class DouyinTemplateInfo extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DouyinTemplateInfoModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_douyin_template_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/DouyinTemplateInfoModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['home_url', '抖音主页URL', 'text.edit'],
                ['douyin_uid', '抖音用户ID'],
                ['douyin_img_url', '抖音图片', 'img_url'],
                ['douyin_url', '抖音URL', 'text.edit'],
                ['jy_uid', '剪映用户ID'],
                ['jy_uid_img_url', '剪映头像', 'img_url'],
                ['status', '状态', 'select.edit', '', [
                    0 => '未写入',
                    1 => '已写入'
                ]],
                ['error_msg', '错误信息'],
                ['time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'douyin_uid', '抖音用户ID'],
                ['select', 'status', '状态', '', '', [
                    '' => '全部状态',
                    0 => '未写入',
                    1 => '已写入'
                ]],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add', ['title'=>'新增']) // 添加新增按钮
            ->addTopButton('download', [
                'title' => '导出Excel',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/douyin_template_info/download.html?' . $this->request->query()
            ]) // 添加导出按钮
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
            $data['time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            
            // 验证数据
            if (empty($data['user_id'])) {
                $this->error('用户ID不能为空');
            }
            
            $r = DB::connect('translate')->table('ts_douyin_template_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增抖音模版信息')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 0],
                ['text', 'home_url', '抖音主页URL', '请输入抖音主页URL'],
                ['text', 'douyin_uid', '抖音用户ID', '请输入抖音用户ID'],
                ['text', 'douyin_img_url', '抖音图片URL', '请输入抖音图片URL'],
                ['text', 'douyin_url', '抖音URL', '请输入抖音URL'],
                ['text', 'jy_uid', '剪映用户ID', '请输入剪映用户ID'],
                ['text', 'jy_uid_img_url', '剪映头像URL', '请输入剪映头像URL'],
                ['radio', 'status', '状态', '', ['未写入', '已写入'], 0],
                ['textarea', 'error_msg', '错误信息', '请输入错误信息']
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

            // 验证数据
            if (empty($data['user_id'])) {
                $this->error('用户ID不能为空');
            }

            // 添加更新时间
            $data['update_time'] = date('Y-m-d H:i:s');

            // 更新数据
            $r = DouyinTemplateInfoModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = DouyinTemplateInfoModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑抖音模版信息') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID'],
                ['text', 'home_url', '抖音主页URL', '请输入抖音主页URL'],
                ['text', 'douyin_uid', '抖音用户ID', '请输入抖音用户ID'],
                ['text', 'douyin_img_url', '抖音图片URL', '请输入抖音图片URL'],
                ['text', 'douyin_url', '抖音URL', '请输入抖音URL'],
                ['text', 'jy_uid', '剪映用户ID', '请输入剪映用户ID'],
                ['text', 'jy_uid_img_url', '剪映头像URL', '请输入剪映头像URL'],
                ['radio', 'status', '状态', '', ['未写入', '已写入'], 0],
                ['textarea', 'error_msg', '错误信息', '请输入错误信息']
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
    
    /**
     * 导出Excel
     */
    public function download()
    {
        // 获取ids参数
        $ids = input('get.ids');
        
        if ($ids) {
            // 将ids字符串分割为数组
            $ids_array = explode(',', $ids);
            // 查询指定ID的数据
            $data_list = DouyinTemplateInfoModel::whereIn('id', $ids_array)->select();
        } else {
            // 获取当前筛选条件
            $map = $this->getMap();
            // 查询所有符合条件的数据
            $data_list = DouyinTemplateInfoModel::where($map)
                ->order('time desc')
                ->select();
        }
        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['user_id', 15, '用户ID'],
            ['home_url', 50, '抖音主页URL'],
            ['douyin_img_url', 50, '抖音图片'],
            ['jy_uid_img_url', 50, '剪映头像']
        ];
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['抖音模版信息导出_' . date('Y-m-d H:i:s'), $cellName, $data_list]);
    }
}