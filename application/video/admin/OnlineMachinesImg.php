<?php
// 在线设备图片记录
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class OnlineMachinesImg extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_online_machines_img')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_online_machines_img', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/OnlineMachinesImgModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mac_address','MAC地址'],
                ['img_url','图片地址', 'img_url'],
                // ['msg','消息内容'],
                ['time', '记录时间'],
                ['right_button', '操作', 'btn'], // 新增操作列
            ])
            ->setSearchArea([  
                ['text', 'mac_address', 'MAC地址'],
                ['datetime', 'time', '记录时间', '', 'YYYY-MM-DD HH:mm:ss'],
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('custom', [
                'title' => '图片轮播',
                'icon' => 'fa fa-image',
                'href' => url('showImgs', ['mac_address' => '__mac_address__'])
            ]) // 新增右侧按钮
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
           
           $r = DB::connect('translate')->table('ts_online_machines_img')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }
                  
       // 显示添加页面
       return ZBuilder::make('form')
            ->addFormItems([
               ['text', 'mac_address', 'MAC地址'],      
               ['image', 'img_url', '图片地址'],      
               ['textarea', 'msg', '消息内容'],
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

           $r = DB::connect('translate')->table('ts_online_machines_img')->where('id',$id)->update($data);
           if ($r) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }

       $info = DB::connect('translate')->table('ts_online_machines_img')->where('id',$id)->find();

       return ZBuilder::make('form')
            ->addFormItems([
               ['text', 'mac_address', 'MAC地址'],      
               ['image', 'img_url', '图片地址'],      
               ['textarea', 'msg', '消息内容'],
            ])
           ->setFormData($info)
           ->fetch();
   }

   public function showImgs($mac_address = null)
   {
       if (!$mac_address) {
           $this->error('缺少mac地址参数');
       }
       // 查询100条记录
       $list = DB::connect('translate')->table('ts_online_machines_img')
           ->where('mac_address', $mac_address)
           ->order('time desc')
           ->limit(100)
           ->select();
       if (!$list) {
           $this->error('没有找到相关图片');
       }
       // 渲染自定义页面
       $imgs = array_map(function($item) {
           return [
               'img_url' => $item['img_url'],
               'time' => $item['time']
           ];
       }, $list);
       // 传递到模板
       $this->assign('imgs', $imgs);
       $this->assign('mac_address', $mac_address);
       return $this->fetch('show_imgs');
   }
} 