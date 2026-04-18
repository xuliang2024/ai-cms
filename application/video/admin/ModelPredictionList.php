<?php
// 支付订单列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ModelPredictionList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_model_predictions')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_model_predictions', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ModelPredictions',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['time', '创建时间'],
                    ['user_id','用户id'],
                    ['task_id','task_id'],
                    ['model', '模型'],
                    ['status', '状态'],
                    ['metrics', '消耗'],
                    // ['output', '输出'],
                    
                    
                    
            ])
           
            ->setSearchArea([  
                ['text', 'user_id', '用户id'],
                ['daterange', 'time', '时间'],   
                
               
            ])
           
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


   



}
