<?php

namespace app\heygem\model;

use think\Model;
class LipSyncTasks extends Model
{   
    // 指定数据库连接
    protected $connection = 'translate';
    // 指定数据表名
    protected $table = 'ts_lip_sync_tasks'; // 对口型任务表
    
    
} 