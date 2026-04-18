<?php
// sd算力云链接
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ComputeLinks extends Admin {
    
    public function index() 
    {
     
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_compute_links')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_compute_links', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ComputeLinksModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['machine_id', '云机器ID'],
                    ['sd_url', 'sd算力云链接地址'],
                    ['billing_type','机器类型'],
                    ['status','status','switch'],
                    ['gpu_type','GPU类型'],

                    ['type_cephalon_name','镜像名称'],
                    ['status_cephalon','镜像状态'],
                    ['username_cephalon','镜像用户名'],
                    ['password_cephalon','镜像密码'],
                    ['note', '备注信息'],
                    ['source','链接来源'],
                    ['total_consume','消耗脑值'],
                    ['created_at','机器创建时间'],
                    ['update_time','本地更新时间'],
                    ['source_int', '类型','status','',[0=>'手动添加',1=>'api开机']],
                    ['time','time'],
                    ['right_button', '操作', 'btn']
                   
                    
            ])
           
            ->setSearchArea([  
                ['text', 'status', 'status'],
                ['text', 'machine_id', '云机器ID'],
                 
            ])

            
            ->addTopButton('add',['title'=>'新增机器'])
            
            ->addTopButton('close_cephalon', [
                'title' => '关闭机器',
                'class' => 'btn btn-danger js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/compute_links/close_cephalon.html?' . $this->request->query()
                // 'href'=>url('close_cephalon?'.$this->request->query(),['machine_id'=>$groupId]),
                // 'href'  => url('download',['pid' => '__id__'])
            ]) 

            // ->addTopButton('get_cephalon_list', [
            //     'title' => '同步线上机器(注意有排队中的情况)',
            //     'class' => 'btn btn-primary js-get',
            //     'icon' => 'fa fa-fw fa-file-excel-o',
            //     'href' => '/admin.php/video/compute_links/get_cephalon_list.html?'
            //     // 'href'  => url('download',['pid' => '__id__'])
            // ])
            ->addTopButton('get_cephalon_list',[
                'title'=>'同步线上机器(注意有排队中的情况)',
                'icon'=>'fa fa-plus',
                'class'=>'btn btn-success btn-sm',
                'href'=>url('get_cephalon_list')
            ]) 
            ->addTopButton('delete',['title'=>'只删除本地机器']) // 批量添加顶部按钮
              
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

            if ($data["source_int"]==1) {

                $this->open_cephalon($data["batch_size"],$data["gpu_type"],$data["type_cephalon"]);

                

            }else{
                $r = DB::connect('translate')->table('ts_compute_links')->insert($data);
                if ($r) {
                    $this->success('新增成功', 'index');
                } else {
                    $this->error('新增失败');
                }

            } 


        }

                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addRadio('source_int', '开机方式', '', ['0' => '手动添加', '1' => 'api开机'], '1')
            ->addFormItems([

                ['text', 'batch_size', '开机数量'],
                ['text', 'type_cephalon', '开机镜像名称','sd_lang_time;sd_xiao_chun_time','sd_lang_time'],
                ['text', 'gpu_type', 'gpu类型','RTX3090','RTX3090'],

                ['text', 'sd_url', 'sd算力云链接地址'],
                ['text', 'note', '备注信息'],
                ['text', 'source', '链接来源'],
                // ['switch', 'wx_mchid', 'wx_mchid'],

            ])
            ->setTrigger('source_int', '0', 'sd_url,note,source')
            ->setTrigger('source_int', '1', 'batch_size,gpu_type,type_cephalon')
            ->fetch();
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_compute_links')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_compute_links')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                ['text', 'sd_url', 'sd算力云链接地址'],
                ['text', 'gpu_type', 'gpu类型'],
                ['text', 'note', '备注信息'],
                ['text', 'source', '链接来源'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }


   function open_cephalon($batch_size,$gpu_type,$type_cephalon){

        $ch = curl_init();
        
        // 准备请求头，假设需要一个'Authorization'头，你可能需要添加它
        $headers = array(
            'Content-Type: application/json'
        );

        // 准备POST数据，包括cephalon_id数组和type参数
        $postData = array(
            "batch_size" =>  (int)$batch_size,
            "gpu_version" => $gpu_type,
            "type_cephalon" => $type_cephalon
        );
        $data = json_encode($postData);

        curl_setopt($ch, CURLOPT_URL, "https://ts-api.fyshark.com/api/open_cephalon");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        
        if(curl_errno($ch)){
            // 如果在执行过程中出现错误，可以在这里处理，例如记录日志等
            // 错误信息：curl_error($ch);
            curl_close($ch);
            return null; // 或者返回或抛出一个错误信息
        }

        curl_close($ch);    
        $this->success('开机成功', 'index');   

    }


  public  function get_cephalon_list(){


    $ch = curl_init();
    
    // 准备请求头，假设需要一个'Authorization'头，你可能需要添加它
    $headers = array(
        'Content-Type: application/json'
    );

    curl_setopt($ch, CURLOPT_URL, "https://ts-api.fyshark.com/api/get_cephalon_list");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    
    if(curl_errno($ch)){
        // 如果在执行过程中出现错误，可以在这里处理，例如记录日志等
        // 错误信息：curl_error($ch);
        curl_close($ch);
        return null; // 或者返回或抛出一个错误信息
    }

    curl_close($ch);
    $this->success('同步完成，注意有排队或者开机中的机器', 'index');
    // return json_decode($result, true);
}

  public  function close_cephalon(){

    $ids = input('get.ids');

     // 将ids字符串分割为数组
    $cephalon_ids = explode(',', $ids);


    if(empty($cephalon_ids) ){
        $this->error('请勾选需要关闭的机器');
    }

       // 查询数据库
    $data_list = DB::connect('translate')->table('ts_compute_links')->whereIn('id', $cephalon_ids)->select();
    
    // 使用array_column从结果数组中提取machine_id列
    $machine_ids = array_column($data_list, 'machine_id');
    // print($);
    // print_r($machine_ids);
    // die();


    $ch = curl_init();
    
    // 准备请求头，假设需要一个'Authorization'头，你可能需要添加它
    $headers = array(
        'Content-Type: application/json'
    );

    // 准备POST数据
    $data = json_encode(array("cephalon_id" => $machine_ids));

    curl_setopt($ch, CURLOPT_URL, "https://ts-api.fyshark.com/api/close_cephalon");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    
    if(curl_errno($ch)){
        // 如果在执行过程中出现错误，可以在这里处理，例如记录日志等
        // 错误信息：curl_error($ch);
        curl_close($ch);
        return null; // 或者返回或抛出一个错误信息
    }

    curl_close($ch);
    // print($result);
    // die();
    $this->success('关机完成', 'index');
    // return json_decode($result, true);
}



}

