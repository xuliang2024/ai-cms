<?php
// 小程序风格画设置表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class StylePaintingSettings extends Admin {
    

    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_style_painting_settings')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_style_painting_settings', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/StylePaintingSettingsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['img_url','封面','img_url'],
                    ['name', '风格名字'],
                    ['sort', '排序','text.edit'],
                    ['status', '状态','text.edit'],
                    // ['style_id', '上级分类ID'],
                    // ['style_name', '上级分类名字'],
                    ['scene_gpt_ai_id', '场景AI'],
                    ['prompt_gpt_ai_id', '正向词AI'],
                    ['sd_model_checkpoint', 'SD模型检查点'],
                    ['sd_vae', 'SD_vae'],
                    ['prompt', '正向提示', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                    }],
                    ['negative_prompt', '负向提示', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                    }],
                    // ['prompt', '正向提示'],
                    // ['negative_prompt', '负向提示'],
                    ['sampler_name', '采样器名称'],
                    ['lora_bg', 'LoRA模型'],
                    ['lora_boy', 'lora_boy'],
                    ['lora_girl', 'lora_girl'],
                    ['steps', '采样步数'],
                    ['cfg_scale', 'cfg比例'],

                    ['time','创建时间'],
                    ['right_button', '操作', 'btn']  
                    
            ])
            ->setSearchArea([  
                ['text', 'status', '状态'],
                
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
            
            $r = DB::connect('translate')->table('ts_style_painting_settings')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
        ->addOssImage('img_url', '封面', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                    ['text', 'name', '名字'],
                    ['text', 'scene_gpt_ai_id', '场景AI'],
                    ['text', 'prompt_gpt_ai_id', '正向词AI'],
                    ['text', 'sd_model_checkpoint', 'Stable Diffusion 模型检查点'],
                    ['text', 'sd_vae', 'Stable Diffusion VAE'],
                    ['textarea', 'prompt', '正向提示'],
                    ['textarea', 'negative_prompt', '负向提示'],
                    ['text', 'sampler_name', '采样器名称'],
                    ['text', 'lora_bg', 'LoRA 模型'],
                    ['text', 'lora_boy', 'lora_boy 模型'],
                    ['text', 'lora_girl', 'lora_girl 模型'],
                    ['text', 'steps', '采样步数'],
                    ['text', 'status', '状态'],
                    ['text', 'cfg_scale', 'CFG 比例'],

               
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

            $r = DB::connect('translate')->table('ts_style_painting_settings')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_style_painting_settings')->where('id',$id)->find();

        return ZBuilder::make('form')
                ->addOssImage('img_url', '封面', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                    ['text', 'name', '名字'],
                    ['text', 'scene_gpt_ai_id', '场景AI'],
                    ['text', 'prompt_gpt_ai_id', '正向词AI'],
                    ['text', 'sd_model_checkpoint', 'Stable Diffusion 模型检查点'],
                    ['text', 'sd_vae', 'Stable Diffusion VAE'],
                    ['textarea', 'prompt', '正向提示'],
                    ['textarea', 'negative_prompt', '负向提示'],
                    ['text', 'sampler_name', '采样器名称'],
                    ['text', 'lora_bg', 'LoRA 模型'],
                    ['text', 'lora_boy', 'lora_boy 模型'],
                    ['text', 'lora_girl', 'lora_girl 模型'],
                    ['text', 'steps', '采样步数'],
                    ['text', 'status', '状态'],
                    ['text', 'cfg_scale', 'CFG 比例'],
               
            ])
        
            ->setFormData($info)
            ->fetch();
    }



}

