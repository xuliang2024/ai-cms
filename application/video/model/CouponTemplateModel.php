<?php
// 优惠券模板模型
namespace app\video\model;

use think\Model;

class CouponTemplateModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_coupon_template';
    
} 
