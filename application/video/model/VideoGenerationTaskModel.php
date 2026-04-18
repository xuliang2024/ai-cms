<?php


namespace app\video\model;

use think\Model;
class VideoGenerationTaskModel extends  Model
{   

    protected $connection='translate';
    protected $table='ts_video_generation_task';//用户表
}