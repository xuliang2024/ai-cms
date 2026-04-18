<?php
namespace app\video\model;

use think\Model;

class ApiKeyCallLogModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'api_key_call_log';
}
