<?php
//底模型列表
namespace app\drawing\admin;
    
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
    
class VideoTask extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_video_tasks')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_video_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video_tasks') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id','任务id'],
                ['name','名称'],
                ['video_url', '视频链接','image_video'],
                ['finish_video_url', '视频','image_video'],
                ['status', '状态',['0' => '等待', '1' => '下发','2'=>'切图完成','3'=>'遮罩完成','4'=>'绘画','5'=>'完成']],
                ['status', '状态','text.edit'],
                ['mask', '状态',['0' => '抠图', '1' => '跳过抠图']],
                ['clip', '状态',['0' => '跳过识文', '1' => '识文']],
                ['tmp_type', '临时状态','text.edit'],
                ['seed', '种子'],
                ['time','时间'],
                ['prompt', '提示词'],
                ['negative_prompt', '负面'],
                ['cfg_scale', 'cfg提词'],
                ['denoising_strength', '重绘幅度'],
                ['steps', '步数'],
                ['sampler_name', '采样方法'],
                ['frame_rate', '帧率'],
                // ['total_frames', '总图片'],
                // ['video_width', '宽'],
                // ['video_height', '高'],
                // ['background_music_url', '背景音乐'],
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->addTopButton('add',['title'=>'新增']) // 批量添加顶部按钮
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
             ->addRightButton('edit',[
                'title'=>'修改',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('prompt,negative_prompt', 400)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


 public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_video_tasks')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
         ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                // ['text','video_url','视频链接'],
                ['text', 'name', '名字'],
                ['text', 'prompt', '触发词','','fashi-g <lora:fashionGirl_v52:1>'],     
                ['text', 'negative_prompt', '负面','','EasyNegative,ng_deepnegative_v1_75t'],     
                ['text', 'cfg_scale', 'cfg_scale','','7'],     
                ['text', 'seed', '种子','','1'],     
                ['text', 'denoising_strength', '重绘幅度','','0.6'],         
                ['select', 'mask','状态','',['0' => '抠图', '1' => '跳过抠图'],1],
                ['select', 'clip','状态','',['0' => '跳过识文', '1' => '识文'],0],
                ['text', 'steps', '步数','','20'],     
                ['text', 'sampler_name', '采样方法','','Euler a'],     
                ['text', 'frame_rate', '帧率','','10'],     
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

            $r = DB::table('ai_video_tasks')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_video_tasks')->where('id',$id)->find();

        return ZBuilder::make('form')
            ->addOssVideo('video_url','视频链接','')
             ->addFormItems([
                ['text', 'name', '名字'],
                ['textarea', 'prompt', '触发词'],     
                ['text', 'seed', '种子','','1'],     
                ['text', 'denoising_strength', '重绘幅度','','0.6'],         

                ['textarea', 'model_name', '模型名字'],     
                ['textarea', 'frame_rate', '帧率'],    
                ['select', 'status','状态','',['0' => '默认', '1' => '下发'],0], 

            ])
          
           
            ->setFormData($info)
            ->fetch();
    }



  
}