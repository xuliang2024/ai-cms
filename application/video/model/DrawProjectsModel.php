<?php
// AI绘画项目模型
namespace app\video\model;

use think\Model;

class DrawProjectsModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_draw_projects';
    
}

