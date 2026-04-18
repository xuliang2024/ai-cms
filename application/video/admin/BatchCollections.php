<?php
// 
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BatchCollections extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_batch_collections')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_batch_collections', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BatchCollectionsModel',2) // 设置数据表名
             ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['user_id', 'user_id'],
                    ['title', 'title'],
                    ['collection_id','collection_id'],
                   
                    ['c_type','c_type'],
                    ['status','status'],
                   
 
                    ['input','input'],
                    ['time','time'],
                    ['up_time','up_time'],
                    ['right_button', '操作', 'btn']
                   
                   
            ])  
            ->setSearchArea([ 
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['daterange', 'up_time', '更新时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['text', 'title', 'title'],
                ['text', 'user_id', 'user_id'],
                ['text', 'collection_id', 'collection_id'],

                ['text', 'c_type', 'c_type'],
                ['text', 'status', 'status'],
                
            ])

            ->addRightButton('info',[
                'title'=>'查看合集详情',
                'icon'  => 'fa fa-fw fa-search-plus',
                
                'href'=>'/admin.php/video/batch_collections_detail/index.html?_s=time=|title=|user_id=|collection_id=__collection_id__&_o=time=between%20time|title=eq|user_id=eq|collection_id=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ])

            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


}

