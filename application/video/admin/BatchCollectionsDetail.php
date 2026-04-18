<?php
// 
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BatchCollectionsDetail extends Admin {
    
    public function index() 
    {
     
        
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_batch_collections_detail')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_batch_collections_detail', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BatchCollectionsDetailModel',2) // 设置数据表名
             ->addColumns([ // 批量添加列
                    ['id', 'id'],
                     ['title','title'],
                    ['collection_id','collection_id'],
                   
 
                    ['collection_detail_id','collection_detail_id'],
                    ['user_id', 'user_id'],


                    // ['value', 'value'],
                    ['value', 'value', 'callback', function ($val, $data) {
                        $val = sprintf(self::$ellipsisElement, $val, $val);
                        return $val;
                    }, '__data__'],

                    // ['result','result'],

                    ['result', 'result', 'callback', function ($val, $data) {
                        $val = sprintf(self::$ellipsisElement, $val, $val);
                        return $val;
                    }, '__data__'],


                   
                   
                    // ['input_value','input_value'],

                    ['input_value', 'input_value', 'callback', function ($val, $data) {
                        $val = sprintf(self::$ellipsisElement, $val, $val);
                        return $val;
                    }, '__data__'],


                    ['time','time'],
                    
                    ['right_button', '操作', 'btn']
                   
                   
            ])  
              ->addRightButton('edit',[
                'title'=>'查看详情',
                'icon'=>'fa fa-fw fa-search-plus'
            ]) // 添加右侧按钮

              
            ->setSearchArea([ 
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']], 
                
                ['text', 'title', 'title'],
                ['text', 'user_id', 'user_id'],
                ['text', 'collection_id', 'collection_id'],

                
                
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');


        // if ($this->request->isPost()) {
        //     // 表单数据
        //     $data = $this->request->post();

        //     $r = DB::connect('translate')->table('ts_batch_collections_detail')->where('id',$id)->update($data);
        //     if ($r) {
        //         $this->success('编辑成功', 'index');
        //     } else {
        //         $this->error('编辑失败');
        //     }
        // }


      

        $info = DB::connect('translate')->table('ts_batch_collections_detail')->where('id',$id)->find();

        return ZBuilder::make('form')    
                ->addFormItems([
                
                ['text', 'id', 'id'],       
                ['text', 'user_id', 'user_id'],      
                ['text', 'title', 'title'],      
                ['text', 'collection_id', 'collection_id'],      
                ['text', 'collection_detail_id', 'collection_detail_id'],      
                ['text', 'user_id', 'user_id'],      
                ['textarea', 'value', 'value'],             
                ['textarea', 'result', 'result'],             
                ['textarea', 'input_value', 'input_value'],             
               
                  
                ['text', 'time', 'time'],      
               
               
            ])

        
            ->setFormData($info)
            ->hideBtn('submit')

            ->fetch();
    }



}

