<?php
namespace app\video\admin;
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;


class BaseAdmin extends Admin {
    
    protected $connection = [
        'type'     => 'mysql',
        'hostname' => '127.0.0.1',
        'database' => 'translate',
        'username' => 'translate',
        'password' => 'HWLXpJ3Ye5mMpXBx',
        'hostport' => '3306',
        'params'   => [],
        'charset'  => 'utf8',
        'prefix'   => 'ts',
    ];

    protected function getConnection() {
        return Db::connect($this->connection);
    }
}
