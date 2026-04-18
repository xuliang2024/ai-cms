<?php
// 豆包视频任务模型
namespace app\video\model;

use think\Model;

class DoubaoVideoTaskModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_doubao_video_tasks';
    
} 