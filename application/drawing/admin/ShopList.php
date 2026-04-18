<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ShopList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_shop_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_shop_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('shop_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],

                ['title','标题'],
                ['sub_title','副标题'],
                ['tag','标签'],
                ['money','金额'],
                ['coin','点卷'],
                ['status','状态','text.edit'],
                ['sort','排序','text.edit'],
                ['is_vip','是否会员'],
                ['vip_level','会员等级'],
                ['time','名字'],
                
                ['right_button', '操作', 'btn']
                
            ])
            ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButtons(['add','delete'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_shop_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([

                ['text','title','标题'],
                ['textarea','sub_title','副标题'],
                ['text','tag','标签'],
                ['text','money','金额'],
                ['text','coin','点卷'],
                ['text','status','状态'],
                ['text','sort','排序'],
                ['text','is_vip','是否会员'],
                ['text','vip_level','会员等级']
                
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

            $r = DB::table('ai_shop_info')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_shop_info')->where('id',$id)->find();

        return ZBuilder::make('form')
              
             ->addFormItems([
              
                ['text','title','标题'],
                ['textarea','sub_title','副标题'],
                ['text','tag','标签'],
                ['text','money','金额'],
                ['text','coin','点卷'],
                ['text','status','状态'],
                ['text','sort','排序'],
                ['text','is_vip','是否会员'],
                ['text','vip_level','会员等级']


            ])
          
            ->setFormData($info)
            ->fetch();
    }



    
 

  
}