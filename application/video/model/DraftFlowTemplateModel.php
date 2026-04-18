<?php

namespace app\video\model;

use think\Model;

class DraftFlowTemplateModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_draft_flow_template'; // 草稿导出模版表
    
    /**
     * 根据工作流ID获取模版信息
     */
    public function getByWorkflowId($workflow_id)
    {
        return $this->where('workflow_id', $workflow_id)->find();
    }
    
    /**
     * 获取用户的工作流模版
     */
    public function getUserTemplates($user_id)
    {
        return $this->where('user_id', $user_id)->select();
    }
    
    /**
     * 获取公开的模版列表
     */
    public function getPublicTemplates()
    {
        return $this->where('is_public', 1)->select();
    }
    
    /**
     * 根据用户ID和工作流ID获取模版
     */
    public function getUserWorkflowTemplate($user_id, $workflow_id)
    {
        return $this->where([
            'user_id' => $user_id,
            'workflow_id' => $workflow_id
        ])->find();
    }
    
    /**
     * 更新模版的更新时间
     */
    public function updateTime($id)
    {
        return $this->where('id', $id)->update(['updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * 设置模版公开状态
     */
    public function setPublicStatus($id, $is_public)
    {
        return $this->where('id', $id)->update([
            'is_public' => $is_public ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * 获取指定用户的模版数量
     */
    public function getUserTemplateCount($user_id)
    {
        return $this->where('user_id', $user_id)->count();
    }
} 