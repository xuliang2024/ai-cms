<?php
//底模型列表
namespace app\drawing\admin;
    
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
    
class BigModelsList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_big_models_list')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ai_big_models_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('big_models_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['model_name','文件名字'],
                ['sd_vae','优化VAE'],
                ['name', '名字'],
                ['img', '封面','img_url'],
                ['status', '状态','switch'],
                ['sort','排序','text.edit'],
                ['coin','金币','text.edit'],
                ['content', '内容'],
                ['trigger_word', '触发词'],
                ['prompt', '默认正向'],
                ['diss_word', '过滤词'],
                ['negative_prompt', '默认反向'],
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
            ->setColumnWidth('prompt,negative_prompt,trigger_word,content', 300)
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
            
            $r = DB::table('ai_big_models_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                ['text', 'model_name', '文件名字'],
                ['text','sd_vae','优化VAE'],
                ['text', 'name', '备注名字'],
               
                ['select', 'status', '状态','',[0=>'已关闭',1=>'已开启'],1],               

                ['textarea', 'trigger_word', '触发词'],     
                ['textarea', 'prompt', '默认正向'],     
                ['textarea', 'diss_word', '过滤词用英文逗号分割'],
                ['textarea', 'negative_prompt', '默认反向'],     

                ['textarea', 'content', '内容'],     




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

            $r = DB::table('ai_big_models_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_big_models_list')->where('id',$id)->find();

        return ZBuilder::make('form')
              ->addOssImage('img', '图片', '', '', '', '', '', ['size' => '50,50'])
             ->addFormItems([
               ['text', 'model_name', '文件名字'],
               ['text','sd_vae','优化VAE'],
                ['text', 'name', '备注名字'],
                ['select', 'status', '状态','',[0=>'已关闭',1=>'已开启'],1],               

                ['textarea', 'trigger_word', '触发词'],     
                ['textarea', 'prompt', '默认正向'],     
                ['textarea', 'diss_word', '过滤词用英文逗号分割'],
                ['textarea', 'negative_prompt', '默认反向'],     

                ['textarea', 'content', '备注'],     

            ])
          
           

            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }



  
}