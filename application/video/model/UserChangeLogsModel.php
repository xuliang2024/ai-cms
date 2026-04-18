<?php


namespace app\video\model;

use think\Model;
class UserChangeLogsModel extends  Model
{   

    protected $connection='translate';
    protected $table='ts_user_change_logs';//用户表
}