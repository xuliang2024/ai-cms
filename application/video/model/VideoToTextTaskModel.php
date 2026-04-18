<?php


namespace app\video\model;

use think\Model;
class VideoToTextTaskModel extends  Model
{   

    protected $connection='translate';
    protected $table='ts_video_to_text_task';//
}