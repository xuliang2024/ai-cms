<?php
namespace app\video\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Index extends Admin {
	
    public function index() 
    {
        

        $today = date("Y-m-d");
        // 用户数
        $this->assign('user_cnt', Db::connect('translate')->table('ts_users')->count());
        // 总订单数
        $this->assign('order_cnt', Db::connect('translate')->table('ts_pay_order_info')->where('status = 2 ')->count());
        // 总收入
        $this->assign('order_money', (Db::connect('translate')->table('ts_pay_order_info')->where('status = 2 ')->sum('money')/100.0));
        // 总会员数
        // $this->assign('paly_cnt', Db::connect('translate')->table('ts_users')->where('vip_level = 1 ')->count());

        // 总算力部署数
        $this->assign('deployment_cnt', Db::connect('translate')->table('ts_deployment_requests')->count());

        // 洗爆款数
        $this->assign('video_infos_cnt', Db::connect('translate')->table('ts_video_infos')->where('status = 2 ')->count());


        // 总mj用户数
        $this->assign('mj_account_cnt', Db::connect('translate')->table('ts_user_mj_account')->where('status = 1  and user_type = 0')->count());


        // 总mj任务合集数
        $this->assign('mj_collection_task_cnt', Db::connect('translate')->table('ts_user_mj_collection_task')->where('status = "SUCCESS"  and user_type = 0 ')->count());

        //总小数制作数
        $this->assign('book_list_cnt', Db::connect('translate')->table('ts_book_list')->where('status = 2 ')->count());

        // 总视频翻译
        $this->assign('video_task_cnt', Db::connect('translate')->table('ts_video_task')->where('status = 2 ')->count());

        //总dall3绘画数量
        $this->assign('dall_3_tasks_cnt', Db::connect('translate')->table('ts_dall_3_tasks')->where('status = 2 ')->count());
        // 总换脸视频数
        $this->assign('face_tasks_cnt', Db::connect('translate')->table('ts_face_tasks')->where('status = 2 ')->count());
        // 总图生视频数 
        $this->assign('video_generation_task_cnt', Db::connect('translate')->table('ts_video_generation_task')->where('status = "succeeded" ')->count());   




        
        $this->assign('today_user_cnt', (Db::connect('translate')->table('ts_users')->where(" time > date_sub(curdate(),interval 0 day)")->count()));
        
        $this->assign('today_order_cnt', (Db::connect('translate')->table('ts_pay_order_info')->where(" status = 2   and  time > date_sub(curdate(),interval 0 day)")->count()));  


        $this->assign('today_order_money', (Db::connect('translate')->table('ts_pay_order_info')->where(" status = 2   and  time > date_sub(curdate(),interval 0 day)")->sum('money')/100.0));


        // $this->assign('today_play_cnt', (Db::connect('translate')->table('ts_users')->where(" vip_level = 1 and time > date_sub(curdate(),interval 0 day)")->count()));

        $this->assign('today_deployment_cnt', (Db::connect('translate')->table('ts_deployment_requests')->where(" time > date_sub(curdate(),interval 0 day)")->count()));


        // 洗爆款数
        $this->assign('today_video_infos_cnt', Db::connect('translate')->table('ts_video_infos')->where('status = 2  and time > date_sub(curdate(),interval 0 day)')->count());


        // 总mj用户数
        $this->assign('today_mj_account_cnt', Db::connect('translate')->table('ts_user_mj_account')->where('status = 1  and user_type = 0 and time > date_sub(curdate(),interval 0 day)')->count());


        // 总mj任务合集数
        $this->assign('today_mj_collection_task_cnt', Db::connect('translate')->table('ts_user_mj_collection_task')->where('status = "SUCCESS"  and user_type = 0 and time > date_sub(curdate(),interval 0 day)')->count());

        //总小数制作数
        $this->assign('today_book_list_cnt', Db::connect('translate')->table('ts_book_list')->where('status = 2 and time > date_sub(curdate(),interval 0 day)')->count());

        // 总视频翻译
        $this->assign('today_video_task_cnt', Db::connect('translate')->table('ts_video_task')->where('status = 2 and time > date_sub(curdate(),interval 0 day)')->count());

        //总dall3绘画数量
        $this->assign('today_dall_3_tasks_cnt', Db::connect('translate')->table('ts_dall_3_tasks')->where('status = 2 and time > date_sub(curdate(),interval 0 day)')->count());
        // 总换脸视频数
        $this->assign('today_face_tasks_cnt', Db::connect('translate')->table('ts_face_tasks')->where('status = 2 and time > date_sub(curdate(),interval 0 day)')->count());
        // 总图生视频数 
        $this->assign('today_video_generation_task_cnt', Db::connect('translate')->table('ts_video_generation_task')->where('status = "succeeded" and time > date_sub(curdate(),interval 0 day)')->count());  








        $this->assign('page_title', '仪表盘');

        return $this->fetch(); // 渲染模板

        

    }


      //获取用户数据
    public function getuserdata(){

        $data_list = Db::query("select * from book_user_info where  time > date_sub(curdate(),interval 14 day);");
        $c_data = array();
        foreach ($data_list as $value) {
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d');
            // echo $js_day;
            if(!array_key_exists($js_day,$c_data)){
                $c_data[$js_day]  = 1;
            }else{
                $c_data[$js_day]  += 1;
            }
        }
        $x_data = array_keys($c_data);
        $y_data = array_values($c_data);

        return json(array("x_data"=>$x_data,"y_data"=>$y_data));

    }


    //获取用户数据小时计
    public function getuserdata_h(){

        $data_list = Db::query("select * from book_user_info where  time > date_sub(curdate(),interval 0 day);");
        $c_data = array();
        foreach ($data_list as $value) {
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d H');
            // echo $js_day;
            if(!array_key_exists($js_day,$c_data)){
                $c_data[$js_day]  = 1;
            }else{
                $c_data[$js_day]  += 1;
            }
        }
        $x_data = array_keys($c_data);
        $y_data = array_values($c_data);

        return json(array("x_data"=>$x_data,"y_data"=>$y_data));

    }



    //获取收入趋势
    public function getorderdata(){
        $data_list = Db::query("select * from book_order_info where  status = 1 and  time > date_sub(curdate(),interval 14 day);");
        
        $x_data_count = array();
        $x_data_sum = array();
        
        foreach ($data_list as $value) {
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d');
            
            //计数
            if(!array_key_exists($js_day,$x_data_count)){
                $x_data_count[$js_day]  = 1;
            }else{
                $x_data_count[$js_day]  += 1;
            }

            //求和
            if(!array_key_exists($js_day,$x_data_sum)){
                $x_data_sum[$js_day]  = $value["money"]/100;
            }else{
                $x_data_sum[$js_day]  += $value["money"]/100;
            }

            $x_data_sum[$js_day] = round($x_data_sum[$js_day],2);

        }
        
        $x_data = array_keys($x_data_sum);
        $y_data_count = array_values($x_data_count);
        $y_data_sum = array_values($x_data_sum);

        return json(array("x_data"=>$x_data,"y_data_count"=>$y_data_count,"y_data_sum"=>$y_data_sum));

    }



    public function getflowdata_h(){
        
        $data_list = Db::query("select * from book_read_record where time > date_sub(curdate(),interval 0 day);");
        
        $c_data = array();
        foreach ($data_list as $value) {
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d H');
            // echo $js_day;
            if(!array_key_exists($js_day,$c_data)){
                $c_data[$js_day]  = 1;
            }else{
                $c_data[$js_day]  += 1;
            }
        }
        $x_data = array_keys($c_data);
        $y_data = array_values($c_data);        
        
        return json(array("x_data"=>$x_data,"y_data"=>$y_data));    
        

    }
    public function getflowdata(){
        
        $data_list = Db::query("select * from book_read_record where  time > date_sub(curdate(),interval 14 day);");
        
        $c_data = array();
        foreach ($data_list as $value) {
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d');
            // echo $js_day;
            if(!array_key_exists($js_day,$c_data)){
                $c_data[$js_day]  = 1;
            }else{
                $c_data[$js_day]  += 1;
            }
        }
        $x_data = array_keys($c_data);
        $y_data = array_values($c_data);        

        return json(array("x_data"=>$x_data,"y_data"=>$y_data));    


    }



}