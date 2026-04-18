<?php
// 管理人员名单
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserManagementList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_management_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_management_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/UserManagementListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['name', '名字'],
                    ['phone', '手机号'],
                    ['department','所属部门'],
                    ['department', '所属部门','status','',[0=>'未分类',1=>'研发',2=>'产品',3=>'运营',4=>'市场',5=>'其他']],
                    ['status','状态','switch'],
                    ['comment','备注'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'name', '名字'],
                ['text', 'phone', '手机号'],
                ['text', 'department', '所属部门'],
                ['text', 'status', '状态'],
                ['select', 'department', '所属部门','', '',[0=>'未分类',1=>'研发',2=>'产品',3=>'运营',4=>'市场',5=>'其他']],
                // ['select', 'vip_level', '会员级别', '', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'超级会员']],
                
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
            
            $r = DB::connect('translate')->table('ts_user_management_list')->insert($data);
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
                ['text', 'phone', '手机号'],
                ['select', 'department', '所属部门','',[0=>'未分类',1=>'研发',2=>'产品',3=>'运营',4=>'市场',5=>'其他'],0],
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

            $r = DB::connect('translate')->table('ts_user_management_list')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_user_management_list')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名字'],
                ['text', 'phone', '手机号'],
                ['select', 'department', '所属部门','',[0=>'未分类',1=>'研发',2=>'产品',3=>'运营',4=>'市场'],0],
                ['text', 'comment', '备注'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}

