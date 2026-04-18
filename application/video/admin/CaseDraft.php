<?php
// 案例草稿管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CaseDraftModel;

class CaseDraft extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = CaseDraftModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_case_draft', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CaseDraftModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '名称', 'text.edit'],
                ['draft_url', '草稿URL', 'text.edit'],
                ['status', '状态', 'select', [0 => '禁用', 1 => '启用']],
                ['case_type', '案例类型', 'text.edit'],
                ['desc', '描述', 'text.edit'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '名称'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']],
                ['text', 'case_type', '案例类型']
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->fetch(); // 渲染页面
    }

    public function add() 
    {
        // 定义案例类型选项
        $case_types = [
            'article' => '文章',
            'video' => '视频',
            'audio' => '音频',
            'image' => '图片',
            'other' => '其他'
        ];
        
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['time'] = date('Y-m-d H:i:s');
            
            $r = CaseDraftModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名称', '请输入案例名称', '', 'required'],
                ['text', 'draft_url', '草稿URL', '请输入草稿URL', '', 'required'],
                ['select', 'case_type', '案例类型', '请选择案例类型', $case_types, 'article', 'required'],
                ['textarea', 'desc', '描述', '请输入案例描述'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用'], 1]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 定义案例类型选项
        $case_types = [
            'article' => '文章',
            'video' => '视频',
            'audio' => '音频',
            'image' => '图片',
            'other' => '其他'
        ];
        
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = CaseDraftModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CaseDraftModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '名称', '请输入案例名称', '', 'required'],
                ['text', 'draft_url', '草稿URL', '请输入草稿URL', '', 'required'],
                ['select', 'case_type', '案例类型', '请选择案例类型', $case_types, '', 'required'],
                ['textarea', 'desc', '描述', '请输入案例描述'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CaseDraftModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 