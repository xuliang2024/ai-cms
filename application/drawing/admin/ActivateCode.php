<?php
//lora模型列表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ActivateCode extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_activation_code_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_activation_code_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('activation_code_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['activation_code_info', '激活码'],
                ['mac_address','mac'],
                ['user_id','user_id'],
                ['status', '状态'],
                ['day', '激活天数'],
                ['time', '创建时间'],
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([  
                ['text', 'activation_code_info', '激活码', '', '', ''],
                ['select', 'status', '状态', '', '', ['0'=>'未激活','1'=>'已激活']],
                ['text', 'day', '天数', '', '', ''],
                ['text', 'user_id', 'user_id', '', '', ''],
                ['text', 'mac_address', 'mac', '', '', ''],
              
            ])
            ->setHeight('auto')
            ->addTopButton('delete')
            //添加7天的按钮
            ->addTopButton('self',[
                'title'=>'添加日卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add1daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加3天卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add3daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加周卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add7daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加月卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add31daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加季卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add93daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加年卡',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add365daycode')
            ])
            ->addTopButton('self',[
                'title'=>'添加永久',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('addforevercode')
            ])
            ->addTopButton('download', [
                'title' => '导出激活码',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/drawing/activate_code/download.html?' . $this->request->query()
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
    $data_list = DB::table('ai_activation_code_info')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['activation_code_info', 50, '激活码'],
            ['day', 10, '激活天数'],
            
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

    public function add1daycode(){
        $this->adddaycode(1);
    }
    
    //添加7天的激活码
    public function add7daycode(){
        $this->adddaycode(7);
    }

    public function adddaycode($day=7){
        $num = 20;
        $status = 0;
        $data = [];
        for($i=0;$i<$num;$i++){
            $code = $this->createUuid();
            $data[$i]['activation_code_info'] = $code;
            $data[$i]['status'] = $status;
            $data[$i]['day'] = $day;
        }
        $res = Db::table('ai_activation_code_info')->insertAll($data);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function add31daycode(){   
        $this->adddaycode(31);    

    }

    public function add3daycode(){   
        $this->adddaycode(3);    
    }

    public function add93daycode(){
        $this->adddaycode(93);
    }
    public function add365daycode(){
        $this->adddaycode(365);

    }
    public function addforevercode(){
        $this->adddaycode(36500);
    }

   




  
}