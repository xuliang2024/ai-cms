<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BtIpsLog extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_bt_ips_log')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_bt_ips_log', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BtIpsLogModel',2) // 设置数据表名
             ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['ip', 'ip'],
                
                   
                    
                    ['res_log', 'res_log', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 200, '...');
                    }],

                    ['run_log', 'run_log', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 200, '...');
                    }],

                    ['time','创建时间'],
                    ['right_button', '操作', 'btn']
                   
                   
                   
            ])  
             ->addRightButton('edit',[
                'title'=>'查看详情',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
            ->setSearchArea([ 
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['text', 'ip', 'ip'],
                ['text', 'res_log', 'res_log'],
                ['text', 'run_log', 'run_log'],
                
        
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('res_log', 250)
            ->setColumnWidth('run_log', 250)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

      

        $info = DB::connect('translate')->table('ts_bt_ips_log')->where('id',$id)->find();

        return ZBuilder::make('form')    
                ->addFormItems([
                
                ['text', 'id', 'id'],       
                ['text', 'ip', 'ip'],      
                ['textarea', 'res_log', 'res_log'],      
                ['textarea', 'run_log', 'run_log'],      
                ['text', 'time', 'time'],      
               
            ])

        
            ->setFormData($info)
            ->hideBtn('submit')

            ->fetch();
    }

}

