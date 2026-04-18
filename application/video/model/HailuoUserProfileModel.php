<?php
// 海螺用户资料模型
namespace app\video\model;

use think\Model;

class HailuoUserProfileModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_hailuo_user_profiles';
    
} 