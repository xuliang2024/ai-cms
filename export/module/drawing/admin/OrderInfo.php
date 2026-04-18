<?php
//充值订单
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class OrderInfo extends Admin {
	
    public function index() 
    {
            
        $map = $this->getMap();

        
        $search_area = [
            ['text', 'user_id', '用户id'],
            ['daterange', 'time', '时间'],   
            ['select', 'status', '支付状态', '', '', ['0' => '创建订单', '1' => '支付成功']],
            
            ['text', 'out_trade_no', '订单号'],
            
            ['text', 'appid', '应用'],
             ['text', 'channel_name', '渠道商'],
            // ['text', 'channel_name', '渠道商'],
            ['text', 'source_name', '推广标识'],
           
        ];
                
        $map = $this->getMap();
        $data_list = DB::table('ai_order_info')->where($map)
        ->order('time desc')
        ->paginate(); 

        cookie('ai_order_info', $map);
        
        return ZBuilder::make('table')
            
            ->setTableName('order_info') // 设置数据表名    
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['time', '创建时间'],
                    ['user_id','用户id'],
                    ['out_trade_no', '订单号'],
                    ['money', '支付金额(分)'],
                    ['coin', '金币'],
                    ['title', '标题'],
                    ['channel_name','渠道'],
                    ['source_name','推广标识'],
                    ['status', '状态','status','',[0=>'创建订单',1=>'支付成功',2=>'支付失败']],
                    ['cid_msg','归因消息'],
                    ['appid','应用'],
                    ['status', '状态','vip_level','',[0=>'默认',1=>'普通会员',2=>'高级会员',3=>'尊贵会员']],
                    
            ])
            
              
            ->setSearchArea($search_area)
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }

    

  
}