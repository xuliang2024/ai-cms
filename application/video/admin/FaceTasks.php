<?php
// dall3绘画列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class FaceTasks extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_face_tasks')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_face_tasks', $map);

         
        
        return ZBuilder::make('table')
            ->setTableName('video/FaceTasksModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['user_id','user_id'],
                    ['task_id','task_id'],
                    ['source_img',  '人脸图片','img_url'],
                    ['target_img', '替换图片', 'callback', function($target_img) {
                        if (strpos($target_img, 'mp4') !== false) {
                            return '<div class="table-cell">
                            <div class="m-video" data-src="'.$target_img.'"><img style="width: 40px;" src="https://we-spa.oss-cn-shenzhen.aliyuncs.com/total_picture/1663473318830.png"></div></div>';
                        } else {
                            return '<div class="table-cell">
                            <div class="js-gallery"><img class="image" data-original="'.$target_img.'" src="'.$target_img.'"></div></div>';
                        }
                    }],

                    ['output_img', '替换图片', 'callback', function($output_img) {
                        if (strpos($output_img, 'mp4') !== false) {
                            return '<div class="table-cell">
                            <div class="m-video" data-src="'.$output_img.'"><img style="width: 40px;" src="https://we-spa.oss-cn-shenzhen.aliyuncs.com/total_picture/1663473318830.png"></div></div>';
                        } else {
                            return '<div class="table-cell">
                            <div class="js-gallery"><img class="image" data-original="'.$output_img.'" src="'.$output_img.'"></div></div>';
                        }
                    }],
                    ['task_type', '类型'],
                    ['face_model', 'face_model'],
                    ['face_enhancer', '人脸增强'],
                    ['frame_enhancer', '图片增强' ,'text.edit'],
                    ['face_detector_size', '尺寸'],
                    ['face_enhancer_model', '人脸增强模型'],
                    ['frame_enhancer_model', '图片增强模型'],
                    ['reference_face_position', '位置'],
                    ['reference_frame_number', '帧位置'],
                    ['status', '状态','text.edit'],
                    ['status', '状态','status','',[0=>'等待中',1=>'处理中',2=>'完成',3=>'失败']],
                    ['time','时间'],
                    
            ])
           
            ->setSearchArea([  
                ['text', 'user_id', '用户id'],
                ['text', 'task_id', 'task_id'],
                ['daterange', 'time', '时间'],   
                ['select', 'status', '状态', '', '', [0=>'等待中',1=>'处理中',2=>'完成',3=>'失败']],
                            
               
            ])
           
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


   



}
