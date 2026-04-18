<?php
// 小说制作
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;



class BookList extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_book_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_book_list', $map);
        
        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        return ZBuilder::make('table')
            ->setTableName('video/BookListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['video_url', '参考图', 'image_video'],
                ['status', '状态','text.edit'],
                ['name', 'name'],

                ['time', '创建时间'],
                ['redraw_cnt', '重绘次数'],
                ['right_button', '操作', 'btn'],
                ['style_id', '风格ID','text.edit'],
                ['text_gpt_ai_id', 'text_gptid'],
                ['prompt_gpt_ai_id', 'prompt_gptid'],
                
                // ['pre_prompt', '默认前缀'],
                // ['after_prompt', '默认后缀'],

                // ['negative_prompt', '默认负面', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                // // ['cmd', 'cmd'],
                // ['cmd', 'cmd', 'callback', function($source_text) {
                //     // 限制字符串长度为50个字符
                //     return mb_strimwidth($source_text, 0, 50, '...');
                // }],
                // ['param_config', 'param_config'],
                // ['draw_type', '绘画类型','status','',[0=>'sd',1=>'mj',2=>'dall3']],
                ['bgm_url', 'bgm_url', 'image_video'],
                ['score_int', '作品评分'],
                // ['first_img', '第一帧的图片'],
                // ['first_title', '第一帧的文本'],
                ['progress_cnt', '进度'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'user_id', 'user_id'],
                ['text', 'id', 'id'],
                ['text', 'status', '状态'],
                ['text', 'style_id', '风格ID'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            // ->addRightButton('custom',[
            //     'title'=>'查看详情',
            //     'icon'=>'fa fa-fw fa-bus',
            //     'class'=>'btn btn-info btn-rounded',
            //     'href'=>url('video/book_list_detail/index',['book_id'=>'__id__']),
            // ],false,['style'=>'primary','title' => true,'icon'=>false])

             ->addRightButton('info',[
                'title'=>'查看详情',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                'href'=>'/admin.php/video/book_list_detail/index.html?_s=time=|book_id=__id__|index=|mp3_status=|gpt_status=|draw_status=|video_status=|scene_status=&_o=time=between%20time|book_id=eq|index=eq|mp3_status=eq|gpt_status=eq|draw_status=eq|video_status=eq|scene_status=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])

       

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
        $data_list_time = DB::connect('translate')->table('ts_book_list')
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
