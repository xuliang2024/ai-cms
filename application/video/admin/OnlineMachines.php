<?php
// 分成明细表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class OnlineMachines extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_online_machines')->where($map)
        ->order('id desc')
        ->paginate(50);

        

        cookie('ts_online_machines', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/OnlineMachinesModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                
                ['id', 'ID'],
                    ['mac_address','mac'],
                    ['user_id','用户ID','text.edit'],
                    
                    ['name','名字', 'text.edit'],
                    ['restart','重启', 'switch'],
                    ['report_time','上报时间'],
                    ['right_button', '操作', 'btn'],
                    ['day_cnt','当日数据'],
                    ['running_cnt','处理中'],
                    ['hour_cnt','小时数'],
                    ['median_time','中位数'],
                    ['avg_time','平均数'],
                    ['jy_user','剪映信息', 'text.edit'],
                    ['status', '状态','switch'],
                    ['cnt', '次数'],
                    ['dispatch_cnt', '调度次数', 'text.edit'],
                    ['report_cnt', '上报次数', 'text.edit'],
                    
                    // ['status','状态','text.edit'],
                    
                    ['update_time','更新时间'],
                    ['create_time', '时间'],
                    
                    
            ])
             ->setSearchArea([  
                ['text', 'mac_address', 'mac'],
                ['text', 'name', '名字'],
                ['text', 'status', '状态']
              
            ])
            // ->addOrder('report_time') // 添加排序
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('edit', [
                'title' => '查看',
                'href'  => '/admin.php/video/online_machines_img/index.html?_s=mac_address=__mac_address__&_o=mac_address=eq|time=eq'
            ])
            ->addRightButton('showimgs', [
                'title' => '图片',
                'href'  => '/admin.php/video/online_machines_img/showimgs/mac_address/__mac_address__.html'
            ])
            ->addRightButton('task', [
                'title' => '查看任务',
                'href'  => '/admin.php/video/draft_list/index.html?_s=user_id=|mac_address=__mac_address__|draft_id=|dayid=|status=7|time=&_o=user_id=eq|mac_address=eq|draft_id=eq|dayid=eq|status=eq|time=between%20time'
            ])
          
            ->fetch(); // 渲染页面
    }

    public function add() 
    {

       // 保存数据
       if ($this->request->isPost()) {
           // 表单数据
           $data = $this->request->post();
           
           $r = DB::connect('translate')->table('ts_online_machines')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }

                  
       // 显示添加页面
       return ZBuilder::make('form')
            ->addFormItems([
               ['text', 'name', '标题'],       
               ['text', 'mac_address', 'mac地址'],      
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

           $r = DB::connect('translate')->table('ts_online_machines')->where('id',$id)->update($data);
           if ($r) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }


       $info = DB::connect('translate')->table('ts_online_machines')->where('id',$id)->find();

       return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'name', '标题'],       
               ['text', 'mac_address', 'mac地址'],      
                
            ])
       
           ->setFormData($info)
           ->fetch();
   }

    
}
