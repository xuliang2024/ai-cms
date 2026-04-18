<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class FantuiApi extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();

    
        
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_compute_fantui_api')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_compute_fantui_api', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/FantuiApiModel',2) // 设置数据表名
             ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['sd_url','地址','text.edit'],
                    ['status', '状态','switch'],
                    ['comment','备注','text.edit'],
                    ['time','创建时间'],
                    
                   
                   
            ])  
            ->setSearchArea([ 
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']], 
        
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add',['title'=>'新增']) // 批量添加顶部按钮
            ->setColumnWidth([
                'word' => 300 // 将'word'列的宽度设置为200px
            ])
             

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

            
                $r = DB::connect('translate')->table('ts_compute_fantui_api')->insert($data);
                if ($r) {
                    $this->success('新增成功', 'index');
                } else {
                    $this->error('新增失败');
                }

            }


        

                   
        // 显示添加页面
        return ZBuilder::make('form')
            
            ->addFormItems([

                ['text', 'sd_url', '地址'],
                
                ['textarea', 'comment', '备注'],
                
            ])
            ->fetch();
    }


     



}

