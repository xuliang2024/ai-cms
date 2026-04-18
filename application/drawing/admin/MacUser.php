<?php
//lora模型列表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MacUser extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        $order = $this->getOrder('time desc');
        $data_list = DB::table('ai_mac_user_info')->where($map)
        ->order($order)
        ->paginate();


        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ai_mac_user_info', $map);
        


        return ZBuilder::make('table')
            ->setTableName('mac_user_info') // 设置数据表名
             ->addOrder('user_id') // 添加排序
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mac_address','mac'],
                ['user_id','user_id'],

                // ['mj_share_switch','是否共享mj图片'],
                // ['draw_cnt','总做图','text.edit'],
                // ['top_cnt','快速','text.edit'],

                ['mj_vip','mj_vip','text.edit'],
                

                ['svip','svip','text.edit'],
                ['mj_url','mj_url','text.edit'],
                ['mj_key','mj_key','text.edit'],
                
                ['duration_expire_time', '时长码过期时间','text.edit'],
                ['expire_time', '过期时间','text.edit'],
                ['last_login_time', '最近登录时间'],
                ['time', '创建时间'],
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '新增', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'last_login_time', '活跃日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'duration_expire_time', '时长过期日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'expire_time', '过期日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'mac_address', 'mac', '', '', ''],
                ['text', 'user_id', 'user_id', '', '', ''],
                ['text', 'mj_share_switch', '是否共享mj图片', '', '', ''],
              
            ])
            
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($js)
            // ->addTopButton('add',['title'=>'新增分类'])
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
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

           $groupBy2 = $isSameDay ? 'HOUR(last_login_time)' : 'DATE(last_login_time)';
                    // 3.1 查询基于 time 的数据（原有逻辑）
        $data_list_time = DB::table('ai_mac_user_info')
        ->whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select();

        // 3.2 查询基于 last_login_time 的数据
        $data_list_last_login = DB::table('ai_mac_user_info')
        ->whereTime('last_login_time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy2} as axisValue",
            'COUNT(*) as activeCount'
        ])
        ->group('axisValue')
        ->select();

        // 初始化数组
        $x_data = array();
        $y_data_time = array();  // 对于 time 字段
        $y_data_last_login = array();  // 对于 last_login_time 字段

        // 遍历查询结果
        foreach ($data_list_time as $value) {
        // 添加到 x 轴和 y 轴数据 (time 字段)
        array_push($x_data, $value['axisValue']);
        array_push($y_data_time, $value['count']);
        }

        foreach ($data_list_last_login as $value) {
        // 添加到 y 轴数据 (last_login_time 字段)
        array_push($y_data_last_login, $value['activeCount']);
        }

        // JSON 编码数据以用于 JavaScript
        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_last_login_json = json_encode($y_data_last_login);

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
                data: ['新增', '活跃']
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
                },
                {
                    name: '活跃',
                    type: 'line',
                    data: {$y_data_last_login_json},
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