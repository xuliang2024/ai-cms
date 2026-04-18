<?php
// 分销推广日报
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserEarningsDate extends Admin {
    
    public function index() 
    {
     


        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_earnings_date')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_user_earnings_date', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/UserEarningsDateModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['dayid', '日期'],
                ['user_id', '用户ID'],
                ['invite_user', '邀请人数'],
                ['pay_cnt', '消费笔数'],
                ['pay_money', '消费金额'],
                ['translate_get', '获取佣金'],
                
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                // ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
