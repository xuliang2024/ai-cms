<?php
// 用户操作记录表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class UserChangeLogs extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();

            // code...
        $map[]=["admin_userid","eq", is_signin()];
        
        // 使用动态指定的数据库连接进行查询
        $data_list =  DB::connect('translate')->table('ts_user_change_logs')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_users_change_logs', $map);
        return ZBuilder::make('table')
            
            
            // ->setConnection('translate')
             // ->setTableName('users') // 设置数据表名
             ->setTableName('video/UserChangeLogsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['user_id','用户ID'],
                    ['vip_level_start', '记录前会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['vip_level_end', '记录后会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['vip_time_start','记录前vip时间'],
                    ['vip_time_end','记录后vip时间'],
                    ['commission_rate_start','记录后佣金比例'],
                    ['commission_rate_end','记录后佣金比例'],
                    ['admin_userid','运营ID'],
                    ['admin_username','运营昵称'],
                    ['time','创建时间'],
                
            ])

           


            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', 'user_id'],
                
              
            ])
            
            ->setRowList($data_list) // 设置表格数据
            
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

  



   






}
