<?php
// namespace app\index\controller;

// use think\Controller;

namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;



class HomeController extends admin
{
    public function index()
    {
        // 模拟数据
        $enterpriseMessages = [
            ['title' => '这里是消息标题+正文前20字', 'date' => '2024-07-22'],
            // 其它数据...
        ];

        $pendingTasks = [
            ['title' => 'A某提交了调拨申请', 'date' => '2024-07-22'],
            // 其它数据...
        ];

        $this->assign('enterpriseMessages', $enterpriseMessages);
        $this->assign('pendingTasks', $pendingTasks);

        return $this->fetch();
    }
}