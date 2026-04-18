<?php
// Fal API 任务记录模型
namespace app\video\model;

use think\Model;

class FalTasksModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_fal_tasks';
    
} 