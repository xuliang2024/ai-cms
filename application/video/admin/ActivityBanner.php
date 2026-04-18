<?php
// 活动横幅管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class ActivityBanner extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_activity_banner')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_activity_banner', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ActivityBannerModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['activity_type', '活动类型'],
                ['name', '活动名称'],
                ['desc', '活动描述'],
                ['sort','排序权重' ,'text.edit'],
                ['status', '状态', 'switch'],
                ['img_url', '图片地址', 'img_url'],
                ['goto_url','跳转链接','text.edit'],
                ['start_time', '开始时间'],
                ['end_time', '结束时间'],
                ['right_button', '操作', 'btn'],
            ])
            ->setSearchArea([  
                ['text', 'name', '活动名称'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']],
                ['text', 'activity_type', '活动类型'],
                ['daterange', 'start_time', '开始时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'end_time', '结束时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('add') // 添加新增按钮
            ->addRightButtons(['edit', 'delete']) // 添加右侧操作按钮
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
           
           // 设置创建时间
           $data['time'] = date('Y-m-d H:i:s');
           
           $r = DB::connect('translate')->table('ts_activity_banner')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }
                  
       // 显示添加页面
       return ZBuilder::make('form')
            ->addOssImage('img_url', '图片', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
               ['text', 'activity_type', '活动类型', '活动的类型标识'],      
               ['text', 'name', '活动名称', '活动的名称'],
               ['textarea', 'desc', '活动描述', '活动的详细描述'],
               ['text', 'goto_url', '跳转链接', '活动的跳转链接'],
               ['number', 'sort', '排序权重', '数值越大排序越靠前'],
               ['radio', 'status', '状态', '', [0 => '禁用', 1 => '启用'], 1],
               ['datetime', 'start_time', '开始时间', '', 'YYYY-MM-DD HH:mm:ss'],
               ['datetime', 'end_time', '结束时间', '', 'YYYY-MM-DD HH:mm:ss'],
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
           
           $r = DB::connect('translate')->table('ts_activity_banner')->where('id', $id)->update($data);
           if ($r !== false) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }

       $info = DB::connect('translate')->table('ts_activity_banner')->where('id', $id)->find();

       return ZBuilder::make('form')
            ->addOssImage('img_url', '图片', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
               ['hidden', 'id'],
               ['static', 'activity_type', '活动类型', '活动的类型标识'],      
               ['text', 'name', '活动名称', '活动的名称'],
               ['textarea', 'desc', '活动描述', '活动的详细描述'],
               ['text', 'goto_url', '跳转链接', '活动的跳转链接'],
               ['number', 'sort', '排序权重', '数值越大排序越靠前'],
               ['radio', 'status', '状态', '', [0 => '禁用', 1 => '启用']],
               ['datetime', 'start_time', '开始时间', '', 'YYYY-MM-DD HH:mm:ss'],
               ['datetime', 'end_time', '结束时间', '', 'YYYY-MM-DD HH:mm:ss'],
            ])
           ->setFormData($info)
           ->fetch();
   }
   
   public function delete($ids = null)
   {
       if ($ids === null) $this->error('缺少参数');
       
       $r = DB::connect('translate')->table('ts_activity_banner')->where('id', 'in', $ids)->delete();
       if ($r !== false) {
           $this->success('删除成功');
       } else {
           $this->error('删除失败');
       }
   }
} 