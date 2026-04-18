<?php
// 激活码获取
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TsActivationCodeInfo extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_activation_code_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_activation_code_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ActivationCodeInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['activation_code_info', '激活码'],
                    ['user_id','user_id'],
                    ['status', '状态'],
                    ['pay_info_id', '支付模版ID'],
                    ['money', '售卖金额(分)'],
                    ['pay_info_type', '商品类型','status','',[0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'算力充值',8=>'直播套餐']],
                    ['pay_is_vip', '会员类型','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['activate_time', '激活时间'],
                    
                    ['time', '创建时间'],
                    ['comment', '所属用户ID'],
                    // ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'activation_code_info', '激活码'],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'activate_time', '激活时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'status', '状态'],
                ['text', 'user_id', 'user_id'],
                ['text', 'pay_info_id', '支付模版ID'],
                ['select', 'pay_info_type', '商品类型', '', '', [0=>'月卡',1=>'年卡',2=>'积分',3=>'积分加油包',4=>'单独功能',5=>'算力充值',8=>'直播套餐']],
                ['select', 'pay_is_vip', '会员类型', '', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
              
            ])
            ->addTopButton('add')
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('download', [
                'title' => '导出激活码',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/ts_activation_code_info/download.html?' . $this->request->query()
                // 'href'  => url('download',['pid' => '__id__'])
            ]) 
            ->setRowList($data_list) // 设置表格数据
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
         //下载
    public function download() {
        

        // $map = $this->getMap();
        
        // $data_list = DB::table('ai_activation_code_info')->where($map)->select();

         // 获取ids参数
    $ids = input('get.ids');
    // 将ids字符串分割为数组
    $ids_array = explode(',', $ids);
    
    // 查询数据库
    $data_list = DB::connect('translate')->table('ts_activation_code_info')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['activation_code_info', 50, '激活码'],
            ['money', 10, '金额(分)'],
            
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出激活码'.date('Y-m-d H:i:s'), $cellName, $data_list]);

       

    }


function createUuid() {
        if (function_exists('com_create_guid')){
            return trim(com_create_guid(), '{}');
        } else {
            mt_srand((double)microtime()*10000);    // optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);    // "-"
            $uuid = substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12);
            return $uuid;
        }
    }


    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if ( $data["comment"] == '') {
                $this->error('请输入所属的用户ID');
                // code...
            }
            
            if (!empty( $data["pay_info_id"]) ) {
                // code...
            // $data_pay_name = DB::query('select  name  from we_cat_pay_info where id = "'.$data["pay_id"].'"  ;');
            $data_pay_name = DB::connect('translate')->query('select  money,pay_type,is_vip  from ts_pay_info where id = "'.$data["pay_info_id"].'"  ;');

             foreach ($data_pay_name as $value){   
                $data["money"] = $value["money"];
                $data["pay_info_type"] = $value["pay_type"];
                $data["pay_is_vip"] = $value["is_vip"];
             } 

            
            }else{

                $this->error('请选择支付模版');
            }

            if (!empty( $data["add_cnt"]) ) {


                $num = $data["add_cnt"];
                $status = 0;
                $data_all = [];
                for($i=0;$i<$num;$i++){
                    $code = $this->createUuid();
                    $data_all[$i]['activation_code_info'] = $code;
                    $data_all[$i]['money'] = $data["money"];
                    $data_all[$i]['pay_info_type'] = $data["pay_info_type"];
                    $data_all[$i]['pay_is_vip'] = $data["pay_is_vip"];
                    $data_all[$i]['add_cnt'] = $data["add_cnt"];
                    $data_all[$i]['comment'] = $data["comment"];
                    $data_all[$i]['pay_info_id'] = $data["pay_info_id"];
                }
                $res = DB::connect('translate')->table('ts_activation_code_info')->insertAll($data_all);
                if($res){
                    $this->success('添加成功', 'index');
                }else{
                    $this->error('添加失败');
                }

            }else{
                $this->error('请输入激活码个数');
            }
        }

        $pay_id = array();
       
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        // $datas = DB::query("select id ,name  from we_cat_pay_info where status = 1 and p_user_id = ".get_pid()." ;");
        $datas = DB::connect('translate')->query("select id ,title,money,pay_type  from ts_pay_info ;");
        $is_we_apps = "";
        foreach ($datas as $data) {
                
               
                $data =array( $data["id"] => $data["title"]."-".$data["money"]);
                 
                $pay_id = $pay_id +$data;
            } 


                   
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['select', 'pay_info_id', '选择支付模版', '请选择支付模版', $pay_id],
                ['text', 'add_cnt', '激活码个数'],
                ['text', 'comment', '所属用户id'],
                
            ])
            ->fetch();
    }


}
