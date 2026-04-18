<?php
// Coze插件管理模型
namespace app\video\model;

use think\Model;

class CozePluginsModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_coze_plugins';
} 