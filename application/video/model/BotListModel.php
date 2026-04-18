<?php
// 机器人列表模型
namespace app\video\model;

use think\Model;

class BotListModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_bot_list';
} 