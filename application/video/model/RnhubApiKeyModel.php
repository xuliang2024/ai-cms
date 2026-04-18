<?php
// API密钥管理模型
namespace app\video\model;

use think\Model;

class RnhubApiKeyModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_rnhub_api_keys';
} 