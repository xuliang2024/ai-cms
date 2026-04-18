<?php
// Coze任务列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class CozeTasks extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_coze_tasks')->where($map)
        ->order('created_at desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html = "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_coze_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CozeTasksModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['user_id', '用户ID'],
                ['workflow_id', '工作流ID'],
                ['input', '输入参数', 'text'],
                ['interval_ms', '间隔时间(ms)', 'text'],
                ['max_cnt', '最大次数', 'text'],
                ['callback_workflow_id', '回调工作流ID'],
                ['callback_key', '回调Key'],
                ['status', '状态', 'text.edit'],
                ['run_cnt', '运行次数', 'text'],
                ['coze_token', 'Coze Token'],
                ['workflow_result', '工作流结果'  , 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                } ],
                ['callback_result', '回调结果' , 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                } ],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['time', '时间'],
            ])
            ->setSearchArea([  
                ['text', 'task_id', '任务ID'],
                ['text', 'user_id', '用户ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['text', 'status', '状态'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->addTopButton('delete', ['title' => '删除']) // 批量添加顶部按钮
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'created_at=') === 0) {
                    $daterangeValue = substr($param, strlen('created_at='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
        $groupBy = $isSameDay ? 'HOUR(created_at)' : 'DATE(created_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = DB::connect('translate')->table('ts_coze_tasks')
        ->whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as status_pending',
            'SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as status_running',
            'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as status_completed',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as status_failed'
        ])
        ->group('axisValue')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_pending = array();
        $y_data_status_running = array();
        $y_data_status_completed = array();
        $y_data_status_failed = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_pending, $value['status_pending']);
            array_push($y_data_status_running, $value['status_running']);
            array_push($y_data_status_completed, $value['status_completed']);
            array_push($y_data_status_failed, $value['status_failed']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_pending_json = json_encode($y_data_status_pending);
        $y_data_status_running_json = json_encode($y_data_status_running);
        $y_data_status_completed_json = json_encode($y_data_status_completed);
        $y_data_status_failed_json = json_encode($y_data_status_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'Coze任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '等待中', '运行中', '已完成', '失败']
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
                    name: '总任务数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '等待中',
                    type: 'line',
                    data: {$y_data_status_pending_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '运行中',
                    type: 'line',
                    data: {$y_data_status_running_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '已完成',
                    type: 'line',
                    data: {$y_data_status_completed_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败',
                    type: 'line',
                    data: {$y_data_status_failed_json},
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