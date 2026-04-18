<?php
// 机器人案例列表模型
namespace app\video\model;

use think\Model;

class BotCaseListModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_bot_case';
} 