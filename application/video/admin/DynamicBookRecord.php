<?php
// 动态机器记录表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DynamicBookRecord extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_dynamic_book_record')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_dynamic_book_record', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/DynamicBookRecordModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'Id'],
                    ['record_type', '类型','status','',[0=>'脑端绘画',1=>'弹性视频合成']],
                    ['draw_change_status', '画图机器状态','status','',[0=>'不变',1=>'新增',2=>'减少',-1=>'爆满异常']],
                    ['get_book_unfinished_cnt','未完书籍数'],
                    ['get_draw_cnt','未完绘制数'],
                    
                    ['change_draw_apparatus_cnt', '变更绘画机器数'],
                    // ['wx_pay_key', 'wx_pay_key'],
                    
                    ['draw_apparatus_before_cnt', '变前绘画机器数'],
                    ['draw_apparatus_after_cnt', '变后绘画机器数'],
                    ['get_video_cnt','未完视频合成数'],
                    ['set_video_apparatus_cnt','设置期望弹性数'],
                    ['time','time'],
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'appid', 'appid'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['select', 'record_type', '类型', '', '', ['0'=>'绘画','1'=>'视频合成']],
                ['select', 'draw_change_status', '画图机器状态', '', '', ['0'=>'不变','1'=>'新增','2'=>'减少','-1'=>'爆满异常']],
                
                
               
            ])

            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }



}
