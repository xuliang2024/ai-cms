<?php
// 支付模版配置
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class PayInfo extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_pay_info')->where($map)
        ->order('sort desc')
        ->paginate();

        cookie('ts_pay_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/PayInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['title', '标题'],
                    ['sub_title','副标题'],
                    // ['content','详细描述'],
                    ['tag','标签'],
                    ['money', '售卖金额(分)','text.edit'],
                    ['points_balance','积分余额','text.edit'],

                    ['sort', '排序','text.edit'],
                    ['status', '状态','switch'],
                    ['pay_type', '类型','status','',[0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'充值',6=>'扣子新人大礼包',7=>'扣子工坊月卡',8=>'直播套餐' , 10=>'积分充值' ]],

                    ['is_vip', '会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['comment', '备注'],
                    // ['jump_appid', '外跳appid'],
                    ['time', '创建时间'],
                    ['right_button', '操作', 'btn']
            ])
            
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'status', '状态'],
                ['select', 'pay_type', '类型', '', '', [0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'充值',6=>'扣子新人大礼包',7=>'扣子工坊月卡',8=>'直播套餐' , 10=>'积分充值' ]],
                ['select', 'is_vip', '会员级别', '', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
              
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::connect('translate')->table('ts_pay_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'sub_title', '副标题'],
                ['ckeditor', 'content', '详细描述'],
                ['text', 'tag', '标签'],
                ['text', 'money', '售卖金额(分)'],
                ['text', 'points_balance', '积分余额'],
                ['select', 'pay_type', '类型', '', [0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'充值',6=>'扣子新人包',7=>'扣子月卡',8=>'直播套餐' , 10=>'积分充值' ]],
                ['select', 'is_vip', '会员等级', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
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

            $r = DB::connect('translate')->table('ts_pay_info')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_pay_info')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'sub_title', '副标题'],
                ['ckeditor', 'content', '详细描述'],
                ['text', 'tag', '标签'],
                ['text', 'money', '售卖金额(分)'],
                ['text', 'points_balance', '积分余额'],

                ['select', 'pay_type', '类型', '', [0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'充值',6=>'扣子新人包',7=>'扣子工坊月卡',8=>'直播套餐' , 10=>'积分充值' ]],
                ['select', 'is_vip', '会员等级', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                ['text', 'comment', '备注'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }




}
