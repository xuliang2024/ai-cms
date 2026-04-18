<?php
//微软语音记录表
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AzureUser extends Admin {
	
    public function index() 
    {
        $order = $this->getOrder('time desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_azure_user_list')->where($map)
        ->order($order)
        ->paginate();

        cookie('ai_azure_user_list', $map);
        
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            ->setTableName('azure_user') // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['dayid','日期'],
                    ['user_id','用户'],
                    
                    ['status', '状态','status','',[0=>'创建',2=>'完成',3=>'失败']],
                    ['url', '输出音频','image_video'],
                    // ['textContent','文本'],
                    ['textContent', '文本', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                    ['textlen','文本长度'],
                    ['voiceName', 'voiceName'],
                    ['style', 'style'],
                    ['role', 'role'],
                    ['rate', 'rate'],
                    ['pitch', 'pitch'],
                    ['privAudioDuration', '时长'],

                    ['time', '创建时间'],
                   
            ])
            // ->hideCheckbox()
            ->addTopButton('download', [
                'title' => '导出文档',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/drawing/azure_user/download.html?' . $this->request->query()
                // 'href'  => url('download',['pid' => '__id__'])
            ]) 
            ->setSearchArea([  
                ['daterange', 'dayid', '时间'], 
                ['text', 'user_id', '用户', '', '', ''],
                ['select', 'status', '状态', '', '', ['0'=>'创建','2'=>'完成','3'=>'失败']],
               
            ])
            // ->addRightButtons($contro_right_btn) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
        public function download() {
         // 获取ids参数
    $ids = input('get.ids');
    // 将ids字符串分割为数组
    $ids_array = explode(',', $ids);
    
    // 查询数据库
    $data_list = DB::table('ai_azure_user_list')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['dayid', 10, 'dayid'],
            ['user_id', 10, 'user_id'],
            ['status', 10, 'status'],
            ['textlen', 10, 'textlen'],
            
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出微软语音记录表'.date('Y-m-d H:i:s'), $cellName, $data_list]);

       

    }

  
}