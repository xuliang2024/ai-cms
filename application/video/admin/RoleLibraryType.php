<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class RoleLibraryType extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_role_library_type')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_role_library_type', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/RoleLibraryTypeModel',2) // 设置数据表名
                        ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['name', '名称'],
                    
                    ['sort','排序','text.edit'],
                    ['status', 'status','switch'],
                    ['comment','备注','text.edit'],
                    // ['time','time'],
                    ['right_button', '操作', 'btn']
                   
                       
            ])
                    
            ->setSearchArea([  
                ['text', 'name', '名字'],
                ['text', 'status', 'status'],
                
                
               
            ])

            ->addTopButton('add')
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
            
            $r = DB::connect('translate')->table('ts_role_library_type')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
            
                ['text', 'name', 'name'],
               
                ['text', 'sort', '排序'],
                // ['radio', 'status', '',['0'=>'下架','1'=>'上架']],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
              
                ['text', 'comment', '备注信息'],
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

            $r = DB::connect('translate')->table('ts_role_library_type')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_role_library_type')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'name', 'name'],
               
                ['text', 'sort', '排序'],
                // ['radio', 'status', '',['0'=>'下架','1'=>'上架']],
              
                ['text', 'comment', '备注信息'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}

