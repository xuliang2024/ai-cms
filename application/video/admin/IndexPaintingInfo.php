<?php
// 首页分类下的风格模版
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class IndexPaintingInfo extends Admin {

    public function index($index_style_id = 0) 
    {
     
        $map = $this->getMap();
        if($index_style_id >  0 ){
            // $map["video_id"] = $video_id;
            $map[]=["index_style_id","=", $index_style_id];
        }else{
            $this->success('index_style_id错误', url('video/ts_index_style/index'));
        }
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_index_painting_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_index_painting_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/IndexPaintingInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name','模版名称'],
                    ['index_style_name','分类名称'],
                    ['index_style_id','分类ID'],
                    // ['book_id','作品ID'],
                    // ['book_name','作品名字'],
                    ['style_id','风格ID'],
                    ['style_name','风格名字'],
                    ['bgm_id','背景音ID'],
                    ['bgm_title','背景音名字'],
                    ['local_name','配音中文名'],
                    ['short_name','配音简称名'],
                    ['image_url', '封面','img_url'],
                    ['video_url', '视频','image_video'],
                    ['is_index', '是否首页','switch'],
                    ['is_example', '是否示例','switch'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],
                    ['comment', '备注'],
                    
                   
                    ['time','创建时间'],
                    ['right_button', '操作', 'btn']  
                    
            ])
            ->setSearchArea([  
                ['text', 'status', '上架状态'],
                
            ])
            // ->addTopButton('add',['title'=>'新增模版'])
            ->addTopButton('back') // 批量添加顶部按钮
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    //  public function add() 
    //  {

    //     // 保存数据
    //     if ($this->request->isPost()) {
    //         // 表单数据
    //         $data = $this->request->post();

    //         if ($data["style_id"] != '' ) {
    //             // code...
    //             $data_style_name= DB::connect('translate')->table('ts_index_painting_info')->where('id',$data["style_id"])->find();
    //             $data["style_name"] = $data_style_name["name"];
    //         // $data_pay_name = DB::query('select  name  from we_cat_pay_info where id = "'.$data["pay_id"].'"  ;');
    //         //  foreach ($data_pay_name as $value){   
    //         //     $data["pay_name"] = $value["name"];
    //         //  } 


    //         }
    //         $r = DB::connect('translate')->table('ts_index_painting_info')->insert($data);
    //         if ($r) {
    //             $this->success('新增成功', 'index');
    //         } else {
    //             $this->error('新增失败');
    //         }
    //     }

    //          $style_id = array();
       
    //         // $datas = DB::table('backmarket_order_line_info_tab')->select();
    //     // $datas = DB::query("select id ,name  from ts_style_painting_settings where status = 1 order by sort desc ;");
    //     $datas = DB::connect('translate')->table('ts_index_painting_info')->where('status',1)->order('sort desc')->select();
    //     foreach ($datas as $data) {
                
               
    //             $data =array( $data["id"] => $data["name"]."-".$data["id"]);
                 
    //             $style_id = $style_id +$data;
    //         }    
    //     // 显示添加页面
    //     return ZBuilder::make('form')
    //         ->addOssImage('image_url', '封面', '', '', '', '', '', ['size' => '50,50'])
    //         ->addOssVideo('original_url','模版视频','')
    //             ->addFormItems([
    //             // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
    //             ['text', 'name', '模版名字'],     
    //             ['select', 'style_id', '选择绘画风格分类', '请选择绘画风格分类', $style_id],  
    //             ['text', 'comment', '备注'],      
               
    //         ])
    //         ->fetch();
    // }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if ($data["style_id"] != '' ) {
                // code...
                $data_style_name= DB::connect('translate')->table('ts_style_painting_settings')->where('id',$data["style_id"])->find();
                $data["style_name"] = $data_style_name["name"];
  
            }

            if ($data["bgm_id"] != '' ) {
                // code...
                $data_style_name= DB::connect('translate')->table('ts_audio_bgm_list')->where('id',$data["bgm_id"])->find();
                $data["bgm_title"] = $data_style_name["title"];
  
            }
            



            $r = DB::connect('translate')->table('ts_index_painting_info')->where('id',$id)->update($data);
            if ($r) {
                // $this->success('编辑成功', 'index');
                $this->success('新增成功', url('video/index_painting_info/index',['index_style_id'=>$data['index_style_id']]));
            } else {
                $this->error('编辑失败');
            }
        }

          $style_id = array();
       
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        // $datas = DB::query("select id ,name  from ts_style_painting_settings where status = 1 order by sort desc ;");
        $datas = DB::connect('translate')->table('ts_style_painting_settings')->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["name"]."-".$data["id"]);
                 
                $style_id = $style_id +$data;
            } 


            $bgm_id = array();
       
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        // $datas = DB::query("select id ,name  from ts_style_painting_settings where status = 1 order by sort desc ;");

        $datas = DB::connect('translate')->table('ts_audio_bgm_list')->order('sort desc')->select();
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["title"]."-".$data["id"]);
                 
                $bgm_id = $bgm_id +$data;
            } 



        $info = DB::connect('translate')->table('ts_index_painting_info')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addStatic('index_style_id', '上级分类模版','分类名称：'.$info["index_style_name"], $info["index_style_name"] ,$info["index_style_id"])
            ->addOssImage('image_url', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addOssVideo('video_url','模版视频','')
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'name', '模版名字'],
                ['select', 'style_id', '选择绘画风格分类', '请选择绘画风格分类', $style_id],  
                ['select', 'bgm_id', '选择bgm背景音乐分类', '请选择bgm背景音乐分类', $bgm_id],  
                ['text', 'local_name', '配音中文名(local_name)'],      
                ['text', 'short_name', '配音简称名(short_name)'],            
                ['text', 'comment', '备注'],      
               
            ])

        
            ->setFormData($info)
            ->fetch();
    }



}

