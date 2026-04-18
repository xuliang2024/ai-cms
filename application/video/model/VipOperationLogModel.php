<?php
// VIP操作日志模型
namespace app\video\model;

use think\Model;

class VipOperationLogModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_vip_operation_log';
    
} 
