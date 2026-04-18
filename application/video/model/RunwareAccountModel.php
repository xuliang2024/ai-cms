<?php
// Runware账号管理模型
namespace app\video\model;

use think\Model;

class RunwareAccountModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_runware_account';
    
    // 状态常量
    const STATUS_DISABLED = 0; // 禁用
    const STATUS_NORMAL = 1;   // 正常
    
    // 获取状态列表
    public static function getStatusList()
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_NORMAL => '正常',
        ];
    }
}

