<?php
// 分成明细表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class EarningsList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_earnings_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_earnings_list', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/EarningsListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                
                ['id', 'ID'],
                    ['user_id','收益用户ID'],
                    ['pay_user_id','充值用户ID'],
                    ['pay_user_name','充值用户名字'],
                    ['pay_id','充值订单ID'],
                    ['money', '金额'],
                    ['money_type', '分成类型','status','',[0=>'用户充值']],
                    ['earnings_type', '进出类型','status','',[0=>'收益',1=>'提成']],
                    ['time', '时间'],
                    
                
            ])
             ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '收益用户'],
                ['text', 'pay_user_id', '充值用户'],
                ['text', 'pay_id', '充值订单'],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
