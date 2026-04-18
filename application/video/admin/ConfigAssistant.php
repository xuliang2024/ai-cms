<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ConfigAssistant extends Admin {

    public function index() 
    {
        $order = $this->getOrder('sort desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_assistant_list')->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_assistant_list', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit','delete'];
        return ZBuilder::make('table')
            ->setTableName('video/AssistantModel',2) // 设置数据表名
            ->addColumns([
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['icon_url', '图标', 'img_url'],
                    ['prompt', '提示', 'callback', function($value) {
                        return mb_strimwidth($value, 0, 30, '...');
                    }],
                    ['description', '描述'],
                    ['status', '状态', 'switch'],
                    ['sort', '排序', 'text.edit'],
                    ['time', '时间戳'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addRightButtons($contro_right_btn)
            ->setSearchArea([
                ['text', 'title', '标题'],
                ['text', 'status', '状态'],
            ])
            ->addOrder('sort')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_assistant_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['ossimage', 'icon_url', '图片'],
                ['textarea', 'prompt', '提示'],
                ['textarea', 'description', '描述'],
                ['select', 'status', '状态', '', [0 => '启用', 1 => '禁用'], 0],
                ['number', 'sort', '排序', '', 0],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = DB::connect('translate')->table('ts_assistant_list')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_assistant_list')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['ossimage', 'icon_url', '图片'],
                ['textarea', 'prompt', '提示'],
                ['textarea', 'description', '描述'],
                ['select', 'status', '状态', '', [0 => '启用', 1 => '禁用']],
                ['number', 'sort', '排序'],
            ])
            ->setFormData($info)
            ->fetch();
    }
}