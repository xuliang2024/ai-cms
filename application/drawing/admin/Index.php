<?php
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

use think\Db;

class Index extends Admin
{
    public function index()
    {
        
       
        $today = date("Y-m-d");
        $this->assign('user_cnt', Db::table('ai_user_info')->count());
        $this->assign('order_cnt', Db::table('ai_draw_img_list')->count());

        $this->assign('order_money', (Db::table('ai_order_info')->where('status = 1 ')->sum('money')/100.0));

        $this->assign('paly_cnt', Db::table('ai_draw_task_list')->where("status = 2")->count());
        
        $this->assign('today_user_cnt', (Db::table('ai_user_info')->where(" time > date_sub(curdate(),interval 0 day)")->count()));
        
        $this->assign('today_order_cnt', (Db::table('ai_draw_img_list')->where("time > date_sub(curdate(),interval 0 day)")->count()));  


        $this->assign('today_order_money', (Db::table('ai_order_info')->where(" status = 1   and  time > date_sub(curdate(),interval 0 day)")->sum('money')/100.0));


        $this->assign('today_play_cnt', (Db::table('ai_draw_task_list')->where("status = 2  and time > date_sub(curdate(),interval 0 day)")->count()));



        $this->assign('page_title', '仪表盘');
        return $this->fetch(); // 渲染模板

    }

    //获取用户数据
    public function getuserdata(){

        $data_list = Db::query("select * from ai_user_info where  time > date_sub(curdate(),interval 14 day);");
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

        $data_list = Db::query("select * from ai_user_info where  time > date_sub(curdate(),interval 0 day);");
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
        $data_list = Db::query("select * from ai_order_info where  status = 1 and  time > date_sub(curdate(),interval 14 day);");
        
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
        
        $data_list = Db::query("select * from ai_draw_img_list where time > date_sub(curdate(),interval 0 day);");
        
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
        
        $data_list = Db::query("select * from ai_draw_img_list where  time > date_sub(curdate(),interval 14 day);");
        
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