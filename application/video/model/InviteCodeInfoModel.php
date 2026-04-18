<?php

namespace app\video\model;

use think\Model;

class InviteCodeInfoModel extends Model
{   
    protected $connection = 'translate';
    protected $table = 'ts_invite_code_info'; // 邀请码信息表
    
    /**
     * 根据邀请码获取信息
     */
    public function getByCode($invite_code)
    {
        return $this->where('invite_code', $invite_code)->find();
    }
    
    /**
     * 增加使用次数
     */
    public function incrementCount($invite_code)
    {
        return $this->where('invite_code', $invite_code)->setInc('cnt');
    }
    
    /**
     * 获取用户的邀请码
     */
    public function getUserInviteCode($user_id)
    {
        return $this->where('user_id', $user_id)->find();
    }
} 