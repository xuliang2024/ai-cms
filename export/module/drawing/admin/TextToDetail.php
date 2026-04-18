<?php
//文转视频任务记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TextToDetail extends Admin {
	
    public function index($text_video_id=null) 
    {
        $map = $this->getMap();

        $map[]=["text_video_id","=", $text_video_id];  
        $data_list = DB::table('ai_text_detail_list')->where($map)
        ->order('time desc')
        ->paginate();


        cookie('ai_text_detail_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('text_detail_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['text_video_id','书本ID'],
                ['text_word','拆分内容'],
                ['text_tags','场景描述词'],
                ['mp3_url', 'mp3','image_video'],
                ['mp4_url', 'mp4视频','image_video'],
                ['right_button', '操作', 'btn'],
                // ['draw_progress', '绘画率'],
                ['status', 'tag识别','status','',[0=>'等待中',1=>'制作中',2=>'完成']],
                ['status', 'tag识别','text.edit'],

                ['mp3_status', 'mp3状态','status','',[0=>'等待中',1=>'制作中',2=>'完成']],
                ['mp3_status', 'mp3状态','text.edit'],

                ['draw_status', '作画状态','status','',[0=>'等待中',1=>'制作中',2=>'完成']],
                ['draw_status', '作画状态','text.edit'],

                ['mp4_status', 'mp4_status','status','',[0=>'等待中',1=>'制作中',2=>'完成']],
                ['mp4_status', 'mp4状态','text.edit'],

                ['comment', '备注'],
                ['time', '创建时间'],
               
                
            ])
            // ->hideCheckbox()

            // ->setSearchArea([
                
            //     ['text', 'text_video_id', '书本ID'],
               
            // ])

            ->addRightButton('info',[
                'title'=>'查看绘制',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                'href'=>'/admin.php/drawing/draw_task_list/index.html?_s=user_id=|model_id=|style_id=|book_cid=__id__|time=|id=|status=|gc_type=&_o=user_id=eq|model_id=eq|style_id=eq|book_cid=eq|time=between%20time|id=eq|status=eq|gc_type=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            ->addRightButton('edit',[
                'title'=>'编辑文本',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('text_word,text_tags', 400)
            ->setColumnWidth('right_button', 200)

            ->setHeight('auto') 
            ->addTopButton('delete')
           
            ->fetch(); // 渲染页面

    }



     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_text_detail_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_text_detail_list')->where('id',$id)->find();

        return ZBuilder::make('form')
           
             ->addFormItems([
                 ['textarea', 'text_word', '文本内容'],
                ['textarea', 'text_tags', '提词内容'],

                ['select', 'draw_status', '作画状态','',['0' => '等待中', '2' => '完成','1' => '制作中']],  

                ['text', 'comment', '备注'],

            ])
          
           
            ->setFormData($info)
            ->fetch();
    }


  
}