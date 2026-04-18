<?php
// 算力区域表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ComputeRegions extends Admin {
    
    public function index() 
    {
        

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_compute_regions')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_compute_regions', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ComputeRegionsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['region_sign', '区域名称'],
                
                // ['status', '状态','status','',[0=>'下架',2=>'上架']],
                ['status', '状态','text.edit'],
                ['sort', '排序','text.edit'],

                // ['source_text', 'source_text', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                ['title', '镜像标题'],
                ['sub_title', '镜像副标题'],
               
                ['time', '创建时间'],
                
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'region_sign', 'region_sign'],
                ['text', 'status', '状态'],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
