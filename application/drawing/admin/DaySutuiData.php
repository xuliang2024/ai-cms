<?php
//速推日报
namespace app\drawing\admin;
    
use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
    
class DaySutuiData extends Admin {
	   
    public function index() 
    {
        $map = $this->getMap();
        $data_list = DB::table('ai_app_day')->where($map)
        ->order('dayid desc')
        ->paginate()->each(function($item, $key){
          

          $item["fail_drawing_rat"] = $item["fail_drawing_rat"]*100 . "%";

          if ($item["fail_collection_rat"]!='') {
            // code...
            $item["fail_collection_rat"] = $item["fail_collection_rat"]*100 . "%";
          }

         
          return $item;
        });


        cookie('ai_app_day', $map);

        // $js = $this->getChartjs($data_list);
        // $content_html =  "";
        // if($js != ""){
        //     $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        // }

 
        return ZBuilder::make('table')
            ->setTableName('app_day') // 设置数据表名
            ->addColumns([ // 批量添加列
                // ['id', 'ID'],
                ['dayid','日期'],
                ['add_user_cnt','新增用户'],
                ['active_user_cnt', '活跃用户'],
                
                ['succeed_collection_cnt', '绘画合集数'],
                ['fail_collection_cnt', '绘画合集失败'],
                ['fail_collection_rat', '作画合集失败率'],

                ['add_drawing_cnt', '绘画任务数'],
                ['fail_drawing_cnt', '绘画任务失败'],
                ['fail_drawing_rat', '作画任务失败率'],
        
                // ['comment', '备注'],  
                
                // ['time', '创建时间'],
                
            ])
            ->hideCheckbox()
            // ->js("libs/echart/echarts.min")
            // ->setExtraHtml($content_html, 'toolbar_top')
            // ->setExtraJs($js)
            ->setRowList($data_list) // 设置表格数据
            ->setSearchArea([
                 ['daterange', 'dayid', '时间'],   
                
            ])
            ->setHeight('auto')
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->fetch(); // 渲染页面

    }

   public function getChartjs($data_list){

        $x_data = array();

        $lowbackbox_price = array();//作品参与率
        $lowbackbox_pay = array();//充值参与率
        $lowbackbox_ad = array();//广告参与率

    
        foreach ($data_list as $value) {

                       
            $day_time = date_create( $value["time"]);
            $js_day = date_format($day_time,'m-d');

            array_push($x_data,$value["dayid"]);
            // array_push($x_data,$js_day);
            
            array_push($lowbackbox_price,$value["user_new_cnt"]);          
            array_push($lowbackbox_pay,$value["img_cnt_dis"]);          
            array_push($lowbackbox_ad,$value["avg_draw_cnt"]);          
            
        }


        $lowbackbox_price = json_encode(array_reverse($lowbackbox_price));   
        $lowbackbox_pay = json_encode(array_reverse($lowbackbox_pay));   
        $lowbackbox_ad = json_encode(array_reverse($lowbackbox_ad));   

        $x_datas = array_unique($x_data);
        $indexed_numbers = array_values($x_datas);
        $indexed_numbers = json_encode(array_reverse($indexed_numbers));

       $js = "
      <script type='text/javascript'>
       var myChart = echarts.init(document.getElementById('main'));

var option;
option = {
  title: {
    text: '新用户趋势'
  },
  tooltip: {
    trigger: 'axis'
  },
  legend: {
    data: ['新增用户','作画人数','人均作画']
  },
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  toolbox: {
    feature: {
      saveAsImage: {}
    }
  },
  xAxis: {
    type: 'category',
    boundaryGap: false,
    data: {$indexed_numbers}
  },
  yAxis: {
    type: 'value'
  },

  series: [
    
    {
      name: '新增用户',
      type: 'line',
      
      data: {$lowbackbox_price}
    },
    {
      name: '作画人数',
      type: 'line',
      
      data: {$lowbackbox_pay}
    },
    {
      name: '人均作画',
      type: 'line',
      
      data: {$lowbackbox_ad}
    }
    
    
  ]
};


myChart.setOption(option);

       </script>";

    
    return $js;

    }





  
}