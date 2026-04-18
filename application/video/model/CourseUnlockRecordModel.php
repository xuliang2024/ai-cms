<?php
// 用户课程解锁记录模型
namespace app\video\model;

use think\Model;

class CourseUnlockRecordModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_course_unlock_record';
    
} 
