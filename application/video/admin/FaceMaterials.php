<?php
// 素材表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class FaceMaterials extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_face_materials')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_face_materials', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/FaceMaterialsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['title', 'title'],
                    // ['url', 'url'],
                    // ['url', 'url','link', 'https://sz-video-st.oss-cn-shenzhen.aliyuncs.com/1702900906264-668e86ec0a71.png', '_blank', 'pop'],
                    ['url',  '图片','img_url'], 
                    ['video_url', '视频','image_video'],
                    ['img_type', '类型','status','',[0=>'图片',1=>'视频']],
                    ['sort', '排序','text.edit'],
                    ['status', '状态','switch'],
                    ['cat','分类'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'status', 'status'],
                
                
               
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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
            
            $r = DB::connect('translate')->table('ts_face_materials')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'title', 'title'],
                // ['text', 'url', 'url'],
                ['select', 'img_type', '类型','',[0=>'图片',1=>'视频'],0],
                ['text', 'sort', 'sort'],
                ['select', 'status', '状态','',[0=>'下架',1=>'上架'],0],
                ['text', 'cat', '分类'],
                ['ossimage', 'url', '图片链接'],
                ['ossvideo', 'video_url', '视频链接'],
            ])
            // ->addOssVideo('video_url','视频链接','')
            // ->addOssImage('url','图片链接','')
            ->setTrigger('img_type', '0', 'url')
            ->setTrigger('img_type', '1', 'video_url')
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_face_materials')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_face_materials')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'title', 'title'],
                // ['text', 'url', 'url'],
                ['select', 'img_type', '类型','',[0=>'图片',1=>'视频'],0],
                ['text', 'sort', 'sort'],
                ['select', 'status', '状态','',[0=>'下架',1=>'上架'],0],
                ['text', 'cat', '分类'],
            ])
            
            ->addOssImage('url','图片链接','')
            ->addOssVideo('video_url','视频链接','')
            // ->setTrigger('img_type', '0', 'url')
            // ->setTrigger('img_type', '1', 'video_url')
            ->setFormData($info)
            ->fetch();
    }



}

