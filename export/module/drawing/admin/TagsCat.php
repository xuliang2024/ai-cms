<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TagsCat extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_tags_categorie')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_tags_categorie', $map);
        
        return ZBuilder::make('table')
            ->setTableName('tags_categorie') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name','名字'],
                ['status','状态','switch'],
                ['sort','排序','text.edit'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->hideCheckbox()
            ->setSearchArea([
                
                ['text', 'name', '名称', '', '', ''],
                ['select', 'status', '上线', '', '', ['0' => '默认', '1' => '上线']],
            
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButtons(['add','delete'])
            ->addRightButtons(['edit','custom'=>[
                'title' => '分类管理',
                'icon'  => 'fa fa-fw fa-navicon',
                'href'  => url('drawing/tags_content/index?_s=cat_id=__id__|zh_name=|en_name=|status=&_o=cat_id=eq|zh_name=eq|en_name=eq|status=eq', '')
            ]]) // 批量添加右侧按钮//,'delete'
            
            ->fetch(); // 渲染页面

    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_tags_categorie')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名字'],
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

            $r = DB::table('ai_tags_categorie')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_tags_categorie')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'name', '名字'],    
            ])
          
            ->setFormData($info)
            ->fetch();
    }



    
 

  
}