<?php
// 每日优惠券派发记录模型
namespace app\video\model;

use think\Model;

class DailyCouponRecordModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_daily_coupon_record';
    
} 
