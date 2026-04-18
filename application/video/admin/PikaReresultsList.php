<?php
// pika视频列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class PikaReresultsList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_pika_reresults_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_pika_reresults_list', $map);
        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }
        
        return ZBuilder::make('table')
            ->setTableName('video/PikaReresultsListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['type_video', '类型','status','',[0=>'pika',1=>'pixverse']],
                    ['put_id', '上传ID'],
                    ['user_id', 'user_id'],
                    ['prompt_text','描述词'],

                    ['result_url','视频效果','image_video'],
                    ['video_poster','视频封面','img_url'],
                    ['image_thumb','原图','img_url'],
                    ['adjusted', '是否调整'],
                    ['upscaled', '是否高清'],
                    ['extended','延长次数'],

                    ['videos', 'videos', 'callback', function ($val, $data) {
                    $val = sprintf(self::$ellipsisElement, $val, $val);
                    return $val;
                    }, '__data__'],

                    ['params', 'params', 'callback', function ($val, $data) {
                    $val = sprintf(self::$ellipsisElement, $val, $val);
                    return $val;
                    }, '__data__'],


                    ['time','time'],
                   
                    
            ])
           
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'put_id', '上传ID'],
                ['text', 'user_id', 'user_id'],
                ['select', 'type_video', '类型', '', '', ['0'=>'Pika','1'=>'pixverse']],
                
                
               
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($js)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    public function getChartjs() {
        // 1. 获取参数
        $queryParameters = $this->request->get();
    
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'time=') === 0) {
                    $daterangeValue = substr($param, strlen('time='));
                    break;
                }
            }
        }
    
        // 2. 解析开始时间和结束时间
        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            // 如果没有提供日期范围，则使用当前日期
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }
    
        // 判断是否为同一天
        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
    
           // 3. 数据库查询并按日期或小时分组计数
           $groupBy = $isSameDay ? 'HOUR(time)' : 'DATE(time)';
           $xAxisType = $isSameDay ? '小时' : '日期';

           // $groupBy2 = $isSameDay ? 'HOUR(time)' : 'DATE(last_login_time)';
                    // 3.1 查询基于 time 的数据（原有逻辑）
        $data_list_time = DB::connect('translate')->table('ts_pika_reresults_list')
        ->whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select();

        // 3.2 查询基于 last_login_time 的数据
        // $data_list_last_login = DB::connect('translate')->table('ts_book_list')
        // ->whereTime('last_login_time', 'between', [$startDate, $endDate])
        // ->field([
        //     "{$groupBy2} as axisValue",
        //     'COUNT(*) as activeCount'
        // ])
        // ->group('axisValue')
        // ->select();

        // 初始化数组
        $x_data = array();
        $y_data_time = array();  // 对于 time 字段
        // $y_data_last_login = array();  // 对于 last_login_time 字段

        // 遍历查询结果
        foreach ($data_list_time as $value) {
        // 添加到 x 轴和 y 轴数据 (time 字段)
        array_push($x_data, $value['axisValue']);
        array_push($y_data_time, $value['count']);
        }

        // foreach ($data_list_last_login as $value) {
        // // 添加到 y 轴数据 (last_login_time 字段)
        // array_push($y_data_last_login, $value['activeCount']);
        // }



        // JSON 编码数据以用于 JavaScript
        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        // $y_data_last_login_json = json_encode($y_data_last_login);

        // 创建 ECharts JavaScript 代码
        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['新增']
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
                data: {$x_data_json}
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: '新增',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }

}

