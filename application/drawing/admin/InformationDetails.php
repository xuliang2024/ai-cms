<?php
//咨讯详情
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class InformationDetails extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_information_details')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_information_details', $map);
                
        return ZBuilder::make('table')
            ->setTableName('information_details') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['sub_title', '副标题'],
                    ['img', '封面','img_url'],
                    ['type', '类型',['0' => '日常咨询', '1' => '活动详情','2'=>'新手教程','3'=>'纯图']],
                    ['jump_url','跳转路径'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],               
                    ['time', '时间'],
                    ['right_button', '操作', 'btn'],
            ])
            ->hideCheckbox()
            ->addTopButton('add',['title'=>'新增'])
            ->addRightButtons(['edit','delete']) // 批量添加右侧按钮//
           
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
            $data["p_user_id"] = is_signin();
            $r = DB::table('ai_information_details')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
 
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                    ['text','title', '标题'],
                    ['textarea', 'sub_title', '副标题'],
                    ['ckeditor', 'content', '页面内容'],    
                    ['select', 'type', '资讯类型','',['0' => '日常咨询', '1' => '活动详情','2'=>'新手教程','3'=>'纯图'],0],   
                    ['text', 'jump_url', '跳转路径'],    
                    ['text', 'sort', '排序'],
                    ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线'],1],                 

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
           
            $r = DB::table('ai_information_details')->where('id',$id)->update($data);
            // print($r);
            // die();
            if ($r) {
                
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_information_details')->where('id',$id)->find();

        return ZBuilder::make('form')
                       ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                    ['text','title', '标题'],
                    ['textarea', 'sub_title', '副标题'],
                    ['ckeditor', 'content', '页面内容'],    
                    ['select', 'type', '资讯类型','',['0' => '日常咨询', '1' => '活动详情','2'=>'新手教程','3'=>'纯图'],0],   
                    ['text', 'jump_url', '跳转路径'],    
                    ['text', 'sort', '排序'],
                    ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线'],1],                 

            ])
            ->setFormData($info)
            ->fetch();
    }
  
}