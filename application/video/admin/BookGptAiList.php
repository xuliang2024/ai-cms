<?php
// 小说制作gpt助手
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;



class BookGptAiList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_book_gpt_ai_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_book_gpt_ai_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BookGptAiListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['prompt', 'prompt预设', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                // ['prompt', 'system预设'],
                ['name', 'name'],
                ['sub_name', 'sub_name'],
                // ['message', 'message'],
                ['message', 'message', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                ['model', 'model'],
                ['status', '状态','text.edit'],
                ['sort', '排序','text.edit'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('add',['title'=>'新增']) // 批量添加顶部按钮
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
            
            $r = DB::connect('translate')->table('ts_book_gpt_ai_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'name', '名字'],       
                ['textarea', 'prompt', 'prompt预设词'],       
                ['text', 'sub_name', '子名字'],       
                ['textarea', 'message', 'message'],      
                ['text', 'model', 'model'],      
               
               
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

            $r = DB::connect('translate')->table('ts_book_gpt_ai_list')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_book_gpt_ai_list')->where('id',$id)->find();

        return ZBuilder::make('form')

           
                ->addFormItems([
                ['text', 'name', '名字'],       
                ['textarea', 'prompt', 'prompt预设词'],       
                ['text', 'sub_name', '子名字'],       
                ['textarea', 'message', 'message'],      
                ['text', 'model', 'model'],      
               
            ])
        
            ->setFormData($info)
            ->fetch();
    }

}
