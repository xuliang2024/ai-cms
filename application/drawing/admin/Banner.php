<?php
//首页轮播图
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\admin\model\Boxdeviceinfo as BoxdeviceinfoModel;
use think\Db;

class Banner extends Admin {
	
    public function index() 
    {
        $order = $this->getOrder('sort desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_banner')->where($map)
        ->order($order)
        ->paginate();

        cookie('ai_banner', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit','delete'];
        return ZBuilder::make('table')
            // ->setPageTitle('用户管理') // 设置页面标题
            ->setTableName('banner') // 设置数据表名
            // ->setSearch(['id' => 'ID', 'username' => '用户名', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name','名称'],
                    
                    ['img', '轮播图','img_url'],
                    ['jpath', '路径跳转'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],
                    ['begin_time', '开始时间'],
                    ['end_time', '结束时间'],
                    ['time', '创建时间'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn) // 批量添加顶部按钮
            // ->addTopButton('custom', $btn_export) // 添加授权按钮
            ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
       
            ->setSearchArea([
                // ['text', 'username', '昵称'],
                // ['select', 'test', '用户名', '', '', ['test' => 'test', 'ming' => 'ming']],
                ['text', 'name', '名字'],
                ['text', 'status', '状态'],
               
            ])
            // ->openTopData($top_data)
            ->addOrder('sort') // 添加排序
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
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
            
            $r = DB::table('ai_banner')->insert($data);
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
                ['text', 'name', '名字'],             
            ])
            ->addOssImage('img', '图片(尺寸1029*438)', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'jpath', '跳转路径'],      
                ['select', 'status', '状态','',[0=>'已下架',1=>'已上线'],1],
                ['text', 'sort', '排序'],
                ['datetime', 'begin_time', '开始时间','必填 开始时间不能大于结束时间',date('Y-m-d H:i:s'),'YYYY-MM-DD HH:mm:ss','autocomplete=off'],
                ['datetime', 'end_time', '结束时间','必填',date('Y-m-d').' 23:59:59','YYYY-MM-DD HH:mm:ss','autocomplete=off'],
               
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
            $r = DB::table('ai_banner')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_banner')->where('id',$id)->find();

        return ZBuilder::make('form')
            
             ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'name', '名字'],             
            ])
            ->addOssImage('img', '图片(尺寸1029*438)', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'jpath', '跳转路径'], 
                // ['text', 'img', '图片'],                                 
                ['select', 'status', '状态','',[0=>'已下架',1=>'已上线']],
                ['text', 'sort', '排序'],
                ['datetime', 'begin_time', '开始时间','必填 开始时间不能大于结束时间',date('Y-m-d H:i:s'),'YYYY-MM-DD HH:mm:ss','autocomplete=off'],
                ['datetime', 'end_time', '结束时间','必填',date('Y-m-d').' 23:59:59','YYYY-MM-DD HH:mm:ss','autocomplete=off'],
               
            ])
            
            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }
   

  
}