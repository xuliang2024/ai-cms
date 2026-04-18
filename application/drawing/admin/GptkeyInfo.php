<?php
//获取gptkey
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use GuzzleHttp\Client;

class GptkeyInfo extends Admin {
    
    protected $userId = 76324;
    protected $apiKey = '91ac72921c924c769a03d533311cfc13';
    // protected $baseUrl = 'https://api.gpt-ai.live/api/token/';
    protected $baseUrl = 'http://api-gpt.fyshark.com/api/token/';



    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_gptkey_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_gptkey_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('gptkey_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['gptkey', 'gptkey'],
                ['status', '状态'],
                ['time', '创建的时间'],
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([  
                ['text', 'gptkey', 'gptkey', '', '', ''],
                ['select', 'status', '状态', '', '', ['0'=>'未激活','1'=>'已激活']],
            
              
            ])
            ->setHeight('auto')
            // ->addTopButton('delete')
            //添加7天的按钮
            ->addTopButton('self',[
                'title'=>'添加10个5刀的gptkey',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add1daycode')
            ])

            ->addTopButton('self',[
                'title'=>'添加10个10刀的gptkey',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add1daycode10')
            ])

            ->addTopButton('download', [
                'title' => '导出key',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/drawing/gptkey_info/download.html?' . $this->request->query()
                // 'href'  => url('download',['pid' => '__id__'])
            ])                
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
    $data_list = DB::table('ai_gptkey_info')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['gptkey', 50, 'gptkey'],
           
            
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出getkey'.date('Y-m-d H:i:s'), $cellName, $data_list]);

       

    }

    public function add1daycode(){
        $data = [];
         for ($i = 0; $i < 10; $i++) {
            $this->createCode();
            $key = $this->getCode();
            $code = "sk-".$key;
            echo "sk-" . $key . "\n";
            $data[$i]['gptkey'] =$code;
        }

        $res = Db::table('ai_gptkey_info')->insertAll($data);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
       
    }
    
    public function add1daycode10(){
        $data = [];
         for ($i = 0; $i < 10; $i++) {
            $this->createCode10();
            $key = $this->getCode();
            $code = "sk-".$key;
            echo "sk-" . $key . "\n";
            $data[$i]['gptkey'] =$code;
        }

        $res = Db::table('ai_gptkey_info')->insertAll($data);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
       
    }


       public function createCode()
    {
        $client = new Client();
        // $sevenDaysFromNow = new \DateTime('+7 days');
        $sevenDaysFromNow = new \DateTime('2099-12-31');
        $expiredTimeTimestamp = $sevenDaysFromNow->getTimestamp();
        
        $data = [
            'name' => (string)$this->userId,
            // 'remain_quota' => 10000000,
            'remain_quota' => 2500000,
            'expired_time' => $expiredTimeTimestamp,
            'unlimited_quota' => false
        ];

        $response = $client->request('POST', $this->baseUrl, [
            'headers' => $this->getHeaders(),
            'json' => $data
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        } else {
            return null;
        }
    }

       public function createCode10()
    {
        $client = new Client();
        // $sevenDaysFromNow = new \DateTime('+7 days');
        $sevenDaysFromNow = new \DateTime('2099-12-31');
        $expiredTimeTimestamp = $sevenDaysFromNow->getTimestamp();
        
        $data = [
            'name' => (string)$this->userId,
            'remain_quota' => 5000000,
            // 'remain_quota' => 2500000,
            'expired_time' => $expiredTimeTimestamp,
            'unlimited_quota' => false
        ];

        $response = $client->request('POST', $this->baseUrl, [
            'headers' => $this->getHeaders(),
            'json' => $data
        ]);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        } else {
            return null;
        }
    }

    public function getCode()
    {
        $client = new Client();
        $response = $client->request('GET', $this->baseUrl . '?p=0', [
            'headers' => $this->getHeaders()
        ]);

        if ($response->getStatusCode() == 200) {
            $res = json_decode($response->getBody()->getContents(), true);
            $data = $res['data'];
            foreach ($data as $item) {
                if ($item['name'] == (string)$this->userId) {
                    return $item['key'];
                }
            }
        } else {
            return null;
        }
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey
        ];
    }




  
}