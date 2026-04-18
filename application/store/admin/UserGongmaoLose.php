<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserGongmaoLose extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_user_gongmao_lose')->where($map)
        ->order($order)
        ->paginate();

        cookie('hm_user_gongmao_lose', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/UserGongmaoLoseModel',2) // 设置数据表名
            ->addOrder('consume,money,money_p1,money_p2,moeny_rebeat,moeny_rebeat_p2,user_current_amount') // 添加排序
            ->addColumns([ // 批量添加列
                    ['id', '用户ID'],
                    ['mobile', '手机号'],
                      ['gongmao_status', '工猫签约状态','status','',[0=>'未签约',1=>'待签约',2=>'签约文件生成中',3=>'签约完成',4=>'签约失败，绑定手机号和签约时的手机号不一致']],
                    ['user_id', '用户ID'],
                    



                    ['user_status','user_status'],
                    
                    ['mobile_gongmao','mobile_gongmao'],

                    ['name_gongmao','name_gongmao'],
                    ['identity_gongmao','identity_gongmao'],
                    ['bank_account_no_gongmao','bank_account_no_gongmao'],
                    ['alipay_account_no_gongmao','alipay_account_no_gongmao'],
                    ['time','创建时间','datetime'],
                   
                    ['timestamp','timestamp'],
                    ['contract_id','contract_id'],

                                      ['gongmao_mobile_status', '手机是否一致','status','',[0=>'未绑定',1=>'已绑定',2=>'已绑定但不一致',3=>'绑定成功且签约完成']],
                    
                    ['app_key', 'app_key'],
                    ['service_id','service_id'],

                    
                    ['contract_file_url_gongmao', '签约合同地址', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 100, '...');
                    }],

                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', '用户ID'],
                ['text', 'name_gongmao', '签约名'],
                
                ['text', 'mobile', '手机号码'],
               
                 
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     
}

