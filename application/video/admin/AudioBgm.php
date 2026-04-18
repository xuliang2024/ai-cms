<?php
// lora模型列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AudioBgm extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_audio_bgm_list')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_audio_bgm_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/AudioBgmModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['image_url', '封面','img_url'],
                ['title', '标题'],
                ['sub_title', '副标题'],
                ['mp3_len', '音频长度'],
                ['mp3_url', '音乐', 'image_video'],
                ['status', '状态','switch'],
                ['cat_name', '分类名'],
                ['tags', '标签'],
                ['use_cnt', '使用人数','text.edit'],
                ['sort', '排序','text.edit'],
                
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
              
            ])
            ->addTopButtons(['add','delete']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->addRightButtons(['edit']) 
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_audio_bgm_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
        ->addOssImage('image_url', '封面', '', '', '', '', '', ['size' => '50,50'])
        ->addOssVideo('mp3_url','音乐','')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'sub_title', '副标题'],
                
                ['text','cat_name', '分类名'],
                ['text','mp3_len', '音频长度'],
                ['text','tags', '标签'],
                
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

            $r = DB::connect('translate')->table('ts_audio_bgm_list')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_audio_bgm_list')->where('id',$id)->find();

        return ZBuilder::make('form')
        ->addOssImage('image_url', '封面', '', '', '', '', '', ['size' => '50,50'])
        ->addOssVideo('mp3_url','音乐','')
             ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'sub_title', '副标题'],
                
                ['text','cat_name', '分类名'],
                ['text','mp3_len', '音频长度'],
                ['text','tags', '标签'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



}
