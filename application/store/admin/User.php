<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class User extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_user')->where($map)
        ->order($order)
        ->paginate();

        cookie('hm_user', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/UserModel',2) // 设置数据表名
            ->addOrder('consume,money,money_p1,money_p2,moeny_rebeat,moeny_rebeat_p2,user_current_amount') // 添加排序
            ->addColumns([ // 批量添加列
                    ['id', '用户ID'],
                    ['username', '用户名'],
                    ['email', '电子邮箱'],
                    
                    ['p1','p1'],
                    ['p2','p2'],
                    // ['money_p1','p1返佣金额'],
                    // ['money_p2','p2返佣金额'],
                    ['consume','总消费'],
                    ['money','余额'],
                    ['user_current_amount','销售额'],
                    ['year_month_id','当月日期'],
                    ['user_gradient_scale','梯度奖励比'],
                    ['createtime','创建时间','datetime'],
                    ['user_extend_type','分销白名单','text.edit'],
                    // ['user_extend_type','分销白名单','switch'],
                    
                    ['moeny_rebeat','p1返佣比例'],
                    ['moeny_rebeat_p2','p2返佣比例'],

                    ['gongmao_status', '工猫签约状态','status','',[0=>'未签约',1=>'待签约',2=>'签约文件生成中',3=>'签约完成',4=>'签约失败，绑定手机号和签约时的手机号不一致']],
                    ['gongmao_mobile_status', '手机是否一致','status','',[0=>'未绑定',1=>'已绑定',2=>'已绑定但不一致',3=>'绑定成功且签约完成']],
                    
                    ['mobile', '手机号'],
                    ['mobile_gongmao','签约手机号'],
                    ['name_gongmao','签约名字'],
                    ['identity_gongmao','签约证件号'],
                    ['bank_account_no_gongmao','签约银行卡'],
                    ['alipay_account_no_gongmao','签约支付宝号'],
                    

                    ['gongmao_status','工猫签约状态','text.edit'],//当需要重置状态则输入0
                    ['gongmao_mobile_status','手机号是否一致','text.edit'],//当需要重置状态则输入0
                    ['mobile', '手机号','text.edit'],
                    ['mobile_gongmao','签约手机号','text.edit'],
                    // ['name_gongmao','签约名字','text.edit'],
                    // ['identity_gongmao','签约证件号','text.edit'],
                    // ['bank_account_no_gongmao','签约银行卡','text.edit'],
                    // ['alipay_account_no_gongmao','签约支付宝号','text.edit'],
                    
                    
                    

                    ['contract_file_url_gongmao', '签约合同地址', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 100, '...');
                    }],

                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'createtime', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', '用户ID'],
               
                ['select','gongmao_status', '工猫签约状态','','',[0=>'未签约',1=>'待签约',2=>'签约文件生成中',3=>'签约完成',4=>'签约失败，绑定手机号和签约时的手机号不一致']],

                ['text', 'name_gongmao', '签约名'],
                ['text', 'identity_gongmao', '签约证件'],
                ['text', 'username', 'username'],
                ['text', 'mobile', '手机号码'],
                ['text', 'email', '邮箱'],
                ['text', 'p1', 'p1'],
                ['text', 'p2', 'p2'],
                ['text', 'user_extend_type', '分销白名单'],
                
                
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     
}

