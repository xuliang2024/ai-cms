<?php
// 视频明细列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class VideoDetailInfos extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_video_detail_infos')->where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_video_detail_infos', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/VideoDetailInfosModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['video_id', '关联视频ID'],
        
                    // ['url',  '图片','img_url'], 
                    ['video_url', '拆分视频','image_video'],
                    ['mp3_url', '提取音频','image_video'],
                    // ['text','识别的台词'],
                    ['text', '识别的台词', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                    }],
                    ['new_text', '改写的台词'],
                    ['new_mp3_url', '新生成音频','image_video'],
                    ['image_url',  '提取的图片','img_url'], 

                    

                    ['image_text', '图片描述'],
                    ['new_image_url',  '重绘制图片','img_url'], 
                    ['new_video_url',  '重置的视频','image_video'], 
                    ['duration', '音频时长(微妙)'],
                    ['width','图片宽度'],
                    ['height','图片高度'],
                    ['img_pos','图片位置'],
                    ['motion','动画方向'],
                    // ['img_status', '图片状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    // ['mp3_status', '音频状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    // ['text_status', 'text状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    // ['prompt_status', 'ptompt状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    // ['video_status', '视频状态','status','',[0=>'等待',1=>'处理中',2=>'完成',3=>'失败',4=>'超时']],
                    ['img_status', '提取图片','text.edit'],
                    ['draw_status', '重绘图片','text.edit'],
                    ['mp3_status', '音频状态','text.edit'],
                    ['text_status', 'text状态','text.edit'],
                    ['prompt_status', 'ptompt状态','text.edit'],
                    ['video_status', '视频状态','text.edit'],


                    ['sd_model','模型'],
                    ['denoising_strength','重置幅度(0.5)'],
                    ['time','时间'],
                    
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['text', 'video_id', '视频ID'],
                ['text', 'img_status', '提取图片'],
                ['text', 'draw_status', '重绘图片'],
                ['text', 'mp3_status', '音频状态'],
                ['text', 'text_status', 'text状态'],
                ['text', 'prompt_status', 'ptompt状态'],
                ['text', 'video_status', '视频状态'],
                
                 
               
            ])
            // ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
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
            
            $r = DB::connect('translate')->table('ts_video_detail_infos')->insert($data);
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

            $r = DB::connect('translate')->table('ts_video_detail_infos')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_video_detail_infos')->where('id',$id)->find();

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

