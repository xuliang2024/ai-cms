<?php
//古诗词列表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class PoetryList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_poetry_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_poetry_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('poetry_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['prompt','正面词'],
                ['negative_prompt', '负面词'],
                ['status', '状态','switch'],
                ['comment', '备注'],
            
                ['time', '创建时间'],
                
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->addTopButton('add',['title'=>'新增']) // 批量添加顶部按钮
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
             ->addRightButton('edit',[
                'title'=>'修改',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


 public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_poetry_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        
        // 显示添加页面
        return ZBuilder::make('form')
              
            ->addFormItems([
                ['textarea', 'prompt','正面词'],
                ['textarea', 'negative_prompt', '负面词'],                            
                ['select', 'switch', '状态','',[0=>'关闭',1=>'开启'],1],
                ['text', 'comment', '备注'], 

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

            $r = DB::table('ai_poetry_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_poetry_list')->where('id',$id)->find();

        return ZBuilder::make('form')
            
             ->addFormItems([
                 ['textarea', 'prompt','正面词'],
                ['textarea', 'negative_prompt', '负面词'],                
                ['select', 'switch', '状态','',[0=>'关闭',1=>'开启'],1],   
                ['text', 'comment', '备注'],            
            ])
          
           

            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }

  
}