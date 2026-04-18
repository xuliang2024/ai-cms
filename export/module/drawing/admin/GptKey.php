<?php
//机器表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class GptKey extends Admin {
	

    function fetchUsageAndLimit($token,$sts_session) {
        $ch = curl_init();
        $start_date = date("Y-m-01"); // First day of the current month
        $end_date = date("Y-m-d"); // Current date
    
        $endpoints = array(
            'subscription' => 'http://gpt2.aidraw.natapp1.cc/api/openai/dashboard/billing/subscription',
            'usage' => "http://gpt2.aidraw.natapp1.cc/api/openai/dashboard/billing/usage?start_date={$start_date}&end_date={$end_date}"
        );
    
        $results = array(
            'message' => '' // default value
        );
    
        foreach ($endpoints as $key => $endpoint) {
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if($key == 'usage'){
                $headers = array(
                    'Accept: */*',
                    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                    'Authorization: Bearer ' . $sts_session,
                    'Content-Type: application/json',
                    'Proxy-Connection: keep-alive',
                    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                    'x-requested-with: XMLHttpRequest'
                );
            }else{
                $headers = array(
                    'Accept: */*',
                    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Proxy-Connection: keep-alive',
                    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                    'x-requested-with: XMLHttpRequest'
                );
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            } else {
                $resultArray = json_decode($result, true);
                if (isset($resultArray['error']) && $resultArray['error']['code'] == 'account_deactivated') {
                    $results['message'] = $resultArray['error']['message'] ?? '';
                }
                switch ($key) {
                    case 'subscription':
                        $results['hard_limit_usd'] = $resultArray['hard_limit_usd'] ?? 0;
                        break;
                    case 'usage':
                        $results['total_usage'] = $resultArray['total_usage'] ?? 0;
                        break;
                }
            }
        }
    
        curl_close($ch);
    
        return $results;
    }
    
    // // Usage example
    // $token = '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';
    // print_r(fetchUsageAndLimit($token));
    

    public function updateToken(){
        $map = $this->getMap();
        $data_list = DB::table('ai_gpt_key')->where($map)
        ->order('time desc')
        ->paginate();
    
        // Iterate over data_list and fetch usage and limit for each api_key
        foreach($data_list as $key => $data) {
            $api_key = $data['api_key'];
            $sts_session = $data['sts_session'];
            $result = $this->fetchUsageAndLimit($api_key,$sts_session);
            
            // Update the database with the fetched information
            // Assuming 'total_usage' and 'hard_limit_usd' are fields in your table
            DB::table('ai_gpt_key')
                ->where('id', $data["id"]) // assuming 'id' is the primary key
                ->update([
                    'total_usage' => $result['total_usage']/100,
                    'hard_limit_usd' => $result['hard_limit_usd'],
                    'message' => $result['message']
                ]);
        }
        $this->success('更新成功', 'index');

    }

    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_gpt_key')->where($map)
        ->order('time desc')
        ->paginate();
    
        cookie('ai_gpt_key', $map);
        
        return ZBuilder::make('table')
            ->setTableName('gpt_key') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '标题'],
                ['api_key','api_key'],
                ['mac_address', 'mac地址'],
                ['status', '状态','switch'],
                ['sts_session','token'],
                ['c_type', '机器类型'],
                ['comment', '备注'],
                ['total_usage', '使用'],
                ['hard_limit_usd', '总额度'],
                ['message', '错误信息'],
                ['time','时间'],
                ['right_button', '操作', 'btn']
                // ['time', '创建时间'],
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add')
            ->addTopButton('self',[
                'title'=>'查额度',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('updateToken')
            ])
            ->setSearchArea([  
                ['text', 'api_key', 'gpt key', '', '', ''],
                
              
            ])
            ->addTopButton('delete')
            ->addRightButton('edit')
    
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
            
            $r = DB::table('ai_gpt_key')->insert($data);
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
                ['text', 'title', '标题'],      
                ['text', 'api_key', 'api_key'],   
                ['text', 'sts_session', 'token'],      
                ['radio', 'status', '状态','',[0=>'已下架',1=>'已上线'],1],
                ['text', 'c_type', '类型','','0'],
                // ['text', 'mac_address', 'mac地址'],
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
            $r = DB::table('ai_gpt_key')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::table('ai_gpt_key')->where('id',$id)->find();

        return ZBuilder::make('form')
            
                ->addFormItems([
                // ['radio', 'type', '链接类型', '', [1 => '文字链接', 2 => '图片链接'], 1],
                ['text', 'title', '标题'],      
                ['text', 'api_key', 'api_key'],   
                ['text', 'sts_session', 'token'],    
                ['radio', 'status', '状态','',[0=>'已下架',1=>'已上线'],1],
                ['text', 'c_type', '类型',0],
                // ['text', 'mac_address', 'mac地址'],
                ['text', 'comment', '备注'],
                   
            ])
            
            // ->setTrigger('type', 2, 'logo')
            ->setFormData($info)
            ->fetch();
    }


}