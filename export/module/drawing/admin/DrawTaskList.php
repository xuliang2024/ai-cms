<?php
//绘画任务记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DrawTaskList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_draw_task_list')->where($map)
        ->order('time desc')
        ->paginate()->each(function($item, $key){
            
            $item["draw_img"] = "";
            if ( $item["status"] == 2){
                
                $imgData = DB::table('ai_draw_img_list')->where('task_id', $item["id"])->find();
                if($imgData) {
                    $item["draw_img"] = $imgData['img'];
                }

            }
            
            return $item;
        });

        cookie('ai_draw_task_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('draw_task_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id','用户id'],
                ['draw_img', '绘画图','img_url'],
                ['pid','批次id'],
                ['book_cid','book_cid'],
                // ['status', '状态',['0' => '等待', '1' => '绘画','2'=>'完成']],
                ['gc_type', '绘画类型',['0' => '正常', '1' => '人物动漫化','2'=>'诗文成画']],
                // ['model_name', '文件名字'],
                ['status', '状态','text.edit'],//0已经创建，1绘画中，2绘画完成
                ['time', '创建时间'],
                ['model_id', 'model_id'],
                ['style_id', 'style_id'],
                ['img_url', '参考图','img_url'],
                ['host', '机器'],
                ['prompt', '正向词'],
                ['img_type', '是否高清',['0' => '普通', '1' => '高清']],
                ['img_size', '图片比例1'],
                ['cnt', '图片数量'],
                // ['negative_prompt', '负面词'],
                // ['en_prompt', '正向词'],
                

                
                
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->setSearchArea([
                
                ['text', 'user_id', '用户ID'],
                ['text', 'model_id', '模型ID'],
                ['text', 'style_id', 'style_ID'],
                ['text', 'book_cid', 'book_cid'],
                ['daterange', 'time', '时间'],
                ['text', 'id', '任务ID'],   
                ['text', 'status', '状态'],   
                ['select', 'gc_type', '绘画类型', '', '', ['0' => '正常', '1' => '人物动漫化','2'=>'诗文成画']],
            ])

// http://ai-cms.fyshark.com/admin.php/drawing/draw_img_list/index.html?_s=user_id=|task_id=__id__|hot=&_o=user_id=eq|task_id=eq|hot=eq


            ->addRightButton('info',[
                'title'=>'查看作品',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                'href'=>'/admin.php/drawing/draw_img_list/index.html?_s=user_id=|task_id=__id__|hot=&_o=user_id=eq|task_id=eq|hot=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->setColumnWidth('prompt,host', 400)
            ->addTopButton('back')
            ->addTopButton('delete')
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }



  
}