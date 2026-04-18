<?php
//文转视频任务记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Hybook extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_hybook_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_hybook_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('hybook_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['book_id','书本ID'],
                ['book_name','书名'],
                ['iconUrlLarge','图片','img_url'],
                ['words','字数'],
                ['sort','排序'],

                ['time', '创建时间'],
             
               
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->setSearchArea([
                
                ['text', 'book_id', '书本ID'],
                ['text', 'book_name', '书名'],
               
            ])
            ->addRightButton('info',[
                'title'=>'查看章节',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                'href'=>'/admin.php/drawing/hybook_info/index.html?_s=book_id=__book_id__|chapter_id=&_o=book_id=eq|chapter_id=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('right_button', 200)

            ->setHeight('auto') 
            ->addTopButton('add')
           
            ->fetch(); // 渲染页面

    }


  
}