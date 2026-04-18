<?php
//用户信息
namespace app\ai\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_user_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_user_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('user_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['appid','应用ID'],
                ['open_id', '微信ID'],
                ['channel_name', '渠道商'],
                ['source_name', '推广标识'],
                ['session_key', '用户标识'],
                // ['has_cid', '绑定来源','status','',[0=>'未绑定',1=>'已绑定']],
                ['last_ip', 'IP'],
                ['time', '创建时间'],
                ['unionid', 'unionid'],
                ['phone_num', '手机号'],
                ['country_code', '国家号'],
                 ['user_name', '用户名字'],
                ['user_pwd', '用户密码'],
                ['head_img', '头像','img_url'],
                //  ['debug', '充值白名单','switch'],    
                // ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


  
}