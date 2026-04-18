<?php
//Azure语音秘钥记录表
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AzureSpeech extends Admin {
	
    public function index() 
    {
        // $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_azure_speech')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_azure_speech', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit','delete'];
        return ZBuilder::make('table')
            // ->setPageTitle('用户管理') // 设置页面标题
            ->setTableName('azure_speech') // 设置数据表名
            // ->setSearch(['id' => 'ID', 'username' => '用户名', 'email' => '邮箱']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['account','账号'],
                    // ['user_id','用户ID','text.edit'],
                    ['password', '密码'],
                    ['if_use', '是否使用','switch'],
                    ['if_use', '是否使用','text.edit'],
                    ['status', '秘钥状态','status','',[0=>'异常',1=>'已失效',2=>'可使用']],
                    ['update_time', '更新时间'],
                    ['area', '区域'],
                    ['token', '使用','text.edit'],
                    ['key1', '秘钥1'],
                    ['key2', '秘钥2'],
                    ['mobile', '手机号'],
                    ['card_number', '信用卡号'],
                    ['comment', '备注'],
                    ['time', '创建时间'],
                    ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->addTopButtons($contro_top_btn) // 批量添加顶部按钮
            ->addTopButton('self',[
                'title'=>'检查秘钥状态',
                'icon'=>'fa fa-fw fa-refresh',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('update_key')
            ])

            // ->addTopButton('custom', $btn_export) // 添加授权按钮
            // ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
            ->addRightButtons(['edit','delete']) // 批量添加右侧按钮//,'delete'
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->setSearchArea([
            //     // ['text', 'username', '昵称'],
            //     // ['select', 'test', '用户名', '', '', ['test' => 'test', 'ming' => 'ming']],
            //     ['text', 'name', '名字'],
            //     ['text', 'status', '状态'],
               
            // ])
            // ->openTopData($top_data)
            // ->addOrder('sort') // 添加排序
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面


    }


    public function update_key(){
         
         $data_list = Db::query("select * from ai_azure_speech ;");
        
        foreach ($data_list as $value) {
            $key = $value["key1"];
            $east = $value["area"];
            $result= $this->rescode($key,$east);

            // if ($result==1) {
            //     // code...
            // }
            // print_r('是否可用'.$result.'key是:'.$key);
            $status_re=0;
            switch ($result) {
                case 0://不可用
                    // code...
                    $status_re = 1;
                   
                    break;
                case 1://可用
                    // code...
                    $status_re = 2;
                    
                    break;
                case -1://请求出错（传值的秘钥或者地区有误）
                    // code...
                    $status_re = 0;
                    break;    
                default:
                    // code...
                    break;
            }

            $data['status'] = $status_re;
            $data['update_time'] = date('Y-m-d H:i:s');
// DB::table('backmarket_order_line_info_tab')->where('order_id',$value['order_id'])->update(['Clearing_start'=> '1']);

           $res= Db::table('ai_azure_speech')->where('id',$value['id'])->update($data);

        }

        $this->success('更新成功');
        // if($res){
        //     $this->success('更新成功');
        // }else{
        //     $this->error('更新失败');
        // }
    }



    public function rescode($key = '',$east=''){
       
// 语音服务订阅密钥
// $subscriptionKey = '3e53851cbfc543f49abe7d46c123b2a9';
// $subscriptionKey = '14dd3d6dcfe7401da73c490f8b78c1b4';
$subscriptionKey = $key;

// 语音服务区域
$region = 'eastus';


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://'.$east.'.api.cognitive.microsoft.com/sts/v1.0/issueToken');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  'Ocp-Apim-Subscription-Key: '.$key.'',
  'Content-Length: 0'
));

$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    // echo 'Error:' . curl_error($ch);
    // print_r('出错');
    // print_r('Error:' . curl_error($ch));
    return -1;
}

// print_r($httpcode);
// print_r('打印数据');
// print_r($result);
if ($httpcode == "200") {
    // code...
    // print_r('200');
    // 关闭 cURL
curl_close($ch);
    return 1;
}else{
// 关闭 cURL
curl_close($ch);
    return 0;
    // print_r('不是200');
}

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
            
            $r = DB::table('ai_azure_speech')->insert($data);
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
                ['text', 'account', '账号'],      
                ['text', 'password', '密码'], 
                ['text', 'user_id', '用户ID'],      
                ['text', 'subscribe_id', '订阅ID'],      
                ['text', 'key1', '秘钥1'],      
                ['text', 'key2', '秘钥2'],      
                ['text', 'area', '区域'],      
                ['text', 'mobile', '手机号'],
                ['text', 'card_number', '信用卡号'],
                ['text', 'comment', '备注'],
                
               
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
            $r = DB::table('ai_azure_speech')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_azure_speech')->where('id',$id)->find();

        return ZBuilder::make('form')
            
            
                ->addFormItems([
                
                ['text', 'account', '账号'],      
                ['text', 'password', '密码'],      
                ['text', 'subscribe_id', '订阅ID'],      
                ['text', 'key1', '秘钥1'],      
                ['text', 'key2', '秘钥2'], 
                ['text', 'area', '区域'],       
                ['text', 'mobile', '手机号'],
                ['text', 'card_number', '信用卡号'],                            
                                          
                ['select', 'status', '状态','',[0=>'待认证',1=>'已失效',2=>'可使用']],
                 ['text', 'comment', '备注'], 
                
               
            ])
            
            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }
   

  
}