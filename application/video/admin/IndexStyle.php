<?php
// 小程序首页分类配置
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class IndexStyle extends Admin {
    

    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_index_style')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_index_style', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/PaintingInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['title','名称'],
                    ['icon', '图标','img_url'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],
                    ['comment', '备注'],
                    
                   
                    ['time','创建时间'],
                    ['right_button', '操作', 'btn']  
                    
            ])
            ->setSearchArea([  
                ['text', 'status', '上架状态'],
                
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->addRightButton('edit',[
                'title'=>'修改分类',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
            ->addRightButton('custom',[
                'title' => '新增模版',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-warning btn-rounded',
                'href'  => url('video/index_style/add_info',['id'=>'__id__'])
            ],false,['style'=>'primary','title' => true,'icon'=>false])
            ->addRightButton('custom',[
                'title'=>'查看模版',
                'icon'=>'fa fa-fw fa-bus',
                'class'=>'btn btn-info btn-rounded',
                'href'=>url('video/index_painting_info/index',['index_style_id'=>'__id__']),
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('right_button', 250)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_index_style')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('icon', '图标', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'title', '名字'],       
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

            $r = DB::connect('translate')->table('ts_index_style')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_index_style')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addOssImage('icon', '图标', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'title', '名字'],       
                ['text', 'comment', '备注'],      
               
            ])

        
            ->setFormData($info)
            ->fetch();
    }

    // 新增模版
     public function add_info($id = 0) 
     {

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



            $r = DB::connect('translate')->table('ts_index_painting_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
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



        $order_info = DB::connect('translate')->table('ts_index_style')->where('id',$id)->find();       
        // 显示添加页面
        return ZBuilder::make('form')
            ->addStatic('index_style_id', '分类ID','', $order_info["id"] ,$id)
            ->addStatic('index_style_name', '分类名称','', $order_info["title"] ,$order_info["title"])
            ->addOssImage('image_url', '模版封面', '', '', '', '', '', ['size' => '50,50'])
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
            ->fetch();
    }





}

