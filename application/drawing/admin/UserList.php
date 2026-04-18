<?php
//用户信息
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();

        // $map[]=["appid","=","wxcf756553cfb65cb8"];//目前只展示AI绘画练习生小程序信息
        $data_list = DB::table('ai_user_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_user_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('user_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['channel_name', '渠道商'],
                ['source_name', '推广标识'],
                ['session_key', '用户标识'],
                ['queue_cnt', '同时作画次数'],
                ['stock_cnt', '库存数量'],
                ['p_user_id', '是否分销','switch'],
                ['is_vip', '是否VIP','status','',[0=>'默认用户',1=>'会员']],
                ['vip_level', '用户等级','status','',[0=>'默认用户',1=>'普通会员',2=>'高级会员',3=>'尊贵会员',10=>'周卡会员']],

                // ['has_cid', '绑定来源','status','',[0=>'未绑定',1=>'已绑定']],
                ['last_ip', 'IP'],
                ['time', '创建时间'],
                ['time_is_week', '周卡时间'],
                ['open_id', '微信ID'],
                ['appid','应用ID'],
                ['unionid', 'unionid'],
                ['phone_num', '手机号'],
                ['country_code', '国家号'],
                 ['user_name', '用户名字'],
                ['user_pwd', '用户密码'],
                ['head_img', '头像','img_url'],
                //  ['debug', '充值白名单','switch'],    
                ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->setSearchArea([  
                ['daterange', 'time', '日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', '用户id', '', '', ''],
                ['text', 'appid', '应用id', '', '', ''],
                ['text', 'session_key', '登录标识', '', '', ''],
                ['text', 'channel_name', '渠道商', '', '', ''],
              
              
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setHeight('auto')
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面
            
    }
    
    
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
        
            $r = DB::table('ai_user_info')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_user_info')->where('id',$id)->find();

        return ZBuilder::make('form')
            
            ->addFormItems([
                               
                ['text','open_id', '会员标识'],
                ['select', 'vip_level', '状态','',['0' => '普通','1'=>'会员','2'=>'高级会员','3'=>'尊贵会员','10'=>'周卡会员'],0],

                ['datetime', 'time_is_week', '周卡到期时间','必填 选择一个未来时间作为周卡结束的时间节点',date('Y-m-d H:i:s'),'YYYY-MM-DD HH:mm:ss','autocomplete=off'],
                // ['datetime', 'time_is_week', '周卡到期时间','必填 选择一个未来时间作为周卡结束的时间节点','','YYYY-MM-DD HH:mm:ss','autocomplete=off'],



                ['select', 'p_user_id', '是否分销用户','',['0' => '普通','1'=>'分销'],0],                
                
                ['text','session_key', 'key'],

            ])
             // ->setTrigger('vip_level','10','time_is_week')
            ->setFormData($info)
            ->fetch();
    }



  
}