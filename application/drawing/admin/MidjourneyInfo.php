<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MidjourneyInfo extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_mj_account_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_mj_account_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('mj_account_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],

                ['name','名字'],
                ['guildId', '服务器'],
                ['channelId', '频道'],
                
                ['mjBotChannelId', '机器ID'],
                ['userToken', 'token'],
                ['userAgent', 'UA'],
                ['coreSize', '并发'],
                ['queueSize', '队列'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([
                ['daterange', 'time', '时间'],
                ['text', 'channelId', 'channelId'],
                
            
            ])

             
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add',['title'=>'新增'])

            ->fetch(); // 渲染页面

    }

    public function add() 
    {

       // 保存数据
       if ($this->request->isPost()) {
           // 表单数据
           $data = $this->request->post();
           
           $r = DB::table('ai_mj_account_info')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }

       
       // 显示添加页面
       return ZBuilder::make('form')
           
          
           ->addFormItems([
                   
                   ['textarea','name', '名字'],
                   ['text','guildId', '服务器'],
                   ['text','channelId', '频道'],
                   ['text','mjBotChannelId', '私信ID'],
                   ['text','userToken', 'userToken'],
                   ['text','userAgent', 'userAgent'],
                   ['text','coreSize', '并发'],
                   ['text','queueSize', '队列'],
                   

           ])
           
           ->fetch();
   }



    

 

  
}