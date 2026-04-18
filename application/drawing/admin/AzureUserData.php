<?php
//用户微软语音日报
namespace app\drawing\admin;


use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class AzureUserData extends Admin {
	

    public function index() 
    {
        $order = $this->getOrder('dayid desc');
        $map = $this->getMap();
        
        $data_list = DB::table('ai_azure_user_data')->where($map)
        ->order($order)
         ->paginate()->each(function($item, $key){


            

            if (empty($item["num_rat"])) {
                // 字符串为空
                
            } else {
                // 字符串不为空
                $item["num_rat"] = substr($item["num_rat"], 0, 5);
            }


            if (empty($item["textlen_num_rat"])) {
                // 字符串为空
                
            } else {
                // 字符串不为空
                $item["textlen_num_rat"] = substr($item["textlen_num_rat"], 0, 5);
            }

            return $item;
        });

        cookie('ai_azure_user_data', $map);
        
        
        return ZBuilder::make('table')
            ->setTableName('azure_user_data') // 设置数据表名
            ->addOrder('num,fail_num,textlen_num,num_rat,textlen_num_rat') // 添加排序
            ->addColumns([ // 批量添加列
                ['dayid', 'dayid'],
                    ['user_id', 'user_id'],
                    ['num','使用次数'],
                    ['num_rat','次数占比'],
                    ['fail_num', '失败次数'],
                    ['textlen_num', '文字长度'],
                    ['textlen_num_rat', '文字占比'],
                    // ['time', '创建时间'],
                   
            ])
            // ->hideCheckbox()
            ->addTopButton('download', [
                'title' => '导出文档',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/drawing/azure_user_data/download.html?' . $this->request->query()
                // 'href'  => url('download',['pid' => '__id__'])
            ]) 
            ->addTopButton('delete') // 批量添加顶部按钮
            ->setSearchArea([
                ['daterange', 'dayid', '时间'],   
                ['text', 'user_id', 'user_id', '', '', ''],
            ])
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
    $data_list = DB::table('ai_azure_user_data')->whereIn('id', $ids_array)->select();
    

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            
            ['dayid', 10, 'dayid'],
            ['user_id', 10, 'user_id'],
            ['num', 10, '使用次数'],
            ['num_rat', 10, '次数占比'],
            ['fail_num', 10, '失败次数'],
            ['textlen_num', 10, '文字长度'],
            ['textlen_num_rat', 10, '文字占比'],
            
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出微软语音日报'.date('Y-m-d H:i:s'), $cellName, $data_list]);

       

    }

  
}