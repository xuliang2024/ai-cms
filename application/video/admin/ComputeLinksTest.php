<?php
// sd算力云链接测试使用
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ComputeLinksTest extends Admin {
    
    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_compute_links_test')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_compute_links_test', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ComputeLinksTestModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['machine_id', '云机器ID'],
                    ['sd_url', 'sd算力云链接地址'],
                    ['status','status','switch'],
                    ['gpu_type','GPU类型'],
                    ['note', '备注信息'],
                    ['source','链接来源'],
                    ['billing_type','机器类型'],
                    ['total_consume','消耗脑值'],
                    ['created_at','机器创建时间'],
                    ['update_time','本地更新时间'],
                    ['source_int', '类型','status','',[0=>'手动添加',1=>'api开机']],
                    ['time','time'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'status', 'status'],
                ['text', 'machine_id', '云机器ID'],
                 
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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
            
            $r = DB::connect('translate')->table('ts_compute_links_test')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'sd_url', 'sd算力云链接地址'],
                ['text', 'gpu_type', 'gpu类型'],
                ['text', 'note', '备注信息'],
                ['text', 'source', '链接来源'],
                // ['switch', 'wx_mchid', 'wx_mchid'],

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

            $r = DB::connect('translate')->table('ts_compute_links_test')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_compute_links_test')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'sd_url', 'sd算力云链接地址'],
                ['text', 'gpu_type', 'gpu类型'],
                ['text', 'note', '备注信息'],
                ['text', 'source', '链接来源'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}

