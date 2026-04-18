<?php
// 应用列表
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserExtendDictList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_user_extend_dict_list')->where($map)
        ->order('sort ace')
        ->paginate();

        cookie('hm_user_extend_dict_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/UserExtendDictListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['sales_amount', '销售金额'],
                    ['gradient_scale', '奖励比例%','text.edit'],
                    ['amount_bonus_month','奖励金额','text.edit'],
                    ['sort','排序','text.edit'],
                    ['status','status','switch'],
                    // ['comment','备注'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
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
            
            $r = DB::connect('faka_fyshark_com')->table('hm_user_extend_dict_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'sales_amount', '销售金额'],
                ['text', 'gradient_scale', '分销比例%'],
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

            $r = DB::connect('faka_fyshark_com')->table('hm_user_extend_dict_list')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('faka_fyshark_com')->table('hm_user_extend_dict_list')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'sales_amount', '销售金额'],
                ['text', 'gradient_scale', '分销比例%'],
                ['text', 'comment', '备注'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}

