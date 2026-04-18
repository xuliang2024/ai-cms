<?php
//绘画机器列表
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\admin\model\Boxdeviceinfo as BoxdeviceinfoModel;
use think\Db;

class drawHost extends Admin {
	
    public function index() 
    {
        $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_draw_host')->where($map)
        ->order($order)
        ->paginate();

        cookie('ai_draw_host', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            // ->setPageTitle('用户管理') // 设置页面标题
            ->setTableName('draw_host') // 设置数据表名
            // ->setSearch(['id' => 'ID', 'username' => '用户名', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['title','名称','text.edit'],
                    ['sub_title', '副标题','text.edit'],
                    ['status', '状态','switch'],
                    ['cnt', '绘画数量'],
                    ['host', 'host'],
                    ['time', '更新时间'],
                   
            ])
            ->hideCheckbox()
            // ->addTopButtons($contro_top_btn) // 批量添加顶部按钮
            // ->addTopButton('custom', $btn_export) // 添加授权按钮
            ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面

    }


  
}