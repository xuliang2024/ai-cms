<?php
namespace app\video\model;

use think\Model;

class JimengApiTokenModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_jimeng_api_token';
}
