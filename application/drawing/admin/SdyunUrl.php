<?php
//机器表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SdyunUrl extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_sdyun_url')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_sdyun_url', $map);
        
        return ZBuilder::make('table')
            ->setTableName('sdyun_url') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '标题'],
                ['order','订单'],
                ['sd_url','sd_url'],
                ['time', '创建时间'],
                ['hour','小时','text.edit'],
                // ['deploy_status', '部署状态',['1' => '等待', '2' => '运行','3'=>'删除']],
                // ['deploy_status','部署状态','text.edit'],
                ['deploy_status','状态（1/2/3）','text.edit'],

                ['status', '算力池','switch'],
                ['cnt','请求数'],
                ['error_cnt','错误请求数'],

                ['deployment_uuid','部署id'],
                ['gpu_name','gpu类型'],
                ['image_uuid','镜像ID'],
                ['price','价格'],
                ['uuid','uuid'],
                ['ssh_command','ssh'],
                ['root_password','密码'],
                ['pull_image_progress','百分比'],

                ['created_at','创建时间'],
                ['updated_at','更新时间'],
                ['started_at','启动时间'],
                ['stopped_at','停止时间'],
                
                ['update_time', '激活时间'],
                ['c_type', '机器类型'],
                
                ['right_button', '操作', 'btn']
                
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([  
                ['text', 'title', '标题', '', '', ''],
                ['text', 'order', '订单', '', '', ''],
                ['text', 'sd_url', 'sd_url', '', '', ''],
                ['select', 'deploy_status', '状态', '', '', ['1' => '等待', '2' => '运行','3'=>'删除']],
                
            ])
            ->setHeight('auto')
            ->addTopButton('add')
            ->addTopButton('delete')
            ->addRightButton('edit')

               ->fetch(); // 渲染页面

    }

      public function add() 
     {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            // $result = $this->validate($data, 'Article');
            // if(true !== $result) $this->error($result);
            
            $r = DB::table('ai_sdyun_url')->insert($data);
            if ($r) {
                // 记录行为
                // action_log('link_add', 'cms_link', $link['id'], UID, $data['title']);
                // $this->success('新增成功', 'index');
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
                
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'order', '订单号'],
                ['text', 'title', '标题'],      
                // ['select', 'sd_url', '状态','',[0=>'已下架',1=>'已上线'],1],
                ['text', 'sd_url', 'sd_url'],
                
                // ['text', 'mac_address', 'mac地址'],
                // ['text', 'hour', '时间'],
                ['select', 'hour', '时间','',[1=>'1个小时',4=>'4个小时',6=>'6个小时',12=>'12个小时',24=>'24个小时'],6],
                ['select', 'deploy_status', '状态','',[1=>'部署',2=>'运行',3=>'停止'],1],
                
            ])
            
            // ->setTrigger('type', 2, 'logo')
            ->fetch();
    }

     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            // $result = $this->validate($data, 'Article');
            // if(true !== $result) $this->error($result);
            $r = DB::table('ai_sdyun_url')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_sdyun_url')->where('id',$id)->find();

        return ZBuilder::make('form')
            
                ->addFormItems([
                    ['text', 'order', '订单号'],
                    ['text', 'title', '标题'],      
                    ['text', 'sd_url', 'sd_url'],
                    ['select', 'hour', '时间','',[1=>'1个小时',4=>'4个小时',6=>'6个小时',12=>'12个小时',24=>'24个小时'],6],
                    ['select', 'deploy_status', '状态','',[1=>'部署',2=>'运行',3=>'停止'],1],
                    
                   
            ])
            
            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }


}