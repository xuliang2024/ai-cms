<?php

namespace app\video\model;

use think\Model;

class BackfillRecordModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_backfill_record'; // 回填记录表
} 