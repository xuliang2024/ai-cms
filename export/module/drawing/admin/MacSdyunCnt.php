<?php
//访问记录
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MacSdyunCnt extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_mac_sdyun_cnt')->where($map)
        ->order('update_time desc')
        ->paginate();

        cookie('ai_mac_sdyun_cnt', $map);
        
        return ZBuilder::make('table')
            ->setTableName('mac_sdyun_cnt') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mac_address','mac地址'],
                ['cnt', '请求次数'],
                ['update_time', '更新时间'],
                ['time', '创建时间'],
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([  

                ['text', 'mac_address', 'mac地址', '', '', ''],
              
            ])
            ->setHeight('auto')
            // ->addTopButton('delete')
            ->fetch(); // 渲染页面

    }




  
}