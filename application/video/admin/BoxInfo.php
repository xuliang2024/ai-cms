<?php
// 小程序AI工具箱配置页
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BoxInfo extends Admin {
    

    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_box_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_box_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BoxInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['name', '名称'],
                    ['status', '上架状态','switch'],
                    ['is_index', '是否首页','switch'],
                    ['sort','排序值','text.edit'],
                    ['img','背景图片','img_url'],
                    ['icon','图标','img_url'],
                    ['jpath', '跳转路径'],
                   
                    ['time','创建时间'],
                    ['comment','备注'],
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
            
            $r = DB::connect('translate')->table('ts_box_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
             ->addOssImage('img', '背景图片', '', '', '', '', '', ['size' => '50,50'])
             ->addOssImage('icon', '图标', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                ['text', 'name', '名称'],
                ['text', 'jpath', '跳转链接'],
                ['text', 'comment', '备注'],
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

            $r = DB::connect('translate')->table('ts_box_info')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_box_info')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addOssImage('img', '背景图片', '', '', '', '', '', ['size' => '50,50'])
             ->addOssImage('icon', '图标', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                ['text', 'name', '名称'],
                ['text', 'jpath', '跳转链接'],
                ['text', 'comment', '备注'],
            ])
        
            ->setFormData($info)
            ->fetch();
    }



}

