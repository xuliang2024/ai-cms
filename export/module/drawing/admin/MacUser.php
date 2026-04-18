<?php
//lora模型列表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MacUser extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_mac_user_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_mac_user_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('mac_user_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mac_address','mac'],
                ['user_id','user_id'],
                ['draw_cnt','总做图','text.edit'],
                ['top_cnt','快速','text.edit'],
                ['duration_expire_time', '时长码过期时间','text.edit'],
                ['expire_time', '过期时间','text.edit'],
                ['last_login_time', '最近登录时间'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '新增', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'last_login_time', '活跃日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'duration_expire_time', '时长过期日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'expire_time', '过期日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'mac_address', 'mac', '', '', ''],
                ['text', 'user_id', 'user_id', '', '', ''],
              
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }

   


  
}