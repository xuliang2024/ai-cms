<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AppList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_app_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_app_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/AppListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['appid', 'appid'],
                    ['secret', 'secret'],
                    ['access_token','access_token'],
                    ['name','name'],
                    ['wx_mchid', 'wx_mchid'],
                    // ['wx_pay_key', 'wx_pay_key'],
                    ['wx_pay_key', 'wx_pay_key', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                    }],
                    ['wx_pay_cert_id', 'wx_pay_cert_id'],
                    ['wx_apiv3_key', 'wx_apiv3_key'],
                    ['notify_url','notify_url'],
                    ['dy_token','dy_token'],
                    ['dy_salt','dy_salt'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'appid', 'appid'],
                
                
               
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_app_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'appid', 'appid'],
                ['text', 'secret', 'secret'],
                ['text', 'access_token', 'access_token'],
                ['text', 'name', 'name'],
                ['text', 'wx_mchid', 'wx_mchid'],
                ['text', 'wx_pay_cert_id', 'wx_pay_cert_id'],
                ['text', 'wx_apiv3_key', 'wx_apiv3_key'],
                ['text', 'notify_url', 'notify_url'],
                ['text', 'dy_token', 'dy_token'],
                ['textarea', 'dy_salt', 'dy_salt'],
              
                ['textarea', 'wx_pay_key', 'wx_pay_key'],
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

            $r = DB::connect('translate')->table('ts_app_list')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_app_list')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'appid', 'appid'],
                ['text', 'secret', 'secret'],
                ['text', 'access_token', 'access_token'],
                ['text', 'name', 'name'],
                ['text', 'wx_mchid', 'wx_mchid'],
                ['text', 'wx_pay_cert_id', 'wx_pay_cert_id'],
                ['text', 'wx_apiv3_key', 'wx_apiv3_key'],
                ['text', 'notify_url', 'notify_url'],
                ['text', 'dy_token', 'dy_token'],
                ['textarea', 'dy_salt', 'dy_salt'],
              
                ['textarea', 'wx_pay_key', 'wx_pay_key'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}

