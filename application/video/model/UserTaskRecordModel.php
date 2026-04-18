<?php
// 用户任务完成状态记录模型
namespace app\video\model;

use think\Model;

class UserTaskRecordModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_user_task_record';
} 