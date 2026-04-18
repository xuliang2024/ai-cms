<?php
// 提取文案任务表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class VideoToTextTask extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_video_to_text_task')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_video_to_text_task', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/VideoToTextTaskModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['user_type', '用户类型','status','',[0=>'速推',1=>'网页']],
                    // ['image_url', '镜像封面','img_url'],
                    ['mp3_url', 'mp3链接','image_video'],
                    ['video_url', '视频链接','image_video'],
                    // ['douyin_url', '抖音链接','link','https://lbs.qq.com/getPoint/'],
                    ['douyin_url', '抖音链接','link','__douyin_url__', '_blank'],
                    // ['masked', 'longitude', '店铺位置经度', '<span class="text-danger">必选</span> <a target="_blank" href="https://lbs.qq.com/getPoint/"> 点击获取经纬度</a>', '999.999999'],


                    ['srt_url','字幕文件'],
                    ['status', '状态','text.edit'],
                    ['status', '状态','status','',[0=>'等待',1=>'处理中',2=>'成功',3=>'失败']],
                    ['fail_reson','fail_reson'],
                    // ['content', '识别内容'],
                    // ['wx_pay_key', 'wx_pay_key'],
                    ['content', '识别内容', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                    }],
                    ['time', 'time'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                 ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
                ['select', 'status', '状态', '', '', ['0'=>'等待','1'=>'处理中','2'=>'成功','3'=>'失败']],
                  
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    //  public function add() 
    //  {

    //     // 保存数据
    //     if ($this->request->isPost()) {
    //         // 表单数据
    //         $data = $this->request->post();
            
    //         $r = DB::connect('translate')->table('ts_video_to_text_task')->insert($data);
    //         if ($r) {
    //             $this->success('新增成功', 'index');
    //         } else {
    //             $this->error('新增失败');
    //         }
    //     }

                   
    //     // 显示添加页面
    //     return ZBuilder::make('form')

    //         ->addFormItems([
    //             ['text', 'appid', 'appid'],
    //             ['text', 'secret', 'secret'],
    //             ['text', 'access_token', 'access_token'],
    //             ['text', 'name', 'name'],
    //             ['text', 'wx_mchid', 'wx_mchid'],
    //             ['text', 'wx_pay_cert_id', 'wx_pay_cert_id'],
    //             ['text', 'wx_apiv3_key', 'wx_apiv3_key'],
    //             ['text', 'notify_url', 'notify_url'],
    //             ['text', 'dy_token', 'dy_token'],
    //             ['textarea', 'dy_salt', 'dy_salt'],
              
    //             ['textarea', 'wx_pay_key', 'wx_pay_key'],
    //         ])
    //         ->fetch();
    // }


    // public function edit($id = null)
    // {
    //     if ($id === null) $this->error('缺少参数');

    //     // 保存数据
    //     if ($this->request->isPost()) {
    //         // 表单数据
    //         $data = $this->request->post();

    //         $r = DB::connect('translate')->table('ts_video_to_text_task')->where('id',$id)->update($data);
    //         if ($r) {
    //             $this->success('编辑成功', 'index');
    //         } else {
    //             $this->error('编辑失败');
    //         }
    //     }


    //     $info = DB::connect('translate')->table('ts_video_to_text_task')->where('id',$id)->find();

    //     return ZBuilder::make('form')
    //          ->addFormItems([
    //             ['text', 'appid', 'appid'],
    //             ['text', 'secret', 'secret'],
    //             ['text', 'access_token', 'access_token'],
    //             ['text', 'name', 'name'],
    //             ['text', 'wx_mchid', 'wx_mchid'],
    //             ['text', 'wx_pay_cert_id', 'wx_pay_cert_id'],
    //             ['text', 'wx_apiv3_key', 'wx_apiv3_key'],
    //             ['text', 'notify_url', 'notify_url'],
    //             ['text', 'dy_token', 'dy_token'],
    //             ['textarea', 'dy_salt', 'dy_salt'],
              
    //             ['textarea', 'wx_pay_key', 'wx_pay_key'],
    //         ])
          
    //         ->setFormData($info)
    //         ->fetch();
    // }



}

