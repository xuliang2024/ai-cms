<?php
// 即梦任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\JimengTaskModel;
use app\video\model\JimengAccountModel;

class JimengTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = JimengTaskModel::where($map)
        ->order('created_at desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_jimeng_task', $map);
        
        // 获取MAC地址列表，用于搜索筛选
        $mac_list = JimengAccountModel::column('mac_address', 'mac_address');
        
        // 任务类型和状态定义
        $task_types = [
            1 => '图生视频',
            2 => '对口型',
            3 => '动作模仿'
        ];
        
        $status_types = [
            0 => '待处理',
            1 => '处理中',
            2 => '已完成',
            3 => '失败'
        ];
        
        return ZBuilder::make('table')
            ->setTableName('video/JimengTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID' ],
                ['task_id', '任务ID' ],
                ['money', '金额'],
                ['task_type', '任务类型'],
                ['first_image', '首张图片', 'img_url'],
                ['last_image', '尾张图片', 'img_url'],
                ['video_url', '视频链接', 'callback', function($value){
                    if(empty($value)) return '暂无视频';
                    return '<a href="'.$value.'" target="_blank" class="btn btn-xs btn-success"><i class="fa fa-play"></i> 播放</a>';
                }],
                ['reference_video', '参考视频'],
                ['mac_address', 'MAC地址'],
                ['prompt', '提示词', 'callback', function($value){
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['model', '使用模型'],
                ['ratio', '生成比例'],
                ['duration', '时长(秒)'],
                ['status', '状态' , 'text.edit'],
                ['progress', '进度'],
                
                ['online_task_id', '在线任务ID'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'task_id', '任务ID'],
                ['text', 'task_type', '任务类型'],
                ['text', 'status', '状态'],
                ['text', 'mac_address', 'MAC地址'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
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

        $data_list_time = JimengTaskModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as status_0',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as status_1',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as status_2',
            'SUM(CASE WHEN status = 7 THEN 1 ELSE 0 END) as status_7',
            'SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as status_3'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_0 = array();
        $y_data_status_1 = array();
        $y_data_status_2 = array();
        $y_data_status_7 = array();
        $y_data_status_3 = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_0, $value['status_0']);
            array_push($y_data_status_1, $value['status_1']);
            array_push($y_data_status_2, $value['status_2']);
            array_push($y_data_status_7, $value['status_7']);
            array_push($y_data_status_3, $value['status_3']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_0_json = json_encode($y_data_status_0);
        $y_data_status_1_json = json_encode($y_data_status_1);
        $y_data_status_2_json = json_encode($y_data_status_2);
        $y_data_status_7_json = json_encode($y_data_status_7);
        $y_data_status_3_json = json_encode($y_data_status_3);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '即梦任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '等待中', '下发中', '处理中', '完成', '失败']
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
                    data: {$y_data_status_0_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '下发中',
                    type: 'line',
                    data: {$y_data_status_1_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '处理中',
                    type: 'line',
                    data: {$y_data_status_2_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '完成',
                    type: 'line',
                    data: {$y_data_status_7_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败',
                    type: 'line',
                    data: {$y_data_status_3_json},
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

    

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = JimengTaskModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 