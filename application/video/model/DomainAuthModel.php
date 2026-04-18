<?php
// 域名授权管理模型
namespace app\video\model;

use think\Model;

class DomainAuthModel extends Model
{
    protected $connection = 'translate';
    protected $table = 'ts_domain_auth';
    
    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    
    // 获取状态列表
    public static function getStatusList()
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }
    
    // 检查域名是否在授权期内
    public function isValid()
    {
        if ($this->status != self::STATUS_ENABLED) {
            return false;
        }
        
        $now = date('Y-m-d H:i:s');
        
        // 检查开始时间
        if ($this->start_time && $this->start_time > $now) {
            return false;
        }
        
        // 检查结束时间
        if ($this->end_time && $this->end_time < $now) {
            return false;
        }
        
        return true;
    }
    
    // 根据域名查找授权记录
    public static function findByDomain($domain)
    {
        return self::where('domain', $domain)->find();
    }
    
    // 检查域名是否已授权且有效
    public static function checkDomainAuth($domain)
    {
        $auth = self::findByDomain($domain);
        if (!$auth) {
            return false;
        }
        return $auth->isValid();
    }
}

