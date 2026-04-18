<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MonthStatisticalReward extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('year_month_id desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_month_statistical_reward')->where($map)
        ->order($order)
        ->paginate();

        cookie('hm_month_statistical_reward', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/MonthStatisticalRewardModel',2) // 设置数据表名
             ->addOrder('pay_money') // 添加排序
            // ->addOrder('consume,money,money_p1,money_p2') // 添加排序
            ->addColumns([ // 批量添加列
                    // ['id', '用户ID'],
                    ['user_id', '用户ID'],
                    ['username', '用户名'],
                    ['nickname','昵称'],
                    ['year_month_id','日期'],

                    // ['extend_dict_id','关联分成ID'],
                    

                    
                    // ['pay_order_cnt','支付订单数'],
                    // ['pay_user_cnt','支付人数'],
                    ['amount_bonus_month','梯度奖励'],
                    ['gradient_scale','梯度比例'],
                    ['sales_amount','梯度销售额'],
                    ['pay_money','销售额'],

                    // ['user_balance_all_month','月所有收益'],
                    // ['user_balance_month','月分销收益'],
                    // ['moeny_rebeat','分销比例'],
                    // ['user_extend_type','是否参与梯度奖励'],
                    // ['p1','所属p1'],
                    // ['p2','所属p2'],
                    ['create_time','创建时间','datetime'],

                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                // ['daterange', 'year_month_id', '时间', '', '', ['format' => 'YYYY-MM']],
                ['text', 'year_month_id', '日期'],
                ['text', 'user_id', '用户ID'],
                ['text', 'username', '用户名字'],
                ['text', 'nickname', '昵称'],
                ['text', 'sales_amount', '关联分成目标金额'],
                ['text', 'amount_bonus_month', '关联奖励金额'],
                ['text', 'gradient_scale', '关联奖励比例'],

                ['text', 'user_extend_type', '是否参与梯度奖励'],
                ['text', 'p1', '所属p1'],
                ['text', 'p2', '所属p2'],
                
                
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     
}

