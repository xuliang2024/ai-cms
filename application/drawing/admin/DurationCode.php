<?php
//时长码
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class DurationCode extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_duration_code_info')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_duration_code_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('duration_code_info') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['activation_code_info', '激活码'],
                ['mac_address','mac'],
                ['status', '状态'],
                ['hour', '激活时长'],
                ['activate_time', '激活时间'],
                ['time', '创建时间'],
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([  
                ['text', 'activation_code_info', '激活码', '', '', ''],
                ['select', 'status', '状态', '', '', ['0'=>'未激活','1'=>'已激活']],
                ['text', 'hour', '时长', '', '', ''],
                ['text', 'mac_address', 'mac', '', '', ''],
              
            ])
            ->setHeight('auto')
            // ->addTopButton('delete')
            ->addTopButton('self',[
                'title'=>'添加1小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add1hour')
            ])
            ->addTopButton('self',[
                'title'=>'添加2小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add2hour')
            ])
            ->addTopButton('self',[
                'title'=>'添加4小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add4hour')
            ])
            ->addTopButton('self',[
                'title'=>'添加12小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add12hour')
            ])
            ->addTopButton('self',[
                'title'=>'添加24小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add24hour')
            ])
            ->addTopButton('self',[
                'title'=>'添加48小时码',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('add48hour')
            ])
            ->fetch(); // 渲染页面

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

    
    //添加7天的激活码
    public function add7daycode(){
        $this->adddaycode(1);
    }

    public function adddaycode($hour=1){
        $num = 20;
        $status = 0;
        $data = [];
        for($i=0;$i<$num;$i++){
            $code = $this->createUuid();
            $data[$i]['activation_code_info'] = $code;
            $data[$i]['status'] = $status;
            $data[$i]['hour'] = $hour;
        }
        $res = Db::table('ai_duration_code_info')->insertAll($data);
        if($res){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function add1hour(){
        $this->adddaycode(1);
    }
    
    public function add2hour(){
        $this->adddaycode(2);
    }

    public function add4hour(){
        $this->adddaycode(4);
    }
    
    public function add12hour(){
        $this->adddaycode(12);
    }

    public function add24hour(){
        $this->adddaycode(24);
    }

    public function add48hour(){
        $this->adddaycode(48);
    }

}