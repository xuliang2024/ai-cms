<?php
// 视频处理列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class VideoInfos extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_video_infos')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_video_infos', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/VideoInfosModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['title','标题'],
                    // ['url',  '图片','img_url'], 
                    ['video_url', '视频','image_video'],
                    ['bgm_url','背景','image_video'],
                    ['persion_url','人声','image_video'],
                    ['draw_video_url','重置后视频','image_video'],
                    ['status', '状态','text.edit'],
                    ['split_status','分割状态','text.edit'],
                    ['video_status','视频状态','text.edit'],
                    
                    

                    ['tts_name','角色人'],
                    ['tts_speed', '配音语速','text.edit'],
                    ['sd_model', 'sd模型'],
                    // ['wx_pay_key', 'wx_pay_key', 'callback', function($source_text) {
                    // // 限制字符串长度为50个字符
                    // return mb_strimwidth($source_text, 0, 50, '...');
                    // }],
                    ['width', '图片宽度'],
                    ['height', '图片高度'],
                    ['denoising_strength','重置幅度(0.5)'],
                    ['img_pos','图片位置'],
                    

                    ['prompt','默认正向'],
                    ['negative_prompt', '默认反向', 'callback', function($source_text) {
                        // 限制字符串长度为50个字符
                        return mb_strimwidth($source_text, 0, 50, '...');
                    }],

                    ['logs', '日志', 'callback', function($source_text) {
                        // 限制字符串长度为50个字符
                        return mb_strimwidth($source_text, 0, 50, '...');
                    }],

                    ['content','提取文案'],
                    
                    ['is_change_text','是否改写台词'],
                    // ['status', '状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    
                    ['time','时间'],
                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['text', 'user_id', '用户iD'],
                ['text', 'status', '状态'],
                ['text', 'split_status', '分割状态'],
                ['text', 'mp3_status', '音频状态'],
                ['text', 'image_status', '图片状态'],
                ['text', 'tts_status', '配音状态'],
                ['text', 'video_status', '视频状态'],
                
                
               
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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
            
            $r = DB::connect('translate')->table('ts_video_infos')->insert($data);
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

            $r = DB::connect('translate')->table('ts_video_infos')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_video_infos')->where('id',$id)->find();

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

