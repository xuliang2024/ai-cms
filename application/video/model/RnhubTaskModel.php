<?php
// 任务管理模型
namespace app\video\model;

use think\Model;

class RnhubTaskModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_rnhub_tasks';
    
    // 任务状态常量
    const STATUS_WAITING = 'WAITING';
    const STATUS_QUEUED = 'QUEUED';
    const STATUS_RUNNING = 'RUNNING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    
    // 获取状态列表
    public static function getStatusList()
    {
        return [
            self::STATUS_WAITING => '等待中',
            self::STATUS_QUEUED => '已排队',
            self::STATUS_RUNNING => '运行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失败',
        ];
    }
}

