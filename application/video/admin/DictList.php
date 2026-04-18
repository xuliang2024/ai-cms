<?php
//乐推系统配置
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DictList extends Admin {
	
    public function index() 
    {   
         
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

             // 检查 model_sec_config 是否是 JSON
             if (!$this->isJson($data["model_sec_config"])) {
                $this->error('提交失败：模型每秒价格 需要 JSON 格式');
            }

             // 检查 model_sec_config 是否是 JSON
             if (!$this->isJson($data["model_one_config"])) {
                $this->error('提交失败：模型固定价格 需要 JSON 格式');
            }


           
           $sql = 'update ts_dict_list set  authorization = "'.$data["authorization"].'"  ,  max_draw_apparatus = "'.$data["max_draw_apparatus"].'" ,  min_draw_apparatus = "'.$data["min_draw_apparatus"].'" , gpu_type = "'.$data["gpu_type"].'" , type_cephalon = "'.$data["type_cephalon"].'" , median_cnt = "'.$data["median_cnt"].'" , median_video_cnt = "'.$data["median_video_cnt"].'"  , gpt_key_vector = "'.$data["gpt_key_vector"].'"  , ios_audit_status = "'.$data["ios_audit_status"].
           '"  , model_sec_config = "'.addslashes($data["model_sec_config"]).
           '"  , model_one_config = "'.addslashes($data["model_one_config"]).
           '"  where id = 30;';
           


           $r =  DB::connect('translate')->query($sql);

            $this->success('提交成功', 'index');
            
        }
        
        $info = DB::connect('translate')->table('ts_dict_list')->find();
       
        return ZBuilder::make('form')

            
            // ->addOssImage('promotion_img', '推广链接图片', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                //['textarea', 'callme_hint', '客服微信文案描述'],            
                ['text', 'authorization', '端脑云的authorization值'],          
                ['text', 'median_cnt', '限定绘画增加或者减少机器的临界值'],          
                ['text', 'median_video_cnt', '限定视频合成增加或者减少机器的临界值'],          
                ['text', 'max_draw_apparatus', '最大绘画机器的数量'],          
                ['text', 'min_draw_apparatus', '最小绘画机器的数量'],          
                // ['text', 'expect_draw_expect', '期望的绘画机器数量'],          
                ['text', 'gpu_type', '默认开启的gpt机器类型'],          
                ['text', 'type_cephalon', '默认开启的镜像名称'],          

                ['text', 'gpt_key_vector', 'gpt_key矢量使用'],    
                ['textarea', 'model_sec_config', '模型每秒价格'],    
                ['textarea', 'model_one_config', '模型固定价格'],          

                ['text', 'gpt_key_vector', 'gpt_key矢量使用'],          
                  
                ['radio', 'ios_audit_status', 'ios审核启用', '', ['否', '是']]       

                      

            ])


            
            ->setFormData($info)
            ->fetch();


    }

   
     // 检查字符串是否是 JSON 格式
     private function isJson($string) {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

  
}