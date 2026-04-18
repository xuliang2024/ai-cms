<?php
// 
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class RealtimeStatisticalReward extends Admin {
    
    public function index() 
    {
        
        $order = $this->getOrder('year_month_id desc');
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('faka_fyshark_com')->table('hm_realtime_statistical_reward')->where($map)
        ->order($order)
        ->paginate();

        cookie('hm_realtime_statistical_reward', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/RealtimeStatisticalRewardModel',2) // 设置数据表名
            ->addOrder('pay_money') // 添加排序
            // ->addOrder('consume,money,money_p1,money_p2') // 添加排序
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['username', '用户名'],
                    ['nickname','昵称'],
                    ['year_month_id','日期'],
                    ['balance_status', '是否已奖励','status','',[0=>'未奖励',1=>'已转入余额']],

                    // ['extend_dict_id','关联分成ID'],
        
                    ['amount_bonus_month','梯度奖励'],
                    ['gradient_scale','梯度比例'],
                    ['sales_amount','梯度销售额'],

                    
                   
                    ['pay_money','销售额'],
                    // ['p1','所属p1'],
                    // ['p2','所属p2'],
                    ['create_time','创建时间','datetime'],


                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                // ['daterange', 'year_month_id', '日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'year_month_id', '日期'],
                ['text', 'user_id', '用户ID'],
                 ['select', 'balance_status', '状态', '', '', [0=>'未奖励',1=>'已转入余额']],
                ['text', 'username', '用户名字'],
                ['text', 'nickname', '昵称'],
                ['text', 'sales_amount', '梯度销售额'],
                ['text', 'amount_bonus_month', '梯度奖励'],
                ['text', 'gradient_scale', '梯度比例'],

               
                
               
            ])
            // ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
             ->addTopButton('balance_carried',[
                'title'=>'转入余额',
                'class' => 'btn btn-success js-get',
                'icon' => 'si si-diamond',
                'href' => '/admin.php/store/realtime_statistical_reward/balance_carried.html?' . $this->request->query(),
                
            ])
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

  public  function balance_carried(){


    $ids = input('get.ids');

    if(empty($ids) ){
        $this->error('请勾选需要转入的数据');
    }
    // 将ids字符串分割为数组
    $ids_array = explode(',', $ids);
    
    // 查询数据库
    $data_list = DB::connect('faka_fyshark_com')->table('hm_realtime_statistical_reward')->whereIn('id', $ids_array)->select();

    if (empty($data_list)) {
        $this->error('没有找到匹配的数据');
    }


    foreach ($data_list as $data) {

    $info = DB::connect('faka_fyshark_com')->table('hm_user')->where('id',$data["user_id"])->find();
    $before = $info["money"];
    $after = $info["money"]+$data["amount_bonus_month"];
    $value = $data["amount_bonus_month"];
    $create_time = time();




    $r = DB::connect('faka_fyshark_com')->table('hm_realtime_statistical_reward')->where('id',$data["id"])->update(['balance_status' => 1]);

    $u = DB::connect('faka_fyshark_com')->table('hm_user')->where('id',$data["user_id"])->inc('money', $data["amount_bonus_month"])->update();

    
    // 插入新数据

        // 插入新数据到hm_bill表
    $newData = [
        'user_id' => $data["user_id"],
        'content' => '每月梯度额外奖励',
        'before' => $before,
        'after' => $after,
        'value' => $value,
        'pay_username' => '速推官方',
        'create_time' => $create_time
    ];
    DB::connect('faka_fyshark_com')->table('hm_bill')->insert($newData);
             
                   
    }


    $this->success('已转入余额并创建新数据', 'index');   
    


}


     
}

