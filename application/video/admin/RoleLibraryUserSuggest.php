<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class RoleLibraryUserSuggest extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_role_library_user_suggest')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_role_library_user_suggest', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/RoleLibraryUserSuggestModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['name', '效果title'],
                    ['image_url', '效果图','img_url'],
                    ['sort','排序','text.edit'],
                    
                    ['status', 'status','switch'],
                    ['role_user_id', '关联角色ID'],
                    ['role_user_name', '关联角色名'],
                    ['role_user_image_url', '关联角色图','img_url'],
                    ['creator_type', 'creator_type'],
                    ['content','提示词'],
                    ['style_name','风格名称'],
                    ['type_name','类型名称'],
                    ['type', '平台类型','status','',[0=>'mj',1=>'sd']],
                    // ['label','搜索标签','text.edit'],
                    
                    ['comment','备注','text.edit'],
                    // ['time','time'],
                    ['right_button', '操作', 'btn']
                   
                       
            ])
                    
            ->setSearchArea([  
                ['text', 'name', '角色名字'],
                ['text', 'status', 'status'],
                
               
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
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

            if ($data["role_user_id"] != '' ) {
                // code...
                $data_type_name= DB::connect('translate')->table('ts_role_library')->where('id',$data["role_user_id"])->find();
                $data["role_user_name"] = $data_type_name["name"];
                $data["role_user_image_url"] = $data_type_name["image_url"];
                // $data["creator_type"] = $data_type_name["creator_type"];
                $data["content"] = $data_type_name["content"];
                $data["style_name"] = $data_type_name["style_name"];
                $data["style_id"] = $data_type_name["style_id"];
                $data["type_id"] = $data_type_name["type_id"];
                $data["type_name"] = $data_type_name["type_name"];
                $data["type"] = $data_type_name["type"];

                $data["alias"] = $data_type_name["alias"];
                $data["lora_sd"] = $data_type_name["lora_sd"];
                $data["seed"] = $data_type_name["seed"];
                $data["image_url_wi"] = $data_type_name["image_url_wi"];
                $data["image_url_cref_mj"] = $data_type_name["image_url_cref_mj"];
                $data["image_url_cref_wi"] = $data_type_name["image_url_cref_wi"];
                $data["image_url_sref_wi"] = $data_type_name["image_url_sref_wi"];
                $data["creator_type"] = $data_type_name["creator_type"];

            }else{
                $this->error('新增失败,请选择角色库模型');

            }


            
            $r = DB::connect('translate')->table('ts_role_library_user_suggest')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }



        
        $type_id = array();
        $datas = DB::connect('translate')->table('ts_role_library')->where('status',1)->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => "角色名字:".$data["name"]."-风格名称:".$data["style_name"]."-类型名称:".$data["type_name"]."-平台类型:".$data["type"]);
                 
                $type_id = $type_id +$data;
            } 

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '角色名称'],
                ['ossimage', 'image_url', '图片链接'],
                ['select', 'role_user_id', '选择角色库的角色模型', '请选择角色库的角色模型', $type_id], 
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                
                 

                ['text', 'comment', '备注信息'],                
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

            if ($data["role_user_id"] != '' ) {
                // code...
                $data_type_name= DB::connect('translate')->table('ts_role_library')->where('id',$data["role_user_id"])->find();
                $data["role_user_name"] = $data_type_name["name"];
                $data["role_user_image_url"] = $data_type_name["image_url"];
                // $data["creator_type"] = $data_type_name["creator_type"];
                $data["content"] = $data_type_name["content"];
                $data["style_name"] = $data_type_name["style_name"];
                $data["style_id"] = $data_type_name["style_id"];
                $data["type_id"] = $data_type_name["type_id"];
                $data["type_name"] = $data_type_name["type_name"];
                $data["type"] = $data_type_name["type"];

                $data["alias"] = $data_type_name["alias"];
                $data["lora_sd"] = $data_type_name["lora_sd"];
                $data["seed"] = $data_type_name["seed"];
                $data["image_url_wi"] = $data_type_name["image_url_wi"];
                $data["image_url_cref_mj"] = $data_type_name["image_url_cref_mj"];
                $data["image_url_cref_wi"] = $data_type_name["image_url_cref_wi"];
                $data["image_url_sref_wi"] = $data_type_name["image_url_sref_wi"];
                $data["creator_type"] = $data_type_name["creator_type"];
               
            }else{
                $this->error('新增失败,请选择角色库模型');

            }

            $r = DB::connect('translate')->table('ts_role_library_user_suggest')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_role_library_user_suggest')->where('id',$id)->find();

               $type_id = array();
        $datas = DB::connect('translate')->table('ts_role_library')->where('status',1)->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => "角色名字:".$data["name"]."-风格名称:".$data["style_name"]."-类型名称:".$data["type_name"]."-平台类型:".$data["type"]);
                 
                $type_id = $type_id +$data;
            } 

        return ZBuilder::make('form')
                ->addFormItems([
                ['text', 'name', '效果title'],
                ['ossimage', 'image_url', '效果图'],
                ['select', 'role_user_id', '选择角色库的角色模型', '请选择角色库的角色模型', $type_id], 
                // ['radio', 'status', '立即启用', '', ['否', '是']],
                
                ['text', 'comment', '备注信息'],                
            ])

            ->setFormData($info)
            ->fetch();
    }



}

