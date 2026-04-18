<?php
// 应用列表
namespace app\store\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\common\utils\Common;
use app\store\model\UserModel as UserModel;

class Cashout extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        // $data_list = DB::connect('faka_fyshark_com')->table('hm_cashout')->where($map)
        // ->order('create_time desc')
        // ->paginate();

        $data_list=DB::connect('faka_fyshark_com')->table('hm_cashout')->where($map)
            ->alias('a')
            ->join((new UserModel)->getTable().' c','c.id=a.user_id')
            ->field('a.*,c.bank_account_no_gongmao as bank_account_no_gongmao,c.alipay_account_no_gongmao as alipay_account_no_gongmao')
            ->order('create_time desc')
            // ->paginate();
             ->paginate()->each(function($item, $key){
                            
                // $userData = DB::connect('faka_fyshark_com')->table('hm_user')->where('id', $item["user_id"])->find();
                // if($userData) {
                //     $item["username"] = $userData['username'];
                //     $item["p1"] = $userData['p1'];
                // }
                $item["complete_time_new"] = $item['complete_time']/1000;
            
            
            return $item;
        });



        cookie('hm_cashout', $map);
        
        return ZBuilder::make('table')
            ->setTableName('store/CashoutModel',2) // 设置数据表名
            // ->addColumn('complete_time', '创建时间', 'datetime', '没有填写日期时间', 'Y/m/d H:i:s')
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['out_trade_no', '订单号'],
                    ['user_id', '用户ID'],
                    ['request_id', '工猫标识'],
                    ['alipay_account_no_gongmao', '支付宝账号'],
                    
                    ['bank_account_no_gongmao','银行卡账号'],
                    ['money','提现金额'],
                    // ['status','状态','text.edit'],
                    // ['right_button', '操作', 'btn'],
                    ['status', '状态','status','',[-1=>'驳回提现',0=>'未处理',1=>'已完成',2=>'已提交工猫',3=>'提交工猫失败',4=>'提现失败']],

                    
                    // ['status','状态','text.edit'],
                    ['error_msg','error_msg'],
                    ['create_time','创建时间','datetime'],
                    ['appment_time','提交工猫提现时间'],
                    // ['complete_time','最终提现完成时间'],
                    ['complete_time_new','最终提现完成时间','datetime'],
                    // ['complete_time', '最终提现完成时间', 'callback', function ($val, $data) {
                    // $val = sprintf(self::$ellipsisElement, $val, $val);
                    // return $val;
                    // }, '__data__'],
                    // ['comment','备注'],
                    
                   
                    
            ])
           
            ->setSearchArea([  
               ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
               ['daterange', 'complete_time', '完成时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'out_trade_no', '订单号'],
                ['text', 'user_id', '用户ID'],
                ['text', 'name', '账号名称'],
                ['text', 'account', '账号'],
                ['select', 'status', '状态', '', '', [-1=>'驳回提现',0=>'未处理',1=>'已完成',2=>'已提交工猫',3=>'提交工猫失败',4=>'提现失败']],
                
                
               
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮

            ->addRightButton('custom',[
                'title'=>'支付宝打款',
                'icon'=>'iconfont icon-zhifubao',
                'class'=>'btn btn-info btn-rounded',
                'href'=>url('store/cashout/remit',['id'=>'__id__','money'=>'__money__','user_id'=>'__user_id__','out_trade_no'=>'__out_trade_no__']),
            ],false,['style'=>'primary','title' => false,'icon'=>true])


            ->addRightButton('custom',[
                'title'=>'银行卡打款',
                'icon'=>'fa fa-fw fa-credit-card',
                'class'=>'btn btn-primary btn-rounded',
                'href'=>url('store/cashout/remit_bank',['id'=>'__id__','money'=>'__money__','user_id'=>'__user_id__','out_trade_no'=>'__out_trade_no__']),
            ],false,['style'=>'primary','title' => false,'icon'=>true])
            //  ->addRightButton('custom',[
            //     'title'=>'驳回提现',
            //     'icon'=>'fa fa-fw fa-bus',
            //     'class' => 'btn btn-warning btn-rounded',
            //     'href'=>url('store/cashout/unremit',['id'=>'__id__']),
            // ],false,['style'=>'primary','title' => true,'icon'=>false])

            ->setRowList($data_list) // 设置表格数据
            ->replaceRightButton(['status' => 1], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            // ->replaceRightButton(['status' => -1], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            ->replaceRightButton(['status' => 2], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            ->setColumnWidth('right_button', 200)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }



    public function remit($id = null,$money = null,$user_id =null,$out_trade_no = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 获取用户工猫提现相关字段
        $info = DB::connect('faka_fyshark_com')->table('hm_user')->where('id',$user_id)->find();

        
        // $info['bank_account_no_gongmao'];
        // $info['alipay_account_no_gongmao'];



        // // 变更体现订单状态为提交工猫
        // $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 2,'complete_time' =>time()]);

      
        // 获取所有请求参数
        // $allParams = input();
        // // 获取所有 GET 参数
        // $getParams = input('get.');
        // // 获取所有 POST 参数
        // $postParams = input('post.');
        // // 获取特定的参数
        // $appKey = input('post.appKey', '', 'trim'); // 获取 POST 中的 appKey 参数并进行 trim

        // // 打印查看参数
        // echo "All Parameters: ";
        // var_dump($allParams);  // 打印所有参数
        // echo "GET Parameters: ";
        // print_r($getParams);  // 打印GET参数
        // echo "POST Parameters: ";
        // print_r($postParams);  // 打印POST参数
        // echo "appKey: " . $appKey;  // 打印特定的参数



        $paramMap = [];
        $url = "https://openapi.gongmall.com/api/merchant/doSinglePayment";
        // $appKey = "e9235cb002bd4fb2a41db339d7f798c8";
        // $appSecret = "de2702c741a06f7f5ddfcfd48330f913";

        $appKey = "e9235cb002bd4fb2a41db339d7f798c8";
        $appSecret = "de2702c741a06f7f5ddfcfd48330f913";




        $nonce = uniqid(); // Or use any GUID generator

        $paramMap[Common::APP_KEY] = $appKey;
        $paramMap[Common::TIMESTAMP] = time() * 1000;
        $paramMap[Common::NONCE] = $nonce;

        // $paramMap[Common::SERVICE_ID] = "18659";
        // $paramMap[Common::SERVICE_ID] = "19239";
        $paramMap[Common::SERVICE_ID] = "19513"; //安阳薪童
        // $paramMap[Common::requestId] = "1112229964561112";
        $paramMap[Common::requestId] = $nonce;
        $paramMap[Common::mobile] = $info['mobile_gongmao'];
        $paramMap[Common::name] = $info['name_gongmao'];
        $paramMap[Common::amount] = $money;
        $paramMap[Common::identity] = $info['identity_gongmao'];
        $paramMap[Common::bankAccount] = $info['alipay_account_no_gongmao'];
        $paramMap[Common::dateTime] = date('YmdHis');
        $paramMap[Common::salaryType] = "ALIPAY";
        $paramMap[Common::extRemark] = $out_trade_no;
        
        $sign = Common::getSign($paramMap, $appSecret);
        $paramMap[Common::SIGN] = $sign;

        $result = Common::doPost($url, $paramMap);

        print_r($result);
        
        
        $resultArray = json_decode($result, true);
        $success = $resultArray['success'] ?? false;

        // 根据 success 的值进行判断
        if ($success) {
            $requestId = $resultArray['data']['requestId'] ?? null;
            $appmentTime = $resultArray['data']['appmentTime'] ?? null;
            echo "Success: true";
            echo "Request ID: " . $requestId;

            $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 2,'appment_time' =>$appmentTime,'request_id' =>$requestId]);
             $this->success('提交工猫成功，等待打款', 'index');
            // return json(['result' => $result, 'success' => true, 'requestId' => $requestId]);
        } else {
            $errorMsg = $resultArray['errorMsg'] ?? 'Unknown error';
            echo "Success: false";
            echo "Error Message: " . $errorMsg;

            $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 3,'request_id' =>$nonce,'error_msg'=>$errorMsg]);
             $this->error('提交工猫报错，请前往工作台处理，错误:'.$errorMsg);
            

            // return json(['result' => $result, 'success' => false, 'errorMsg' => $errorMsg]);
        }

    }


    public function remit_bank($id = null,$money = null,$user_id =null,$out_trade_no = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 获取用户工猫提现相关字段
        $info = DB::connect('faka_fyshark_com')->table('hm_user')->where('id',$user_id)->find();

        
        // $info['bank_account_no_gongmao'];
        // $info['alipay_account_no_gongmao'];



        // // 变更体现订单状态为提交工猫
        // $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 2,'complete_time' =>time()]);

      
        // 获取所有请求参数
        // $allParams = input();
        // // 获取所有 GET 参数
        // $getParams = input('get.');
        // // 获取所有 POST 参数
        // $postParams = input('post.');
        // // 获取特定的参数
        // $appKey = input('post.appKey', '', 'trim'); // 获取 POST 中的 appKey 参数并进行 trim

        // // 打印查看参数
        // echo "All Parameters: ";
        // var_dump($allParams);  // 打印所有参数
        // echo "GET Parameters: ";
        // print_r($getParams);  // 打印GET参数
        // echo "POST Parameters: ";
        // print_r($postParams);  // 打印POST参数
        // echo "appKey: " . $appKey;  // 打印特定的参数



        $paramMap = [];
        $url = "https://openapi.gongmall.com/api/merchant/doSinglePayment";
        $appKey = "e9235cb002bd4fb2a41db339d7f798c8";
        $appSecret = "de2702c741a06f7f5ddfcfd48330f913";
        $nonce = uniqid(); // Or use any GUID generator

        $paramMap[Common::APP_KEY] = $appKey;
        $paramMap[Common::TIMESTAMP] = time() * 1000;
        $paramMap[Common::NONCE] = $nonce;

        // $paramMap[Common::SERVICE_ID] = "18659";
        // $paramMap[Common::SERVICE_ID] = "19239";
        $paramMap[Common::SERVICE_ID] = "19513"; //安阳薪童
        // $paramMap[Common::requestId] = "1112229964561112";
        $paramMap[Common::requestId] = $nonce;
        $paramMap[Common::mobile] = $info['mobile_gongmao'];
        $paramMap[Common::name] = $info['name_gongmao'];
        $paramMap[Common::amount] = $money;
        $paramMap[Common::identity] = $info['identity_gongmao'];
        $paramMap[Common::bankAccount] = $info['bank_account_no_gongmao'];
        $paramMap[Common::dateTime] = date('YmdHis');
        $paramMap[Common::salaryType] = "BANK";
        $paramMap[Common::extRemark] = $out_trade_no;
        
        $sign = Common::getSign($paramMap, $appSecret);
        $paramMap[Common::SIGN] = $sign;

        $result = Common::doPost($url, $paramMap);
        
        print_r($result);
        

        $resultArray = json_decode($result, true);
        $success = $resultArray['success'] ?? false;

        // 根据 success 的值进行判断
        if ($success) {
            $requestId = $resultArray['data']['requestId'] ?? null;
            $appmentTime = $resultArray['data']['appmentTime'] ?? null;
            echo "Success: true";
            echo "Request ID: " . $requestId;

            $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 2,'appment_time' =>$appmentTime,'request_id' =>$requestId]);
             $this->success('提交工猫成功，等待打款', 'index');
            // return json(['result' => $result, 'success' => true, 'requestId' => $requestId]);
        } else {
            $errorMsg = $resultArray['errorMsg'] ?? 'Unknown error';
            echo "Success: false";
            echo "Error Message: " . $errorMsg;

            $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => 3,'request_id' =>$nonce,'error_msg'=>$errorMsg]);
             // $this->success('提交工猫报错，请前往工作台处理，错误:'.$errorMsg, 'index');
             $this->error('提交工猫报错，请前往工作台处理，错误:'.$errorMsg);

            // return json(['result' => $result, 'success' => false, 'errorMsg' => $errorMsg]);
        }

    }
    public function unremit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $r = DB::connect('faka_fyshark_com')->table('hm_cashout')->where('id',$id)->update(['status' => -1,'complete_time' =>time()]);

        if ($r) {
                $this->success('已驳回提现', 'index');
            } else {
                $this->error('驳回失败');
            }

    }


}

