<?php
//用户信息
namespace app\ai\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class CatList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_cat_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_cat_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('cat_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name', 'name'],
                    ['role_msg', '角色'],
                    ['status', '状态','switch'],
                    ['sort', '排序','text.edit'],
                    ['time', '时间'],
                    ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add',['title'=>'新增分类'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $r = DB::table('ai_cat_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        
        // 显示添加页面
        return ZBuilder::make('form')
            
            ->addFormItems([
                    ['text','name', '分类名字'],                    
                    ['textarea','role_msg', '角色文字'],

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

            $r = DB::table('ai_cat_list')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_cat_list')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','name', '分类名字'],                    
                ['textarea','role_msg', '角色文字'],
            ])
            ->setFormData($info)
            ->fetch();
    }




  
}