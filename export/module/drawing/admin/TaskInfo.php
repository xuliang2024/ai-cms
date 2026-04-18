<?php
//任务福利配置
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TaskInfo extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        $map[]=["p_user_id","=",get_pid()];
        $data_list = DB::table('ai_task_info')->where($map)
        ->order('time desc')
        ->paginate();
        
        cookie('ai_task_info', $map);
        
        
        return ZBuilder::make('table')
            ->setTableName('task_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['task_name', '任务名字'],
                    ['title', '标题'],
                    ['sub_title', '副标题'],
                    ['btn_title', '按钮标题'],
                    ['img', '任务图标','img_url'],
                    ['coin', '赠送金币'],
                    ['finish_cnt', '完成所需数量','text.edit'],
                    ['sort', '排序','text.edit'],
                    ['status', '状态','switch'],
                    ['repeat', 'vip等级'],
                    ['jumpurl', '跳转标识'],
                    ['dy_ad', '抖音广告'],
                    ['jump_type', '任务类型'],
                    ['time', '时间'],      
                    ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            ->addTopButton('add',['title'=>'新增'])
            ->addRightButtons(['edit','delete']) // 批量添加右侧按钮//,'delete'
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
            
            $r = DB::table('ai_task_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        
        // 显示添加页面
        return ZBuilder::make('form')
            
           
            ->addFormItems([
                    
                    ['text','task_name', '任务名字'],
                    ['text','title', '标题'],
                    ['text','sub_title', '副标题'],
                    ['text','btn_title', '按钮标题'],
                    ['text','img', '任务图标','img_url'],
                    ['text','coin', '赠送金币'],
                    ['text','finish_cnt', '完成所需数量'],
                    ['text','sort', '排序'],
                    ['text','status', '状态'],
                    ['text','repeat', 'vip等级'],
                    ['text','jumpurl', '跳转标识'],
                    ['textarea','attach', '附加配置'],
                    
                    ['text','dy_ad', '抖音广告'],
                    ['text','jump_type', '任务类型'],

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
        
            $r = DB::table('ai_task_info')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_task_info')->where('id',$id)->find();

        return ZBuilder::make('form')
            
            ->addFormItems([
                ['text','task_name', '任务名字'],
                ['text','title', '标题'],
                ['text','sub_title', '副标题'],
                ['text','btn_title', '按钮标题'],
                ['text','img', '任务图标','img_url'],
                ['text','coin', '赠送金币'],
                ['text','finish_cnt', '完成所需数量'],
                ['text','sort', '排序'],
                ['text','status', '状态'],
                ['text','repeat', 'vip等级'],
                ['text','jumpurl', '跳转标识'],
                ['textarea','attach', '附加配置'],
                ['text','dy_ad', '抖音广告'],
                ['text','jump_type', '任务类型'],

            ])

            ->setFormData($info)
            ->fetch();
    }
  
}