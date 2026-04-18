<?php
// 发布任务表格
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class PublishTasks extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_publish_tasks')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_publish_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/PublishTasksModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['managed_account_id', '托管账号ID'],
                ['publish_time', '发布时间'],
                
                ['title', '标题'],
                ['msg', 'msg'],
                ['video_url', '视频地址','image_video'],
                ['cover_image', '图片','img_url'],
                ['finish_img', '截图','img_url'],
                
                // ['status', '状态','status','',[0=>'等待',1=>'处理',2=>'完成',3=>'失败']],
                ['status', '状态','text.edit'],
               
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'publish_time', '发布时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'managed_account_id', '托管账号ID'],
                ['text', 'status', '状态'],
                
              
            ])
            
            
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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
            
            $r = DB::connect('translate')->table('ts_publish_tasks')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'managed_account_id', '托管id'],
                ['text', 'video_url', '视频链接'],
                ['text', 'user_id', '用户id'],
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

            $r = DB::connect('translate')->table('ts_publish_tasks')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_publish_tasks')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'managed_account_id', '托管id'],
                ['text', 'video_url', '视频链接'],
                ['text', 'user_id', '用户id'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }




}
