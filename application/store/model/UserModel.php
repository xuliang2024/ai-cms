<?php


namespace app\store\model;

use think\Model;
class UserModel extends  Model
{   

    protected $connection='faka_fyshark_com';
    protected $table='hm_user';
    protected $autoWriteTimestamp = true;
}