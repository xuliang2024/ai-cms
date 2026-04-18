<?php
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

use think\Db;

class VideoProgress extends Admin
{
    public function index()
    {



        // $this->assign('waiting', Db::table('ai_draw_task_list')->where("status = 0")->count());
        // $this->assign('drawing', Db::table('ai_draw_task_list')->where("status = 1")->count());
        $this->assign('ai_sdyun_cnt', Db::table('ai_sdyun_url')->where("status = 1")->whereOr('deploy_status', '=', 2)->count());

        // $ai_sdyun = Db::table('ai_sdyun_url')->where('id = 1276')->find();
        $ai_sdyun = Db::table('ai_sdyun_url')->where('status = 1')->whereOr('deploy_status', '=', 2)->field('id,title,sd_url')->select();
        // $ai_sdyun_url=$ai_sdyun['sd_url'];
        // $ai_sdyun_url = $ai_sdyun_url.'sdapi/v1/progress?skip_current_image=false';
        // print($ai_sdyun_url);

        $ai_sdyun_json = json_encode($ai_sdyun);


        // 假设 $ai_sdyun 是数据库查询的结果，它是一个包含多条记录的数组
        // $aiSdyunJsonArray = [];
        // foreach ($ai_sdyun as $record) {
        //     // 将每条记录的相关数据转换为JSON格式，并存储到一个新数组中
        //     $aiSdyunJsonArray[] = json_encode($record);
        // }


        // $this->assign('ai_sdyun', $aiSdyunJsonArray);
        $this->assign('ai_sdyun', $ai_sdyun_json);
        return $this->fetch(); // 渲染模板

    }

    function getVideoData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            return null;
        }

        curl_close($ch);
        $data = json_decode($response, true);
        return $data;

    }

    function getVideoPostData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
        // Set the request method to POST
        curl_setopt($ch, CURLOPT_POST, 1);
    
        // Add the POST data
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            return null;
        }
    
        curl_close($ch);
        $data = json_decode($response, true);
        return $data;
    }
    


    
   
}