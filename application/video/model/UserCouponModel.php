<?php
// 用户优惠券模型
namespace app\video\model;

use think\Model;

class UserCouponModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_user_coupon';
    
} 
