<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TagsContent extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_tags_content')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_tags_content', $map);
        
        return ZBuilder::make('table')
            ->setTableName('tags_content') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['cat_id', '分类ID'],
                ['zh_name','中文'],
                ['en_name','英文'],
                ['status','状态','switch'],
                ['sort','排序','text.edit'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->hideCheckbox()
            ->setSearchArea([  
                ['text', 'cat_id', '分类id', '', '', ''],
                ['text', 'zh_name', '中文', '', '', ''],
                ['text', 'en_name', '英文', '', '', ''],
                ['select', 'status', '上线', '', '', ['0' => '默认', '1' => '上线']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButtons(['add','delete'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_tags_content')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'cat_id', '分类ID'],
                ['text', 'zh_name', '中文'],
                ['text', 'en_name', '英文'],
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

            $r = DB::table('ai_tags_content')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_tags_content')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'cat_id', '分类ID'],
                ['text', 'zh_name', '中文'],
                ['text', 'en_name', '英文'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



    
 

  
}