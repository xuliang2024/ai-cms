<?php
namespace app\video\model;

use think\Model;

class ApiKeyDailyStatsModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'api_key_daily_stats';
}
