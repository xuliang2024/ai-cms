<?php
//绘画记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class StyleList extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_style_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ai_style_list', $map);
         // 下截
        $btn_download = [
            'title' => '导出团队信息',
            'icon'  => 'fa fa-fw fa-download',
            'class' => 'btn btn-success',
            'href'  => url('download')
        ];          
        return ZBuilder::make('table')
            ->setTableName('style_list') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name','名字'],
                ['img', '封面','img_url'],
                ['model_id', '大模型','text.edit'],
                ['style', '风格词'],
                ['style_type', '风格类型',['0' => '默认', '1' => '人物动漫化','2' => '五一活动','9'=>'换装风格']],
                ['Denoising',  '重绘幅度'],
                ['sort', '排序','text.edit'],
                ['content', '备注'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                    
            ])
            // ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('btn_download', $btn_download) // 添加授权按钮
            ->setHeight('auto')
            ->addTopButtons(['add','delete'])
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }

       //下载
    public function download() {
        // echo 'ss';
        print_r('打印');
        // $map   = cookie('book_user_info');
        // $map = $this->getMap();

        // $order = ''; //$this->getOrder('customer_number desc');
        // $data_list = BoxdeviceinfoModel::getListDownload($map, $order);

        $data_list = DB::table('ai_style_list')->find();

        print_r($data_list);

        // foreach ($data_list as $key => &$value) {

        // }

        

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id', 10, '用户ID'],
            ['name', 10, '用户名字'],
            // ['team_id', 10, '团队ID'],
            // ['bonus_already_money', 10, '已分成金额'],
            // ['pay_money', 10, '订单金额'],
            // ['earnings_money', 10, '推广佣金'],
            // ['invite_money', 10, '邀请奖励'],
           

        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['导出团队信息', $cellName, $data_list]);



    }

    public function add() 
     {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            $r = DB::table('ai_style_list')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $order_ids =$this->getBigmodels();
                   
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('img', '封面', '', '', '', '', '', ['size' => '50,50'])
            ->addFormItems([
                ['text', 'name', '名字'],
                ['textarea', 'style', '风格词'],   
                ['select', 'model_id', '模型', '请选择模型', $order_ids],              
                ['select', 'style_type', '风格类型','',['0' => '默认', '1' => '人物动漫化','2' => '五一活动','9'=>'换装风格'],0],        
                ['text', 'Denoising', '重绘幅度'],
                ['textarea', 'content', '备注'],     
                
            ])
            ->fetch();
    }

    public function getBigmodels(){
        $order_ids = array();
            // $datas = DB::table('backmarket_order_line_info_tab')->select();
        $datas = DB::query("select id ,name ,status from ai_big_models_list where status = 1;");
        foreach ($datas as $data) {
            $data =array( $data["id"] => $data["name"]);     
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

            $r = DB::table('ai_style_list')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $order_ids =$this->getBigmodels();

        $info = DB::table('ai_style_list')->where('id',$id)->find();

        return ZBuilder::make('form')
              ->addOssImage('img', '图片', '', '', '', '', '', ['size' => '50,50'])
             ->addFormItems([
                ['text', 'name', '名字'],
                ['textarea', 'style', '风格词'],                 
                ['select', 'model_id', '模型', '请选择模型', $order_ids],       
                 ['select', 'style_type', '风格类型','',['0' => '默认', '1' => '人物动漫化','2' => '五一活动','9'=>'换装风格'],0],         
                 ['text', 'Denoising', '重绘幅度'],
                ['textarea', 'content', '备注'],       
            ])
          
            ->setFormData($info)
            ->fetch();
    }



    
 

  
}