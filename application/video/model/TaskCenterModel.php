<?php
// 任务中心模型
namespace app\video\model;

use think\Model;

class TaskCenterModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_task_center';
} 