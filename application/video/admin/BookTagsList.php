<?php
// 小说制作列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;




class BookTagsList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_book_tags_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_book_tags_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BookTagsListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['parent_id', '关联ID'],
                ['tag_type', '类型','status','',[0=>'合集',1=>'标签']],
                ['content', '标签'],
                ['name', 'name'],
                ['image_url', '参考图','img_url'],
                ['status', '状态','text.edit'],
                ['sort', '排序','text.edit'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
