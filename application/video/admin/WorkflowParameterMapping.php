<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class WorkflowParameterMapping extends Admin {

    public function index() 
    {
        $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_workflow_parameter_mapping')->where($map)
            ->order($order)
            ->paginate();

        cookie('ts_workflow_parameter_mapping', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/WorkflowParameterMappingModel', 2)
            ->addColumns([
                ['id', 'ID'],
                ['from_workflow_id', '源工作流ID'],
                ['target_workflow_id', '目标工作流ID'],
                ['template_id', '模板ID'],
                ['mapping', '参数映射', 'callback', function($value) {
                    return mb_strimwidth($value, 0, 30, '...');
                }],
                ['time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons(['add'])
            ->addRightButtons(['edit', 'delete'])
            ->setSearchArea([
                ['text', 'from_workflow_id', '源工作流ID'],
                ['text', 'target_workflow_id', '目标工作流ID'],
                ['text', 'template_id', '模板ID'],
            ])
            ->addOrder('time')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['time'] = date('Y-m-d H:i:s');
            
            $r = DB::connect('translate')->table('ts_workflow_parameter_mapping')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'from_workflow_id', '源工作流ID'],
                ['text', 'target_workflow_id', '目标工作流ID'],
                ['text', 'template_id', '模板ID'],
                ['textarea', 'mapping', '参数映射(JSON格式)'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['time'] = date('Y-m-d H:i:s');
            
            $r = DB::connect('translate')->table('ts_workflow_parameter_mapping')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_workflow_parameter_mapping')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'from_workflow_id', '源工作流ID'],
                ['text', 'target_workflow_id', '目标工作流ID'],
                ['text', 'template_id', '模板ID'],
                ['textarea', 'mapping', '参数映射(JSON格式)'],
            ])
            ->setFormData($info)
            ->fetch();
    }
}