<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\store\model\UserModel ;


class GoodsOrder extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('create_time desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_goods_order')
        ->where($map)
        ->order($order)
        // ->paginate();
        ->paginate()->each(function($item, $key){
                            
                $userData = DB::connect('faka_fyshark_com')->table('hm_user')->where('id', $item["user_id"])->find();
                if($userData) {
                    $item["username"] = $userData['username'];
                    $item["p1"] = $userData['p1'];
                }

            
            
            return $item;
        });
        cookie('hm_goods_order', $map);
        




        return ZBuilder::make('table')
            ->setTableName('store/GoodsOrderModel',2) // 设置数据表名
            // ->addOrder('consume,money,money_p1,money_p2') // 添加排序
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['username', '用户名字'],
                    ['p1', 'p1'],
                    ['out_trade_no', 'out_trade_no'],
                    ['goods_name','goods_name'],
                    ['money','money'],
                    ['gpu_status','gpu_status'],
                    ['shell_divide_type','是否实时分成'],
                    ['shell_award_type','是否月度结算'],
                    
                    ['create_time','创建时间','datetime'],
                    ['pay_time','支付时间','datetime'],
                   

                  
            ])
           
            ->setSearchArea([  
                ['daterange', 'create_time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户ID'],
                ['text', 'gpu_status', 'gpu_status'],
                ['text', 'out_trade_no', 'out_trade_no'],
                ['text', 'shell_divide_type', 'shell_divide_type'],
                // ['text', 'p1', 'p1'],

                
                
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     
}

