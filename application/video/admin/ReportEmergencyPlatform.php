<?php
// 告警平台
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ReportEmergencyPlatform extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_report_emergency_platform')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_report_emergency_platform', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ReportEmergencyPlatformModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['title', '告警名称'],
                    ['solution', '解决方案'],
                    ['user_ids','用户IDs'],
                    ['user_names','用户names'],
                    ['user_phones','用户手机号'],
                    ['status','状态','switch'],
                    ['comment','备注'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'title', '告警名字'],
                ['text', 'status', '状态'],
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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

            if ($data["user_ids"] =='') {
                return   $this->error('推送告警人不能为空');
            } 


             $tagName1='';
             $tagPhone1='';
           
            // $array = explode(",", $data["status_tag"]);
    
            foreach ($data['user_ids'] as  $value) {
         
            $label_name = DB::connect('translate')->table('ts_user_management_list')->where('id',$value)->find();    
            $tagName = $label_name["name"];   
            $tagName1 .= ','.$tagName;

            $tagPhone = $label_name["phone"];   
            $tagPhone1 .= ','.$tagPhone;

            }

            if (strpos($tagName1, ",") === 0) {
                $tagName1 = substr($tagName1, 1);
            }
          
            $data['user_names']= $tagName1 ;

            if (strpos($tagPhone1, ",") === 0) {
                $tagPhone1 = substr($tagPhone1, 1);
            }
          
            $data['user_phones']= $tagPhone1 ;

            $data['user_ids'] = implode(',', $data['user_ids']);

            
            $r = DB::connect('translate')->table('ts_report_emergency_platform')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'title', '告警名字'],
                ['text', 'solution', '告警解决方案'],
                ['text', 'comment', '备注'],
            ])

            ->addSelect('user_ids', '推送告警人', '可选择多个标签类型，(必填)', DB::connect('translate')->table('ts_user_management_list')->where('status',1)->column('id,name'), '', 'multiple')
            // ->addSelect('status_tag', '书籍标签', '可选择多个标签类型，(必填)', DB::table('book_label')->where('status', 'eq', 1)->column('id,label_name'), '', 'multiple')
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if ($data["user_ids"] =='') {
                return   $this->error('推送告警人不能为空');
            } 


             $tagName1='';
             $tagPhone1='';
           
            // $array = explode(",", $data["status_tag"]);
    
            foreach ($data['user_ids'] as  $value) {
         
            $label_name = DB::connect('translate')->table('ts_user_management_list')->where('id',$value)->find();    
            $tagName = $label_name["name"];   
            $tagName1 .= ','.$tagName;

            $tagPhone = $label_name["phone"];   
            $tagPhone1 .= ','.$tagPhone;

            }

            if (strpos($tagName1, ",") === 0) {
                $tagName1 = substr($tagName1, 1);
            }
          
            $data['user_names']= $tagName1 ;

            if (strpos($tagPhone1, ",") === 0) {
                $tagPhone1 = substr($tagPhone1, 1);
            }
          
            $data['user_phones']= $tagPhone1 ;

            $data['user_ids'] = implode(',', $data['user_ids']);

            $r = DB::connect('translate')->table('ts_report_emergency_platform')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_report_emergency_platform')->where('id',$id)->find();

        return ZBuilder::make('form')
                        ->addFormItems([
                ['text', 'title', '告警名字'],
                ['text', 'solution', '告警解决方案'],
                ['text', 'comment', '备注'],
            ])

            ->addSelect('user_ids', '推送告警人', '可选择多个标签类型，(必填)', DB::connect('translate')->table('ts_user_management_list')->where('status', 'eq', 1)->column('id,name,phone'), '', 'multiple')
          
            ->setFormData($info)
            ->fetch();
    }



}

