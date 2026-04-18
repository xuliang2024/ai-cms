<?php
//系统参数设置
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AppConfig extends Admin {
	
    public function index() 
    {   
        // $id = 1;
        // $user_id = is_signin();
        // $flag_id = 3;
        // $id = is_signin();
        
        
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();



           //  if ($flag_id==1) {
           //      // code...

           // $r = DB::table('ai_dict_list')->update($data);

           //  }
           //  elseif($flag_id==0){

           //  $r = DB::table('ai_dict_list')->insert($data);     


           //  }
           
           // $r = DB::table('ai_dict_list')->update($data);
           
           $sql = 'update ai_dict_list set  promotion_img = "'.$data["promotion_img"].'",  kf_img = "'.$data["kf_img"].'",filter_text = "'.$data["filter_text"].'"  where id = 33;';
           // print_r($sql);
           // die();
           $r =  DB::query($sql);


            $this->success('提交成功', 'index');
            // if ($r) {
                
            //     $this->success('提交成功', 'index');
            // } else {
            //     $this->error('提交失败');
            // }
        }

        $info = DB::table('ai_dict_list')->find();
        if ($info!='') {
            // code...
             $flag_id = 1;
        }else{
             $flag_id = 0;
        }
       
        return ZBuilder::make('form')

            
            ->addOssImage('kf_img', '交友群图片', '', '', '', '', '', ['size' => '50,50'])
            ->addOssImage('promotion_img', '推广链接图片', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                //['textarea', 'callme_hint', '客服微信文案描述'],            
                ['textarea', 'filter_text', '配置绘画描述筛选词(每个词之间使用下划线分隔)'],             
                      

            ])


            
            ->setFormData($info)
            ->fetch();


    }

   
  
}