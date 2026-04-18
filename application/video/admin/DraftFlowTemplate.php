<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DraftFlowTemplateModel;

class DraftFlowTemplate extends Admin {

    public function index() 
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        
        $data_list = DraftFlowTemplateModel::where($map)
        ->order($order)
        ->paginate();

        cookie('ts_draft_flow_template', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit', 'delete'];
        return ZBuilder::make('table')
            ->setTableName('video/DraftFlowTemplateModel', 2) // 设置数据表名
            ->setPrimaryKey('id') // 设置主键
            ->addColumns([
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['workflow_id', '工作流ID'],
                    ['workflow_name', '工作流名称','text.edit'],
                    ['video_url', '视频URL', 'callback', function($value) {
                        return $value ? '<a href="'.$value.'" target="_blank" class="label label-info">查看视频</a>' : '-';
                    }],
                    ['img_url', '图片URL', 'callback', function($value) {
                        return $value ? '<a href="'.$value.'" target="_blank" class="label label-success">查看图片</a>' : '-';
                    }],
                    ['is_public', '是否公开' ,'switch'],
                    ['created_at', '创建时间'],
                    ['updated_at', '更新时间'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addRightButtons($contro_right_btn)
            ->setSearchArea([
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'workflow_name', '工作流名称'],
                ['text', 'user_id', '用户ID'],
                ['select', 'is_public', '是否公开', '', ['0' => '否', '1' => '是']],
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
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_public'] = isset($data['is_public']) ? 1 : 0;
            
            // 检查工作流ID是否已存在
            $exists = DB::connect('translate')->table('ts_draft_flow_template')
                ->where('workflow_id', $data['workflow_id'])
                ->where('user_id', $data['user_id'])
                ->find();
            if ($exists) {
                $this->error('该用户的工作流ID已存在，请更换');
            }
            
            $r = DraftFlowTemplateModel::insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '必填', '', 'required'],
                ['text', 'workflow_id', '工作流ID', '必填，最长100个字符', '', 'required maxlength="100"'],
                ['text', 'workflow_name', '工作流名称', '必填，最长200个字符', '', 'required maxlength="200"'],
                ['text', 'video_url', '视频URL', '最长500个字符', '', 'maxlength="500"'],
                ['text', 'img_url', '图片URL', '最长500个字符', '', 'maxlength="500"'],
                ['textarea', 'request_token', '请求Token', '最长500个字符', '', 'maxlength="500"'],
                ['checkbox', 'is_public', '是否公开', '勾选表示公开可见'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_public'] = isset($data['is_public']) ? 1 : 0;
            
            // 检查工作流ID是否已存在（排除当前记录）
            $exists = DraftFlowTemplateModel::where('workflow_id', $data['workflow_id'])
                ->where('workflow_id', $data['workflow_id'])
                ->where('user_id', $data['user_id'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                $this->error('该用户的工作流ID已存在，请更换');
            }
            
            $r = DraftFlowTemplateModel::where('id', $id)->update($data);
            if ($r !== false) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DraftFlowTemplateModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '必填'],
                ['text', 'workflow_id', '工作流ID', '必填，最长100个字符', '', 'required maxlength="100"'],
                ['text', 'workflow_name', '工作流名称', '必填，最长200个字符', '', 'required maxlength="200"'],
                ['text', 'video_url', '视频URL', '最长500个字符', '', 'maxlength="500"'],
                ['text', 'img_url', '图片URL', '最长500个字符', '', 'maxlength="500"'],
                ['textarea', 'request_token', '请求Token', '最长500个字符', '', 'maxlength="500"'],
                ['checkbox', 'is_public', '是否公开', '勾选表示公开可见'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除操作
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('缺少参数');
        
        $ids = is_array($ids) ? $ids : [$ids];
        
        $r = DraftFlowTemplateModel::where('id', 'in', $ids)->delete();
        if ($r) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 