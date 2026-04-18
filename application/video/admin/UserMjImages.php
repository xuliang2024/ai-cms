<?php
// mj转移后的矢量表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserMjImages extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_mj_images')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_mj_images', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/UserMjImagesModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],

                ['prompt', 'prompt', 'callback', function ($val, $data) {
                    $val = sprintf(self::$ellipsisElement, $val, $val);
                    return $val;
                    }, '__data__'],
        
                ['image_url', 'image_url','img_url'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
             
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
