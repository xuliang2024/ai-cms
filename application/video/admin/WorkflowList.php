<?php
// 工作流列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\WorkflowListModel;

class WorkflowList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = WorkflowListModel::where($map)
        ->order('create_time desc')
        ->paginate();

        cookie('ts_workflow_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/WorkflowListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['workflow_id', '工作流ID'],
                ['cost_money', '扣费(单位分)','text.edit'],
                ['input_data', '输入数据' , 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 20, '...') : '';
                }],
                ['nodes_infos', '节点信息' , 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 20, '...') : '';
                }],
                ['zip_url', '下载包' , 'callback', function($value = '') {
                    if (!$value) return '';
                    $displayText = mb_strimwidth($value, 0, 20, '...');
                    return '<a href="' . $value . '" target="_blank" title="' . $value . '">' . $displayText . '</a>';
                }],
                
                ['workflow_name', '工作流名称'],
                ['workflow_description', '工作流描述'],
                ['bot_id', '机器人ID'],
                ['space_id', '空间ID'],
                ['bot_name', '机器人名称'],
                ['sort', '排序','text.edit'],
                ['bot_description', '机器人描述', 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 20, '...') : '';
                }],
                ['icon_url', '图标URL' ,'img_url'],
                ['status', '状态' , 'text.edit'],
                ['input_status','输入状态' , 'text.edit'],
                ['create_time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'workflow_name', '工作流名称'],
                ['text', 'bot_id', '机器人ID'],
                ['text', 'space_id', '空间ID'],
                ['text', 'bot_name', '机器人名称'],
                ['text', 'status', '状态'],
                ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->addRightButton('custom', [
                'title' => '工作流页面',
                'icon'  => 'fa fa-external-link',
                'href'  => 'https://www.coze.cn/work_flow?space_id=__space_id__&workflow_id=__workflow_id__',
                'target' => '_blank',
                'data-replace' => json_encode([
                    '__space_id__' => 'space_id',
                    '__workflow_id__' => 'workflow_id'
                ])
            ]) // 添加外部跳转按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $r = DB::connect('translate')->table('ts_workflow_list')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_workflow_list')->where('id', $id)->find();

        return ZBuilder::make('form')
        
            ->addFormItems([
                
                ['textarea', 'nodes_infos', '节点信息'],
                ['textarea', 'zip_url', '下载包'],

                
            ])
            ->addCkeditor('workflow_description_html', '描述信息')
            ->setFormData($info)
            ->fetch();
    }
    
    /**
     * 查询工作流详情
     * @param int $id 记录ID
     * @return mixed
     */
    public function viewWorkflowDetail($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取工作流记录信息
        $workflow = WorkflowListModel::get($id);
        if (!$workflow) {
            return $this->error('工作流记录不存在');
        }
        
        // 这里可以添加查询工作流详情的逻辑
        // 例如调用API或者其他操作
        
        return $this->success('查询成功');
    }
} 