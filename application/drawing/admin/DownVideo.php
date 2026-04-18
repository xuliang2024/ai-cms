<?php
//绘画机器列表
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DownVideo extends Admin {
	
    public function index() 
    {
        $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_down_video')->where($map)
        ->order($order)
        ->paginate();

        cookie('ai_down_video', $map);
        
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            ->setTableName('down_video') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id','用户'],
                    ['title','名称'],
                    ['url', '下载地址'],
                    ['thumbnail', '封面','img_url'],
                    ['medias_url', '视频', 'callback', function($medias_url) {
                        return "<a href='{$medias_url}' target='_blank'>查看视频</a>";
                    }],
                    ['duration', '时长'],
                    ['time', '更新时间'],
                   
            ])
            ->hideCheckbox()
            ->setSearchArea([  
                ['text', 'user_id', '用户', '', '', ''],
            ])
            ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }


  
}