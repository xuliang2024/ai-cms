<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DrawImgList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_draw_img_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_draw_img_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('draw_img_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id','用户ID'],
                ['task_id', '任务ID'],
                ['img', '图片','img_url'],
                ['check_type', '检查状态',['0' => '默认', '1' => '检测中','2'=>'检测完成']],
                ['status', '图片状态',['0' => '通过', '1' => '绘画中','3'=>'非法','4'=>'用户删除']],
                ['hot', '推荐','switch'],
                ['like_cnt', '点赞数','text.edit'],
                ['unlock_status', '解锁状态',['0' => '未解锁', '1' => '已解锁']],
                ['time', '创建时间'],
                
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([
                ['daterange', 'time', '时间'],
                ['text', 'user_id', '用户ID'],
                ['text', 'task_id', '任务ID'],
                ['select', 'hot', '是否推荐', '', '', ['0' => '默认', '1' => '上推荐']],
                ['select', 'unlock_status', '是否解锁', '', '', ['0' => '未解锁', '1' => '已解锁']],
            
            ])


            // ->hideCheckbox()
             ->setColumnWidth('right_button', 120)
             ->addRightButton('info',[
                'title'=>'查看任务',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                'href'=>'/admin.php/drawing/draw_task_list/index.html?_s=user_id=|model_id=|style_id=|time=|id=__task_id__&_o=user_id=eq|model_id=eq|style_id=eq|time=between%20time|id=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('back')
            ->addTopButton('delete')
            
            ->addRightButton('edit',[
                'title'=>'编辑作品',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

            ->fetch(); // 渲染页面

    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_draw_img_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_draw_img_list')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['select', 'status', '状态','',['0' => '通过','3'=>'非法'],3],                
            ])
          
            ->setFormData($info)
            ->fetch();
    }

 

  
}