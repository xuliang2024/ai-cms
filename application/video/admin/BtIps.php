<?php
// 应用列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BtIps extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();

        // 统计数据
        $total = DB::connect('translate')->table('ts_bt_ips')->count();
        $checked_total = DB::connect('translate')->table('ts_bt_ips')->where('check_status', 1)->count();
        $deployed_total = DB::connect('translate')->table('ts_bt_ips')->where('is_cmd', 1)->count();
        $recent_updated_total = DB::connect('translate')->table('ts_bt_ips')->where('up_time', '>=', date('Y-m-d H:i:s', strtotime('-10 minutes')))->count();
        $online_total = DB::connect('translate')->table('ts_bt_ips')->where('status', 1)->count();

         // 生成HTML内容
        $content_html = "
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th scope='col'>总数量</th>
                    <th scope='col'>已部署</th>
                    <th scope='col'>已校验</th>
                    <th scope='col'>最近更新</th>
                    <th scope='col'>总在线</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$total}</td>
                    <td>{$deployed_total}</td>
                    <td>{$checked_total}</td>
                    <td>{$recent_updated_total}</td>
                    <td>{$online_total}</td>
                </tr>
            </tbody>
        </table>
    ";

        
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_bt_ips')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_bt_ips', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BtIpsModel',2) // 设置数据表名
             ->addColumns([ // 批量添加列
                    ['id', 'id'],
                    ['ip', 'ip'],
                    ['address','地址'],
                    ['word','词'],
                    ['status', '状态','switch'],
                    ['check_status', '校验','switch'],
                    ['point','积分'],
                    ['is_cmd', '部署','switch'],
                    ['flag', '同步','switch'],
                    // ['comment','备注'],
                    
                    // ['key', 'key', 'callback', function($source_text) {
                    // // 限制字符串长度为50个字符
                    // return mb_strimwidth($source_text, 0, 50, '...');
                    // }],

                    ['region','地区'],
                    ['instance_name','名字'],
                    ['yun_name','云'],
                    ['secret_id','云ID'],
                    
                    
                    ['pubkey','公钥'],

                    ['time','创建时间'],
                    ['up_time','更新时间'],
                    ['right_button', '操作', 'btn']
                   
                   
            ])  
            ->setSearchArea([ 
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['daterange', 'up_time', '更新时间', '', '', ['format' => 'YYYY-MM-DD']], 
                ['text', 'ip', 'ip'],
                ['text', 'flag', 'flag'],
                ['text', 'key', 'key'],
                ['text', 'status', 'status'],
                ['text', 'check_status', 'check_status'],

                ['text', 'region', 'region'],
                ['text', 'instance_name', 'instance_name'],
                ['text', 'yun_name', 'yun_name'],
                ['text', 'address', 'address'],
                ['text', 'word', 'word'],
                ['text', 'pubkey', 'pubkey'],
                
        
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setColumnWidth([
                'word' => 300 // 将'word'列的宽度设置为200px
            ])
            ->addRightButton('edit',[
                'title'=>'查看详情',
                'icon'=>'fa fa-fw fa-search-plus'
            ]) // 添加右侧按钮
            
            // ->addRightButton('detector',[
            //     'title'=>'查看ip日志',
            //     'icon'=>'fa fa-fw fa-location-arrow',
            //     'href'=>url('detector/index',['type_id'=>'__id__'])
            // ]) 

            // ->addRightButton('edit',[
            //     'title'=>'查看详情',
            //     'class'=>'btn btn-success btn-rounded',
            // ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

             ->addRightButton('info',[
                'title'=>'查看ip日志',
                'icon'  => 'fa fa-fw fa-location-arrow',
                
                'href'=>'/admin.php/video/bt_ips_log/index.html?_s=time=|ip=__ip__|res_log=|run_log=&_o=time=between%20time|ip=eq|res_log=eq|run_log=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ])
            
             

            ->setRowList($data_list) // 设置表格数据
            // ->setColumnWidth('key', 250)
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');


        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_bt_ips')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


      

        $info = DB::connect('translate')->table('ts_bt_ips')->where('id',$id)->find();

        return ZBuilder::make('form')    
                ->addFormItems([
                
                ['text', 'id', 'id'],       
                ['text', 'ip', 'ip'],      
                ['textarea', 'key', 'key'],             
               
                ['radio', 'check_status', 'check_status', '', ['否', '是']],

                ['text', 'region', 'region'],
                ['text', 'instance_name', 'instance_name'],
                ['text', 'yun_name', 'yun_name'],
                ['text', 'address', 'address'],
                ['text', 'word', 'word'],
                ['text', 'pubkey', 'pubkey'],        
                ['text', 'time', 'time'],      
                ['text', 'up_time', '更新时间'],      
               
            ])

        
            ->setFormData($info)
            // ->hideBtn('submit')

            ->fetch();
    }




}

