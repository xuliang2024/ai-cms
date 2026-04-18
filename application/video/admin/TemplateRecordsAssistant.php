<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TemplateRecordsAssistant extends Admin {

    public function index() 
    {
        $order = $this->getOrder('sort_order desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_template_records')->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_template_records', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit','delete'];
        return ZBuilder::make('table')
            ->setTableName('video/TemplateRecordsModel',2) // 设置数据表名
            ->addColumns([
                    ['id', 'ID'],
                    ['template_id', '模板ID'],
                    ['workflow_id', '工作流ID'],
                    ['sort_order', '排序', 'text.edit'],
                    ['gif_url', 'GIF URL', 'img_url'],
                    ['case_video_url', '案例视频URL', 'url'],
                    ['title', '标题'],
                    ['cover_image_url', '封面图片URL', 'img_url'],
                    ['remarks', '备注', 'callback', function($value) {
                        return mb_strimwidth($value, 0, 30, '...');
                    }],
                    ['workflow_link', '工作流链接', 'url'],
                    ['status', '状态', 'switch'],
                    ['time', '时间戳'],
                    ['user_id', '用户ID'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addRightButtons($contro_right_btn)
            ->setSearchArea([
                ['text', 'title', '标题'],
                ['text', 'status', '状态'],
            ])
            ->addOrder('sort_order')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    private function generateUuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['template_id'] = $this->generateUuid();
            
            $r = DB::connect('translate')->table('ts_template_records')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'sort_order', '排序', '', 0],
                ['ossimage', 'gif_url', 'GIF URL'],
                ['ossvideo', 'case_video_url', '案例视频URL'],
                ['text', 'title', '标题'],
                ['ossimage', 'cover_image_url', '封面图片URL'],
                ['textarea', 'remarks', '备注'],
                ['select', 'status', '状态', '', [0 => '下架', 1 => '上架'], 1],
                ['number', 'user_id', '用户ID'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = DB::connect('translate')->table('ts_template_records')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_template_records')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'sort_order', '排序'],
                ['ossimage', 'gif_url', 'GIF URL'],
                ['ossvideo', 'case_video_url', '案例视频URL'],
                ['text', 'title', '标题'],
                ['ossimage', 'cover_image_url', '封面图片URL'],
                ['textarea', 'remarks', '备注'],
                ['select', 'status', '状态', '', [0 => '下架', 1 => '上架']],
                ['number', 'user_id', '用户ID'],
            ])
            ->setFormData($info)
            ->fetch();
    }
}