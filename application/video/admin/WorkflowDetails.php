<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class WorkflowDetails extends Admin {

    public function index() 
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_workflow_details')->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_workflow_details', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit','delete'];
        return ZBuilder::make('table')
            ->setTableName('video/WorkflowDetailsModel',2) // 设置数据表名
            ->addColumns([
                    ['id', 'ID'],
                    ['workflow_id', '工作流ID'],
                    ['template_id', '模板ID'],
                    ['input_params', '输入参数', 'callback', function($value) {
                        return mb_strimwidth($value, 0, 40, '...');
                    }],
                    ['output_params', '输出参数', 'callback', function($value) {
                        return mb_strimwidth($value, 0, 40, '...');
                    }],
                    ['next_workflow_id', '下一个工作流ID'],
                    ['status', '状态', 'switch'],
                    ['title', '标题'],
                    ['workflow_link', '工作流链接'],
                    ['time', '时间戳'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addRightButtons($contro_right_btn)
            ->setSearchArea([
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'title', '标题'],
            ])
            ->addOrder('id')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_workflow_details')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'template_id', '模板ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['textarea', 'input_params', '输入参数'],
                ['textarea', 'output_params', '输出参数'],
                ['text', 'next_workflow_id', '下一个工作流ID'],
                ['select', 'status', '状态', '', [0 => '启动', 1 => '下架'], 0],
                ['text', 'title', '标题'],
                ['text', 'workflow_link', '工作流链接'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = DB::connect('translate')->table('ts_workflow_details')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_workflow_details')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'template_id', '模板ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['textarea', 'input_params', '输入参数'],
                ['textarea', 'output_params', '输出参数'],
                ['text', 'next_workflow_id', '下一个工作流ID'],
                ['select', 'status', '状态', '', [0 => '启动', 1 => '下架']],
                ['text', 'title', '标题'],
                ['text', 'workflow_link', '工作流链接'],
            ])
            ->setFormData($info)
            ->fetch();
    }
}