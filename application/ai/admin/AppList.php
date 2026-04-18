<?php
//用户信息
namespace app\ai\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AppList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_app_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_app_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('app_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name','名字'],
                    ['appid', 'appid'],
                    ['secret', 'secret'],
                    ['access_token', 'token'],
                    ['dy_token', 'dy_token'],
                    ['dy_salt', 'dy_salt'],
                    ['type', '应用类型',[0=>'小程序',1=>'抖音',2=>'快应用',3=>'快手小程序',4=>'支付宝小程序',5=>'QQ小程序',6=>'百度小程序',7=>'百度后台程序']],
                    
                    ['time', '时间'],
                    ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add',['title'=>'新增应用'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $r = DB::table('ai_app_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        
        // 显示添加页面
        return ZBuilder::make('form')
            
            ->addFormItems([
                    ['text','name', '应用名字'],
                    ['text','appid', 'AppID'],
                    ['text','secret', 'secret'],
                    ['select', 'type', '状态','',[0=>'小程序',1=>'抖音',2=>'快应用',3=>'快手小程序',4=>'支付宝小程序',5=>'QQ小程序',6=>'百度小程序',7=>'百度后台程序'],0],
                    ['text','wx_mchid','微信商户号'],
                    ['textarea','wx_pay_key','支付秘钥'],
                    ['text','wx_pay_cert_id','证书序列号'],
                    ['text','wx_apiv3_key','apiv3秘钥'],
                    ['text','notify_url','回调地址'],
                    ['text','dy_salt','抖音salt'],
                    ['text','dy_token','抖音token'],

                    ['textarea','comment', '备注'],

            ])
            ->fetch();
    }



    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_app_list')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_app_list')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text','name', '应用名字'],
                ['text','appid', 'AppID'],
                ['text','secret', 'secret'],
                ['select', 'type', '状态','',[0=>'小程序',1=>'抖音',2=>'快应用',3=>'快手小程序',4=>'支付宝小程序',5=>'QQ小程序',6=>'百度小程序',7=>'百度后台程序'],0],
                ['text','wx_mchid','微信商户号'],
                    ['textarea','wx_pay_key','支付秘钥'],
                    ['text','wx_pay_cert_id','证书序列号'],
                    ['text','wx_apiv3_key','apiv3秘钥'],
                    ['text','notify_url','回调地址'],
                    ['text','dy_salt','抖音salt'],
                    ['text','dy_token','抖音token'],

                 
                ['textarea','comment', '备注'],        
            ])
            ->setFormData($info)
            ->fetch();
    }




  
}