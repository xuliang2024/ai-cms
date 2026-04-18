<?php
// 课程章节视频模型
namespace app\video\model;

use think\Model;

class CourseChapterVideoModel extends Model
{
    protected $connection = 'translate';
    // 设置当前模型对应的完整数据表名称
    protected $table = 'ts_course_chapter_video';
    
    
} 