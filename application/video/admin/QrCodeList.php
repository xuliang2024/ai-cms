<?php
// 登录二维码获取
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class QrCodeList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_qr_code_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_qr_code_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/QrCodeListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['user_type', '类型','status','',[0=>'速推',1=>'网页']],
                ['code', 'code'],
                ['qr_img', '微信二维码'],
                ['openid','openid'],
                ['unionid','unionid'],
                ['status', '状态','status','',[0=>'等待登录',1=>'已登录']],
                // ['status', '状态','text.edit'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'status', '状态'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
}
