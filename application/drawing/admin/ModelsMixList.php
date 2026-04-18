<?php
//混合模型列表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ModelsMixList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_models_mix_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_models_mix_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('models_mix_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['model_id','底膜id','text.edit'],
                ['lora_id', 'lora_id'],
                ['comment', '备注'],
                ['status', '状态','switch'],
              
               
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
            
            if ($data["model_id"]!='' &&$data["lora_id"]!=''   ) {
              $data_lora = DB::query('select  name from ai_lora_list where id = "'.$data["lora_id"].'";');
              $data_models = DB::query('select  name from ai_big_models_list where id = "'.$data["model_id"].'";');
              
              $lore_name='';
              $models_name='';
              
              foreach ($data_lora as $value) {
                  // code...
                 $lore_name=$value["name"];
              }

             foreach ($data_models as $value) {
                  // code...
                 $models_name=$value["name"];
              }

              $data["comment"] = $models_name."+".$lore_name;
            }else{
                $this->error('请选择底膜模型和lora模型');

            }



            $r = DB::table('ai_models_mix_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        
         $lora_id = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
            $datas = DB::query("select id , name from ai_lora_list ;");
            foreach ($datas as $data) {
                $data =array( $data["id"] => $data["id"]."-".$data["name"]);

                // array_merge($order_ids,$data);
                $lora_id = $lora_id +$data;
            }
            
        $model_id = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
            $datas = DB::query("select id , name from ai_big_models_list where status = 1 ;");
            foreach ($datas as $data) {
                $data =array( $data["id"] => $data["id"]."-".$data["name"]);

                // array_merge($order_ids,$data);
                $model_id = $model_id +$data;
            }
            

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'model_id', 'model_id', '请选择底膜模型', $model_id], 
                ['select', 'lora_id', 'lora_id', '请选择lora模型', $lora_id],  
                 ['select', 'status', '状态','',[0=>'已关闭',1=>'已开启'],1],         




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

             if ($data["model_id"]!='' &&$data["lora_id"]!=''   ) {
              $data_lora = DB::query('select  name from ai_lora_list where id = "'.$data["lora_id"].'";');
              $data_models = DB::query('select  name from ai_big_models_list where id = "'.$data["model_id"].'";');
              
              $lore_name='';
              $models_name='';
              
              foreach ($data_lora as $value) {
                  // code...
                 $lore_name=$value["name"];
              }

             foreach ($data_models as $value) {
                  // code...
                 $models_name=$value["name"];
              }

              $data["comment"] = $models_name."+".$lore_name;
            }else{
                $this->error('请选择底膜模型和lora模型');

            }


            $r = DB::table('ai_models_mix_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

         $lora_id = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
            $datas = DB::query("select id , name from ai_lora_list ;");
            foreach ($datas as $data) {
                $data =array( $data["id"] => $data["id"]."-".$data["name"]);

                // array_merge($order_ids,$data);
                $lora_id = $lora_id +$data;
            }

        $model_id = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
            $datas = DB::query("select id , name from ai_big_models_list ;");
            foreach ($datas as $data) {
                $data =array( $data["id"] => $data["id"]."-".$data["name"]);

                // array_merge($order_ids,$data);
                $model_id = $model_id +$data;
            }

        $info = DB::table('ai_models_mix_list')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
               ['select', 'model_id', 'model_id', '请选择底膜模型', $model_id], 
                ['select', 'lora_id', 'lora_id', '请选择lora模型', $lora_id],  
                 ['select', 'status', '状态','',[0=>'已关闭',1=>'已开启'],1],    
            ])
          
           

            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }





  
}