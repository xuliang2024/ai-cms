<?php
// Flux Kontext Pro 图像生成任务模型
namespace app\video\model;

use think\Model;

class FluxKontextProTaskModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_flux_kontext_pro_task';
    
} 