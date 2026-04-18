<?php
// 配置列表验证器
namespace app\video\validate;

use think\Validate;

class ConfigList extends Validate
{
    // 定义验证规则
    protected $rule = [
        'config_key'  => 'require|max:50',
        'config_value'=> 'require|max:255',
        'description' => 'max:500',
    ];
    
    // 定义错误信息
    protected $message = [
        'config_key.require'  => '配置键不能为空',
        'config_key.max'      => '配置键最多不能超过50个字符',
        'config_value.require'=> '配置值不能为空',
        'config_value.max'    => '配置值最多不能超过255个字符',
        'description.max'     => '描述最多不能超过500个字符',
    ];
    
    // 定义验证场景
    protected $scene = [
        'add'  => ['config_key', 'config_value', 'description'],
        'edit' => ['config_key', 'config_value', 'description'],
    ];
} 