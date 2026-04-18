<?php
//文转视频任务记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class HybookInfo extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_book_chapter')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_book_chapter', $map);
        
        return ZBuilder::make('table')
            ->setTableName('book_chapter') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['book_id','书本ID'],
                ['chapter_id','章节ID'],
                ['time', '创建时间'],
             
               
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->setSearchArea([
                
                ['text', 'book_id', '书本ID'],
                ['text', 'chapter_id', '章节ID'],
               
            ])
            ->addRightButton('edit',[
                'title'=>'查看内容',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('right_button', 200)

            ->setHeight('auto') 
            ->addTopButton('add')
           
            ->fetch(); // 渲染页面

    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_book_chapter')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_book_chapter')->where('id',$id)->find();

        return ZBuilder::make('form')
           
             ->addFormItems([
                
                ['ckeditor', 'content', '章节内容'],
                

            ])
          
           
            ->setFormData($info)
            ->fetch();
    }



  
}