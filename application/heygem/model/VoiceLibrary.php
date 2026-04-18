<?php

namespace app\heygem\model;

use think\Model;
class VoiceLibrary extends Model
{   
    // 指定数据库连接
    protected $connection = 'translate';
    // 指定数据表名
    protected $table = 'ts_voice_library'; // 声音库表
    
    
} 