<?php

namespace app\video\model;

use think\Model;

class SettlementRecordModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_settlement_record'; // 结算记录表
} 