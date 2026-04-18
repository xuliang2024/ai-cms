<?php
//资讯活动首页入口
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
    
class InformationIndex extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_information_index')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_information_index', $map);
                
        return ZBuilder::make('table')
            ->setTableName('information_index') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['sub_title', '副标题'],
                    ['img', '活动封面','img_url'],
                    ['type', '类型',['0' => '日常资讯', '1' => '活动详情','2'=>'新手教程','3'=>'纯图','4'=>'列表','5'=>'单风格']],
                    ['jump_url','跳转路径'],
                    ['detail_id','详情ID'],
                    ['style_id','风格ID'],
                    ['active_title','风格标题'],
                    ['detail_title','详情标题'],
                    ['label_text','标签'],
                    // ['money_week','周卡金额'],
                    ['shop_id',  '充值模版id'],
                    // ['promotion_url',  '推广链接'],
                    ['video_url', '视频链接','image_video'],
                    
                    ['status', '状态','switch'],
                    ['ios_status', 'ios充值','switch'],
                    ['show_week', '周卡展示','switch'],
                    ['sort', '排序', 'text.edit'],               
                    ['time', '时间'],
                    ['right_button', '操作', 'btn'],
            ])
            // ->hideCheckbox()
            ->addTopButton('add',['title'=>'新增'])
            ->addTopButton('delete')
           
            ->addRightButton('edit',[
                'title'=>'编辑活动',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
           
            
            ->addRightButton('custom', [
                'title'=>'查看链接',
                'icon'=>'',
                'class'=>'btn btn-warning btn-rounded',
                'href'=>url('drawing/information_index/ads_url',['style_id'=>'__style_id__']),
                ],     
                true,['area' => ['800px', '50%'], 'title' => '查看链接'])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
             ->setColumnWidth('right_button', 200)
            ->fetch(); // 渲染页面

    }

     
    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            if ($data["detail_id"] != '' ) {
                // code...
            $data_pay_name = DB::query('select  title  from ai_information_details where id = "'.$data["detail_id"].'"  ;');
             foreach ($data_pay_name as $value){   
                $data["detail_title"] = $value["title"];
             } 


            }


        
            if ($data["type"] == 5 ) {
                // code...
            $data_name = DB::query('select  name,appid  from ai_app_list where type = 1  ;');
            $data_style = DB::query('select  name  from ai_style_list where id = "'.$data["style_id"].'" ;');
            $style_name = "";
            foreach ($data_style as $value){   
                $style_name = $value["name"];
            }    

             foreach ($data_name as $value){   
                // $data["detail_title"] = $value["title"];
                $appid = $value["appid"];
                $app_name = $value["name"];
                $source_name = date("mdHi",time()).rand(10,999);

            DB::query('insert into ai_channel_url set channel_name = "'.$appid.'",source_name = "'.$source_name.'"
            ,style_id = "'.$data["style_id"].'",shop_id = "'.$data["shop_id"].'",app_id = "'.$appid.'"
            ,style_name = "'.$style_name.'",app_name = "'.$app_name.'",jump_url = "'.$data["jump_url"].'"
            ,app_type = "1";');     
             } 


             $data["shop_id"] = 30;
             $data["show_week"] = 1;
             $data["ios_status"] = 1;


            }






            $r = DB::table('ai_information_index')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        $detail_id = array();
       
        $datas = DB::query("select id ,title  from ai_information_details where status = 1 ;");
        $is_we_apps = "";
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["title"]."-".$data["id"]);
                 
                $detail_id = $detail_id +$data;
            }   

         $shop_ids =$this->getShop();
         $style_ids =$this->getstyle();
            
        // 显示添加页面
        return ZBuilder::make('form')
            // ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            // ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                    ['ossimage','img', '活动封面', '', '', '', '', '', ['size' => '50,50']],
                    ['ossvideo','video_url', '视频链接',''],
                    ['text','title', '标题'],
                    ['textarea', 'sub_title', '副标题'],
                    
                    ['select', 'type', '资讯类型','',['0' => '日常咨询', '1' => '活动详情','2'=>'新手教程','3'=>'纯图','4'=>'列表','5'=>'单风格'],5],   
                    ['text', 'jump_url', '跳转路径'],    
                    ['text', 'sort', '排序'],
                     ['select', 'detail_id', '选择咨询活动的详情内容', '请选择资讯活动的详情内容', $detail_id],
                     ['select', 'style_id', '选择单风格ID', '请选择单风格ID', $style_ids],
                     ['text', 'label_text', '标签文字'],
                     ['text', 'label_text_color', '标签文字颜色'],
                     ['text', 'label_background', '标签背景颜色'],
                     ['text', 'active_title', '风格标题'],
                     // ['text', 'promotion_url', '推广链接'],
                     ['textarea', 'attch', '限制活动展示(填写appid则不在此小程序上展示此活动，多个appid用回车键换行区分)'],
                     // ['text', 'money_week', '周卡金额（单位：分）'],  
                    // ['select', 'ios_status', 'ios充值开启','',['0' => '不开启', '1' => '开启'],1],
                    ['select', 'show_week', '周卡是否展示','',['0' => '不展示', '1' => '展示'],1],
                    ['select', 'shop_id', '充值', '请选择充值档位', $shop_ids],     
                    ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线'],0],                 
                                   

            ])
             ->setTrigger('type','0,1,2,3','detail_id,img')
             ->setTrigger('type','5','style_id,active_title,video_url')
             ->setTrigger('type','1','label_text,label_text_color,label_background,show_week,shop_id')
            ->fetch();
    }

    public function getShop(){
        $order_ids = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        $datas = DB::query("select id ,title,money  from ai_shop_info;");
        foreach ($datas as $data) {
            $data =array( $data["id"] =>$data["id"]."-".$data["title"]."-".$data["money"]);     
                $order_ids = $order_ids +$data;
        }
        return $order_ids;
    }


    public function getstyle(){
        $order_ids = array();
            
        $data_index = DB::query("select style_id  from ai_information_index where style_id <> '0';");
        $data_style_id = array();
        // $indata = '';
        foreach ($data_index as $data) {

        $style_id111 = $data["style_id"];    

        // $data_style_id = array($style_id111) ;
        array_push($data_style_id, $style_id111);
       
        // print($data_style_id);
        }    

        
         // $indata  = implode("', '", $data_style_id);
         $indata  = "'" . implode("', '", $data_style_id) . "'";

        print($indata);


        $datas = DB::query('select id ,name  from ai_style_list where id not in ( '.$indata.');');
        // $datas = DB::query('select id ,name  from ai_style_list ;');
        foreach ($datas as $data) {
            $data =array( $data["id"] =>$data["id"]."-".$data["name"]);     
                $order_ids = $order_ids +$data;
        }
        return $order_ids;
    }


        public function getstyle_edit($style_id = 0){
        $order_ids = array();
            
        $data_index = DB::query("select style_id  from ai_information_index where style_id <> '0';");
        $data_style_id = array();
        // $indata = '';
        foreach ($data_index as $data) {

        $style_id111 = $data["style_id"];    

        // $data_style_id = array($style_id111) ;
        array_push($data_style_id, $style_id111);
       
        // print($data_style_id);
        }    

        
         
         $index = array_search($style_id, $data_style_id);
            if ($index !== false) {
                unset($data_style_id[$index]);
            }



         $indata  = "'" . implode("', '", $data_style_id) . "'";

        print($indata);


        $datas = DB::query('select id ,name  from ai_style_list where id not in ( '.$indata.');');
        // $datas = DB::query('select id ,name  from ai_style_list ;');
        foreach ($datas as $data) {
            $data =array( $data["id"] =>$data["id"]."-".$data["name"]);     
                $order_ids = $order_ids +$data;
        }
        return $order_ids;
    }




    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            if ($data["detail_id"] != '' ) {
                // code...
            $data_pay_name = DB::query('select  title  from ai_information_details where id = "'.$data["detail_id"].'"  ;');
             foreach ($data_pay_name as $value){   
                $data["detail_title"] = $value["title"];
             } 


            }

            if ($data["shop_id"]!='') {
              // $data_lora = DB::query('select  money  from ai_shop_info where id = "'.$data["shop_id"].'";');
              
              // $money_week='';
              
              
              
              
              // foreach ($data_lora as $value) {
              //     // code...
              //    $money_week=$value["money"];
                
                
              // }

            

              // $data["money_week"] = $money_week;
             
              
            }else{
                $this->error('请选择充值模版');

            }   


            // if ($data["type"] == 5 ) {
            //     // code...
            // $data_name = DB::query('select  name,appid  from ai_app_list where type = 1  ;');
            // $data_style = DB::query('select  name  from ai_style_list where id = "'.$data["style_id"].'" ;');
            // $style_name = "";
            // foreach ($data_style as $value){   
            //     $style_name = $value["name"];
            // }    

            //  foreach ($data_name as $value){   
            //     // $data["detail_title"] = $value["title"];
            //     $appid = $value["appid"];
            //     $app_name = $value["name"];
            //     $source_name = date("mdHi",time()).rand(10,999);

            // DB::query('insert into ai_channel_url set channel_name = "'.$appid.'",source_name = "'.$source_name.'"
            // ,style_id = "'.$data["style_id"].'",shop_id = "'.$data["shop_id"].'",app_id = "'.$appid.'"
            // ,style_name = "'.$style_name.'",app_name = "'.$app_name.'",jump_url = "'.$data["jump_url"].'"
            // ,app_type = "1";');     
            //  } 


            //  $data["shop_id"] = 30;
            //  $data["show_week"] = 1;
            //  $data["ios_status"] = 1;


            // }
            

            $r = DB::table('ai_information_index')->where('id',$id)->update($data);
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $detail_id = array();
       
        $datas = DB::query("select id ,title  from ai_information_details where status = 1 ;");
        $is_we_apps = "";
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["title"]."-".$data["id"]);
                 
                $detail_id = $detail_id +$data;
            }   

         
        $info = DB::table('ai_information_index')->where('id',$id)->find();

        $shop_ids =$this->getShop();  
         $style_ids =$this->getstyle_edit($info["style_id"]);  


        return ZBuilder::make('form')
                       ->addOssImage('img', '活动封面', '', '', '', '', '', ['size' => '50,50'])
                      
        ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                    ['text','title', '标题'],
                    ['textarea', 'sub_title', '副标题'],

                    ['select', 'type', '资讯类型','',['0' => '日常咨询', '1' => '活动详情','2'=>'新手教程','3'=>'纯图','4'=>'列表','5'=>'单风格']],   
                    ['text', 'jump_url', '跳转路径'],

                    ['text', 'sort', '排序'],
                     ['select', 'detail_id', '选择咨询活动的详情内容', '请选择资讯活动的详情内容', $detail_id],
                     ['select', 'style_id', '选择单风格ID', '请选择单风格ID', $style_ids],
                     ['text', 'label_text', '标签文字'],
                     ['text', 'label_text_color', '标签文字颜色'],
                     ['text', 'label_background', '标签背景颜色'],
                     ['text', 'active_title', '风格标题'],
                     ['text', 'promotion_url', '推广链接'],
                     ['textarea', 'attch', '限制活动展示(填写appid则不在此小程序上展示此活动，多个appid用回车键换行区分)'],
                     // ['text', 'money_week', '周卡金额（单位：分）'],  
                    ['select', 'ios_status', 'ios充值开启','',['0' => '不开启', '1' => '开启']],
                    ['select', 'show_week', '周卡是否展示','',['0' => '不展示', '1' => '展示']],
                    ['select', 'shop_id', '充值', '请选择充值档位', $shop_ids],   
                    ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线']],                 

            ])
             ->setTrigger('type','0,1,2,3','detail_id,img')
             ->setTrigger('type','5','style_id,active_title,video_url,ios_status')
              ->setTrigger('type','1,5','label_text,label_text_color,label_background,show_week,shop_id')
            
            ->setFormData($info)
            ->fetch();
    }

   public function ads_url($style_id = null){
        if ($style_id === null) $this->error('缺少参数');


        // $info = DB::table('ai_channel_url')->where('style_id',$style_id)->get();

        $data_style = DB::query('select * from ai_channel_url where style_id = "'.$style_id.'" ;');
            
        // $fields = [];
        $table_data = [
            ['小程序名字', '投放链接']
        ]; 
            foreach ($data_style as $info){   
               
                $channel_name = $info["channel_name"];
                $source_name = $info["source_name"];
                $style_id = $info["style_id"];
                $jump_url = $info["jump_url"];
                $app_name = $info["app_name"];


               
                $first_char = substr($jump_url, 0, 1);
                if (strcmp($first_char, "/") == 0) {
                   
                    $jump_url = substr($jump_url, 1); // 截取掉第一个字符
                   
                } 





                $ads_url = $jump_url."&c=".$channel_name."&s=".$source_name;
                // $ads_url = $jump_url."?style_id=".$style_id."&c=".$channel_name."&s=".$source_name;
                
                $fields = array($app_name, $ads_url);  
                // 把新的数组值添加到二维数组的末尾
                array_push($table_data, $fields);
              
            }    

       

         $table_title = "抖音任务投放链接";
         
            
        return ZBuilder::make('form')
        ->addStatic('style_name', '风格名称','', $info["style_name"], $info["style_name"] )
       
        ->layout(['style_name'   => '4'])
        ->addFormItems([
            ['complexTable', 'list_user', $table_title, $table_data,true],
           
        ])
        ->hideBtn('submit,back')
        // ->css('video')
        ->setFormData($info)
        ->fetch();

   }
     

  
}