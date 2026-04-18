<?php
// 分成明细表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class DraftTemplate extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_draft_template')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_draft_template', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/DraftTemplateModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                
                ['id', 'ID'],
                    ['title','标题'],
                    ['user_id','用户ID'],
                    ['img_url','图片链接','img_url'],
                    ['video_url', '视频链接','image_video'],
                    ['url', '跳转链接'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],
                    ['time', '时间'],
                    ['right_button', '操作', 'btn']  
                    
                
            ])
             ->setSearchArea([  
                ['text', 'title', '标题'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }



    public function add() 
    {

       // 保存数据
       if ($this->request->isPost()) {
           // 表单数据
           $data = $this->request->post();
           
           $r = DB::connect('translate')->table('ts_draft_template')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }

                  
       // 显示添加页面
       return ZBuilder::make('form')
           ->addOssImage('img_url', '图片', '', '', '', '', '', ['size' => '50,50'])
           ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
               // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
               ['text', 'title', '标题'],       
               ['text', 'url', '跳转路径'],      
              
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

           $r = DB::connect('translate')->table('ts_draft_template')->where('id',$id)->update($data);
           if ($r) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }


       $info = DB::connect('translate')->table('ts_draft_template')->where('id',$id)->find();

       return ZBuilder::make('form')

            ->addOssImage('img_url', '图片', '', '', '', '', '', ['size' => '50,50'])
            ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'title', '标题'],       
                ['text', 'url', '跳转路径'],      
                
            ])
       
           ->setFormData($info)
           ->fetch();
   }
     


}
