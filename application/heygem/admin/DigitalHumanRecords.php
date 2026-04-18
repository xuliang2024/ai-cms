<?php
// 数字人记录管理
namespace app\heygem\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\heygem\model\DigitalHumanRecords as DigitalHumanRecordsModel;

class DigitalHumanRecords extends Admin {
    
    /**
     * 数字人记录列表
     */
    public function index() 
    {
        $map = $this->getMap();
        // 查询数据
        $data_list = DigitalHumanRecordsModel::where($map)
            ->order('time desc')
            ->paginate();

        cookie('digital_human_records', $map);
        
        return ZBuilder::make('table')
            ->setTableName('heygem/DigitalHumanRecords',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['title', '标题'],
                ['digital_human_name', '数字人名称'],
                ['video_url', '视频链接', 'image_video'],
                ['audio_url', '音频链接', 'image_video'],
                ['sort', '排序', 'text.edit'],
                ['status', '状态'],
                ['is_public', '是否公开'],
                ['time', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'title', '标题'],
                ['text', 'user_id', '用户ID'],
                ['select', 'status', '状态', '', [
                    0 => '待处理',
                    1 => '处理中',
                    2 => '完成',
                    3 => '失败'
                ]],
                ['select', 'is_public', '是否公开', '', [
                    1 => '是',
                    0 => '否'
                ]]
            ])
            ->addTopButton('delete') // 添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    /**
     * 添加数字人记录
     */
    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $result = DigitalHumanRecordsModel::create($data);
            if ($result) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '请输入用户ID'],
                ['text', 'title', '标题', '请输入标题'],
                ['text', 'digital_human_name', '数字人名称', '请输入数字人名称'],
                ['text', 'video_url', '视频链接', '请输入视频链接'],
                ['text', 'audio_url', '音频链接', '请输入音频链接'],
                ['text', 'clone_audio_url', '克隆音频链接', '请输入克隆后的音频链接'],
                ['textarea', 'audio_content', '音频文本内容', '请输入音频文本内容'],
                ['radio', 'is_public', '是否公开', '', [
                    1 => '是',
                    0 => '否'
                ], 0],
                ['number', 'sort', '排序', '数值越大越靠前', 0],
                ['textarea', 'remarks', '备注信息', '请输入备注信息']
            ])
            ->fetch();
    }

    
} 