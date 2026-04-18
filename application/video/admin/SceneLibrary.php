<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SceneLibrary extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        $map[]=["creator_type","=",0];
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_scene_library_user')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_scene_library_user', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/SceneLibraryUserModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['name', '场景名称'],
                    // ['creator_type', 'creator_type'],
                    ['content','提示词'],
                    // ['style_name','风格名称'],
                    ['type_name','类型名称'],
                    // ['image_url_us_type','image_url_us_type'],
                    ['type', '平台类型','status','',[0=>'mj',1=>'sd']],
                    // ['label','搜索标签','text.edit'],
                    ['sort','排序','text.edit'],
                    ['image_url', '封面','img_url'],
                    // ['image_url_us', '硅谷封面','img_url'],
                    ['status', 'status','switch'],
                    ['comment','备注','text.edit'],
                    // ['time','time'],
                    ['right_button', '操作', 'btn']
                   
                       
            ])
                    
            ->setSearchArea([  
                ['text', 'name', '场景名字'],
                ['text', 'status', 'status'],
                ['text', 'image_url_us_type', 'image_url_us_type'],
                
                
                // ['text', 'style_name', '风格名称'],
                ['text', 'type_name', '类型名称'],
                ['select', 'type', '平台类型', '', '', [0=>'mj',1=>'sd']],
                
               
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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

            
            if ($data["type_id"] != '' ) {
                // code...
                $data_type_name= DB::connect('translate')->table('ts_padding_library_scene')->where('id',$data["type_id"])->find();
                $data["type_name"] = $data_type_name["name"];
  
            }


            
            $r = DB::connect('translate')->table('ts_scene_library_user')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }



        $type_id = array();
        $datas = DB::connect('translate')->table('ts_padding_library_scene')->where('status = 1 ')->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["name"]."-".$data["id"]);
                 
                $type_id = $type_id +$data;
            } 

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addRadio('type', '平台类型', '',['mj', 'sd'], 0)
            ->addFormItems([
                ['text', 'name', '场景名称'],
                // ['text', 'alias', '风格别名'],
                ['textarea', 'content', '提示词'],
                // ['ossimage', 'image_url', '国内图片链接'],
                ['text', 'image_url', '图片链接(从速推客户端上传后，复制链接进来)'],
                ['text', 'image_url_wi', '图片权重()'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                // ['select', 'style_id', '选择角色风格分类', '请选择角色风格分类', $style_id],  
                ['select', 'type_id', '选择场景类型分类', '请选择场景类型分类', $type_id],  
                ['text', 'seed', 'seed值'],
                ['text', 'comment', '备注信息'],

                ['text', 'lora_sd', 'sd的lora'],
                ['ossimage', 'image_url_cref_mj', 'mj角色参考链接'],
                ['text', 'image_url_cref_wi', 'mj角色权重()'],
                ['ossimage', 'image_url_sref_mj', 'mj画风参考链接'],
                ['text', 'image_url_sref_wi', 'mj画风权重()'],

                
            ])

            ->setTrigger('type', 1, 'lora_sd')
            ->setTrigger('type', 0, 'image_url_cref_mj,image_url_cref_wi,image_url_sref_mj,image_url_sref_wi')
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            
            if ($data["type_id"] != '' ) {
                // code...
                $data_type_name= DB::connect('translate')->table('ts_padding_library_scene')->where('id',$data["type_id"])->find();
                $data["type_name"] = $data_type_name["name"];
  
            }

            $r = DB::connect('translate')->table('ts_scene_library_user')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_scene_library_user')->where('id',$id)->find();



        $type_id = array();
        $datas = DB::connect('translate')->table('ts_padding_library_scene')->where('status = 1 ')->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["name"]."-".$data["id"]);
                 
                $type_id = $type_id +$data;
            } 


        return ZBuilder::make('form')
            //  ->addFormItems([
            //     ['text', 'name', '角色名称'],
            //     ['textarea', 'content', '标签内容'],
            //     ['text', 'label', '搜索标签(注意：多个标签用下划线分割)'],
            //     ['text', 'image_url', '封面'],
            //     ['text', 'comment', '备注信息'],
            // ])
          
            ->addRadio('type', '平台类型', '',['mj', 'sd'])
            ->addFormItems([
                ['text', 'name', '风格名称'],
                // ['text', 'alias', '风格别名'],
                ['textarea', 'content', '提示词'],
                // ['ossimage', 'image_url', '国内图片链接'],
                ['text', 'image_url', '图片链接(从速推客户端上传后，复制链接进来)'],
                ['text', 'image_url_wi', '图片权重()'],
                ['radio', 'status', '立即启用', '', ['否', '是']],
                // ['select', 'style_id', '选择角色风格分类', '请选择角色风格分类', $style_id],  
                ['select', 'type_id', '选择场景类型分类', '请选择场景类型分类', $type_id],  
                ['text', 'seed', 'seed值'],
                ['text', 'comment', '备注信息'],

                ['text', 'lora_sd', 'sd的lora'],
                ['ossimage', 'image_url_cref_mj', 'mj角色参考链接'],
                ['text', 'image_url_cref_wi', 'mj角色权重()'],
                ['ossimage', 'image_url_sref_mj', 'mj画风参考链接'],
                ['text', 'image_url_sref_wi', 'mj画风权重()'],

                
            ])

            ->setTrigger('type', '0', 'lora_sd')
            ->setTrigger('type', '1', 'image_url_cref_mj,image_url_cref_wi,image_url_sref_mj,image_url_sref_wi')
            ->setFormData($info)
            ->fetch();
    }



}

