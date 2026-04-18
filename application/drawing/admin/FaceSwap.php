<?php
//换脸视频制作
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class FaceSwap extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_face_swap')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_face_swap', $map);
        
        return ZBuilder::make('table')
            ->setTableName('face_swap') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title','标题'],
                ['original_url', '模版视频','image_video'],
                ['img_url','换脸头像','img_url'],
                ['end_url','合成视频','image_video'],   
                ['status', '状态','status','',[0=>'待合成',1=>'合成中',2=>'已合成']],

                // ['status', '状态','text.edit'],
                ['comment', '备注'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
        
            ->addRightButton('edit',[
                'title'=>'编辑',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('right_button', 200)
            ->setHeight('auto') 
            ->addTopButtons('add,delete')   
            ->fetch(); // 渲染页面

    }

public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_face_swap')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }  
        // 显示添加页面
        return ZBuilder::make('form')
             ->addOssImage('img_url', '换脸头像', '', '', '', '', '', ['size' => '50,50'])
              ->addOssVideo('original_url','模版视频','')
            ->addFormItems([
                // ['text','video_url','视频链接'],
                ['text', 'title', '标题'],
                ['text', 'comment', '备注'],
               
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

            $r = DB::table('ai_face_swap')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_face_swap')->where('id',$id)->find();

        return ZBuilder::make('form')
           
            
             ->addOssImage('img_url', '换脸头像', '', '', '', '', '', ['size' => '50,50'])
              ->addOssVideo('original_url','模版视频','')
            ->addFormItems([
                // ['text','video_url','视频链接'],
                ['text', 'title', '标题'],
                ['radio', 'status', '状态','',[0=>'待合成',1=>'合成中',2=>'已合成']],
                ['text', 'comment', '备注'],
               
            ])
           
            ->setFormData($info)
            ->fetch();
    }


  
}