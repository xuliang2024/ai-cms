<?php
// 镜像表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DockerImages extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_docker_images')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_docker_images', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/DockerImagesModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '镜像名称'],
                ['image_uuid', '镜像ID'],
                ['area', '镜像区域'],
                ['image_url', '镜像封面','img_url'],
                ['status', '状态','status','',[0=>'下架',2=>'上架']],
                ['sort', '排序','text.edit'],
                // ['source_text', 'source_text', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                ['title', '标题'],
                ['sub_title', '副标题'],
                ['cmd', '启动容器命令'],
                ['time', '创建时间'],
                ['created_at', '镜像创建时间'],
                ['updated_at', '镜像更新时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'image_uuid', 'image_uuid'],
                ['text', 'status', '状态'],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_docker_images')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_docker_images')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['ossimage', 'image_url', '图片链接'],
                ['select', 'status', '状态','',[0=>'下架',2=>'上架'],0],

                ['text', 'title', '标题'],
                ['text', 'sub_title', '副标题'],
                ['text', 'cmd', '启动容器命令'],
                


            ])
          
            ->setFormData($info)
            ->fetch();
    }

                
}
