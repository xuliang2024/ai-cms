<?php
// Fal API账号模型
namespace app\video\model;

use think\Model;

class FalAccountModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_fal_account';
    
} 