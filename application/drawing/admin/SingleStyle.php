<?php
//单风格记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SingleStyle extends Admin {
	
 
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_single_style')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_single_style', $map);
                
        return ZBuilder::make('table')
            ->setTableName('single_style') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name','名字'],
                ['img', '封面','img_url'],
                ['model_id', '模型ID'],
                 // ['model_name', '模型名字'],
                ['style_id', '风格ID'],
                ['style_name', '风格名字'],
                ['jpath', '配置路径'],
                // ['style', '风格词'],
                ['status', '状态',['0' => '已下架', '1' => '已上线']],
                ['show_vip', '会员展示',['0' => '不展示', '1' => '展示']],
                // ['money_week',  '周卡价格'],
                ['shop_id',  '充值模版id'],
                // ['week_text',  '周卡文案'],      
                // ['comment', '备注'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                    
            ])
            // ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButtons(['add'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面
          
    }
         

    public function add() 
     {


        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            if ($data["style_id"]!='') {
              $data_lora = DB::query('select  name ,model_id,style from ai_style_list where id = "'.$data["style_id"].'";');
              
              $style_name='';
              $model_id='';
              $style='';
              
              
              foreach ($data_lora as $value) {
                  // code...
                 $style_name=$value["name"];
                 $model_id=$value["model_id"];
                 $style=$value["style"];
              }

            

              $data["style_name"] = $style_name;
              $data["model_id"] = $model_id;
              $data["style"] = $style;
            }else{
                $this->error('请选择风格');

            }            

            if ($data["shop_id"]!='') {
              // $data_lora = DB::query('select  money  from ai_shop_info where id = "'.$data["shop_id"].'";');
              
              // $money_week='';
              
              
              
              
              // foreach ($data_lora as $value) {
              //     // code...
              //    $money_week=$value["money"];
                
                
              // }

            

              // $data["money_week"] = $money_week;
             
              
            }else{
                $this->error('请选择充值模版');

            }       






            $r = DB::table('ai_single_style')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $order_ids =$this->getBigmodels();
        $shop_ids =$this->getShop();
              
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                ['text', 'name', '名字'],
                ['text', 'jpath', '配置路径'],   
                ['select', 'style_id', '风格', '请选择风格', $order_ids],              
                ['select', 'shop_id', '充值', '请选择充值档位', $shop_ids],              
                ['select', 'show_vip', '是否展示会员充值','',['0' => '不展示', '1' => '展示']],        
                // ['text', 'money_week', '会员售卖价格(单位：分)'],
                // ['text', 'week_text', '周卡文案'],
                // ['textarea', 'content', '备注'],  
                ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线'],1],          
                
            ])
            ->fetch();
    }

    public function getBigmodels(){
        $order_ids = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        $datas = DB::query("select id ,name  from ai_style_list;");
        foreach ($datas as $data) {
            $data =array( $data["id"] =>$data["id"]."-".$data["name"]);     
                $order_ids = $order_ids +$data;
        }
        return $order_ids;
    }

    public function getShop(){
        $order_ids = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        $datas = DB::query("select id ,title,money  from ai_shop_info;");
        foreach ($datas as $data) {
            $data =array( $data["id"] =>$data["id"]."-".$data["title"]."-".$data["money"]);     
                $order_ids = $order_ids +$data;
        }
        return $order_ids;
    }

     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
        if ($data["style_id"]!='') {
              $data_lora = DB::query('select  name ,model_id,style from ai_style_list where id = "'.$data["style_id"].'";');
              
              $style_name='';
              $model_id='';
              $style='';
              
              
              foreach ($data_lora as $value) {
                  // code...
                 $style_name=$value["name"];
                 $model_id=$value["model_id"];
                 $style=$value["style"];
              }

            

              $data["style_name"] = $style_name;
              $data["model_id"] = $model_id;
              $data["style"] = $style;
            }else{
                $this->error('请选择风格');

            }   


            if ($data["shop_id"]!='') {
              // $data_lora = DB::query('select  money  from ai_shop_info where id = "'.$data["shop_id"].'";');
              
              // $money_week='';
              
              
              
              
              // foreach ($data_lora as $value) {
              //     // code...
              //    $money_week=$value["money"];
                
                
              // }

            

              // $data["money_week"] = $money_week;
             
              
            }else{
                $this->error('请选择充值模版');

            }       





            $r = DB::table('ai_single_style')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $order_ids =$this->getBigmodels();
        $shop_ids =$this->getShop();

        $info = DB::table('ai_single_style')->where('id',$id)->find();

        return ZBuilder::make('form')
              ->addOssImage('img', '图片', '', '', '', '', '', ['size' => '50,50'])
             ->addFormItems([
               ['text', 'name', '名字'],
                ['text', 'jpath', '配置路径'],   
                ['select', 'style_id', '风格', '请选择风格', $order_ids],              
                ['select', 'show_vip', '是否展示会员充值','',['0' => '不展示', '1' => '展示']],  
                ['select', 'shop_id', '充值', '请选择充值档位', $shop_ids],           
                // ['text', 'money_week', '会员售卖价格(单位：分)'],
                // ['text', 'week_text', '周卡文案'],
                // ['textarea', 'content', '备注'],  
                ['select', 'status', '状态','',['0' => '已下架', '1' => '已上线'],1],        
            ])
          
            ->setFormData($info)
            ->fetch();
    }



    
 

  
}