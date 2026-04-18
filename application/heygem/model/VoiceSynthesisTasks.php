<?php

namespace app\heygem\model;

use think\Model;
class VoiceSynthesisTasks extends Model
{   
    // 指定数据库连接
    protected $connection = 'translate';
    // 指定数据表名
    protected $table = 'ts_voice_synthesis_tasks'; // 语音合成任务表
    
    
} 