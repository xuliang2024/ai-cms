<?php
// 分成明细表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class DraftRecord extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_draft_record')
        ->alias('a')
        ->Join('ts_users u','a.user_id=u.id')
         ->field('a.*,u.from_user_id as from_user_id_u')

        ->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_draft_record', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/DraftRecordModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                
                ['id', 'ID'],
                    ['user_id','用户ID'],
                    ['from_user_id_u','上级用户'],
                    ['draft_id','草稿ID'],
                    ['status', '状态'],
                    ['cnt', '下载次数'],
                    ['last_down_time', '时间'],
                    ['time', '时间'],
                    
                
            ])
             ->setSearchArea([  
                ['text', 'user_id', '用户'],
                ['text', 'u.from_user_id', '上级用户'],
                ['text', 'draft_id', '草稿ID'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
