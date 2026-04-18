<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class RoleLibraryUser extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
         $map[]=["creator_type","=",1];
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_role_library')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_role_library_user', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/RoleLibraryUserModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['user_id', 'user_id'],
                    ['name', '角色名称'],
                    ['content','提示词'],
                    ['style_name','风格名称'],
                    ['type_name','类型名称'],
                    ['image_url_us_type','image_url_us_type'],
                    ['type', '平台类型','status','',[0=>'mj',1=>'sd']],
                    // ['label','搜索标签','text.edit'],
                    // ['sort','排序','text.edit'],
                    ['image_url', '封面','img_url'],
                    // ['status', 'status','switch'],
                    // ['comment','备注'],
                    ['time','time'],
                    // ['right_button', '操作', 'btn']
                   
                       
            ])
                    
            ->setSearchArea([ 
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['text', 'name', '角色名字'],
                ['text', 'user_id', 'user_id'],
                ['text', 'image_url_us_type', 'image_url_us_type'],
                ['text', 'style_name', '风格名称'],
                ['text', 'type_name', '类型名称'],
                ['select', 'type', '平台类型', '', '', [0=>'mj',1=>'sd']],
                
               
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

}

