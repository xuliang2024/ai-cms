<?php
// 配置列表模型
namespace app\video\model;

use think\Model;

class ConfigListModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_config_list';
} 

