<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Bill extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('create_time desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_bill')->where($map)
        ->order($order)
        ->paginate();

        cookie('hm_bill', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/BillModel',2) // 设置数据表名
            // ->addOrder('consume,money,money_p1,money_p2') // 添加排序
            ->addColumns([ // 批量添加列
                    ['id', '用户ID'],
                    ['user_id', '用户ID'],
                    ['content', 'content'],
                    ['before','变动前'],
                    ['after','变动后'],
                    ['value','变动值'],
                    ['create_time','创建时间','datetime'],
                    ['pay_username','支付用户名字'],
                    ['pay_up_username','支付用户上级名字'],
                    ['pay_userid','支付ID'],
                    ['pay_up_userid','支付上级ID'],
                    ['out_trade_no','订单号'],
                    ['text', 'out_trade_no', '订单号'],

                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'create_time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户ID'],
                ['text', 'pay_username', '支付用户名字'],
                ['text', 'pay_up_username', '支付用户上级名字'],
                ['text', 'pay_userid', 'pay_userid'],
                ['text', 'pay_up_userid', 'pay_up_userid'],
                ['text', 'out_trade_no', '订单号'],
                
                
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     
}

