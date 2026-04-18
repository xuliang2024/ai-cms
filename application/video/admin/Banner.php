<?php
// 小程序首页轮播图配置页
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Banner extends Admin {
    
    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_banner')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_banner', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BannerModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name','名称'],
                    
                    ['img', '轮播图','img_url'],
                    ['jpath', '路径跳转'],
                    ['status', '状态','switch'],
                    ['sort', '排序', 'text.edit'],
                    ['begin_time', '开始时间'],
                    ['end_time', '结束时间'],
                   
                    ['time','创建时间'],
                    ['right_button', '操作', 'btn']  
                    
            ])
            ->setSearchArea([  
                ['text', 'status', '上架状态'],
                
            ])
            ->addTopButton('add')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
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
            
            $r = DB::connect('translate')->table('ts_banner')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('img', '图片(尺寸1029*438)', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'name', '名字'],       
                ['text', 'jpath', '跳转路径'],      
                ['datetime', 'begin_time', '开始时间','必填 开始时间不能大于结束时间',date('Y-m-d H:i:s'),'YYYY-MM-DD HH:mm:ss','autocomplete=off'],
                ['datetime', 'end_time', '结束时间','必填',date('Y-m-d').' 23:59:59','YYYY-MM-DD HH:mm:ss','autocomplete=off'],
               
            ])
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_banner')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_banner')->where('id',$id)->find();

        return ZBuilder::make('form')

            ->addOssImage('img', '图片(尺寸1029*438)', '', '', '', '', '', ['size' => '50,50'])
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'name', '名字'],     
                ['text', 'jpath', '跳转路径'], 
                // ['text', 'img', '图片'],                                 

                ['datetime', 'begin_time', '开始时间','必填 开始时间不能大于结束时间',date('Y-m-d H:i:s'),'YYYY-MM-DD HH:mm:ss','autocomplete=off'],
                ['datetime', 'end_time', '结束时间','必填',date('Y-m-d').' 23:59:59','YYYY-MM-DD HH:mm:ss','autocomplete=off'],
               
            ])
        
            ->setFormData($info)
            ->fetch();
    }



}

