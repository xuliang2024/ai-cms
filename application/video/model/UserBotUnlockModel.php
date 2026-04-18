<?php
// 用户智能体解锁模型
namespace app\video\model;

use think\Model;

class UserBotUnlockModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_user_bot_unlock';
}