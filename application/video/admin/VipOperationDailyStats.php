<?php
// VIP操作日统计管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\VipOperationDailyStatsModel;

class VipOperationDailyStats extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询，并计算开通成功率（关闭VIP次数 / 开通VIP次数）
        $data_list = VipOperationDailyStatsModel::where($map)
        ->field('*, CASE WHEN activate_count > 0 THEN ROUND((deactivate_count / activate_count) * 100, 2) ELSE 0 END as activate_success_rate')
        ->order('day_id desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_vip_operation_daily_stats', $map);
        
        // 统计信息
        $stats = [
            'total_days' => VipOperationDailyStatsModel::count(),
            'total_calls' => VipOperationDailyStatsModel::sum('total_calls'),
            'total_activate' => VipOperationDailyStatsModel::sum('activate_count'),
            'total_deactivate' => VipOperationDailyStatsModel::sum('deactivate_count'),
            'total_success' => VipOperationDailyStatsModel::sum('success_count'),
            'total_failed' => VipOperationDailyStatsModel::sum('failed_count'),
            'success_rate' => 0
        ];
        
        // 计算成功率
        if ($stats['total_calls'] > 0) {
            $stats['success_rate'] = round(($stats['total_success'] / $stats['total_calls']) * 100, 2);
        }
        
        return ZBuilder::make('table')
            ->setPageTips("统计天数：{$stats['total_days']} | 总调用次数：{$stats['total_calls']} | 开通VIP：{$stats['total_activate']} | 关闭VIP：{$stats['total_deactivate']} | 成功操作：{$stats['total_success']} | 失败操作：{$stats['total_failed']} | 成功率：{$stats['success_rate']}%")
            ->setTableName('video/VipOperationDailyStatsModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['day_id', '统计日期'],
                
                ['activate_count', '开通VIP次数'],
                ['deactivate_count', '关闭VIP次数'],
                ['activate_success_rate', '退款率', 'callback', function($value){
                    return $value . '%';
                }],
                ['total_calls', '总调用次数'],
                ['success_count', '成功次数'],
                ['failed_count', '失败次数'],
                
                ['created_time', '创建时间'],
                ['updated_time', '更新时间'],
                
            ])
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['daterange', 'day_id', '统计日期', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'created_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'day_id=') === 0) {
                    $daterangeValue = substr($param, strlen('day_id='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
        } else {
            // 默认显示最近30天
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
        }

        $data_list_time = VipOperationDailyStatsModel::whereTime('day_id', 'between', [$startDate, $endDate])
        ->field([
            'day_id as axisValue',
            'total_calls',
            'activate_count',
            'deactivate_count',
            'success_count',
            'failed_count'
        ])
        ->order('day_id asc')
        ->select();

        $x_data = array();
        $y_data_total = array();
        $y_data_activate = array();
        $y_data_deactivate = array();
        $y_data_success = array();
        $y_data_failed = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['total_calls']);
            array_push($y_data_activate, $value['activate_count']);
            array_push($y_data_deactivate, $value['deactivate_count']);
            array_push($y_data_success, $value['success_count']);
            array_push($y_data_failed, $value['failed_count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_activate_json = json_encode($y_data_activate);
        $y_data_deactivate_json = json_encode($y_data_deactivate);
        $y_data_success_json = json_encode($y_data_success);
        $y_data_failed_json = json_encode($y_data_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate . ' - ' . $endDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'VIP操作日统计趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总调用次数', '开通VIP', '关闭VIP', '成功操作', '失败操作']
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
                    name: '总调用次数',
                    type: 'line',
                    data: {$y_data_total_json},
                    itemStyle: {
                        color: '#5470c6'
                    },
                    lineStyle: {
                        color: '#5470c6'
                    },
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '开通VIP',
                    type: 'line',
                    data: {$y_data_activate_json},
                    itemStyle: {
                        color: '#91cc75'
                    },
                    lineStyle: {
                        color: '#91cc75'
                    },
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '关闭VIP',
                    type: 'line',
                    data: {$y_data_deactivate_json},
                    itemStyle: {
                        color: '#fac858'
                    },
                    lineStyle: {
                        color: '#fac858'
                    },
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '成功操作',
                    type: 'line',
                    data: {$y_data_success_json},
                    itemStyle: {
                        color: '#73c0de'
                    },
                    lineStyle: {
                        color: '#73c0de'
                    },
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败操作',
                    type: 'line',
                    data: {$y_data_failed_json},
                    itemStyle: {
                        color: '#ee6666'
                    },
                    lineStyle: {
                        color: '#ee6666'
                    },
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

    public function add()
    {
        // 构建表单
        if (request()->isPost()) {
            $data = input('post.');
            
            $data['created_time'] = date('Y-m-d H:i:s');
            $data['updated_time'] = date('Y-m-d H:i:s');
            
            if (VipOperationDailyStatsModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增VIP操作日统计') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['date', 'day_id', '统计日期', '请选择统计日期', '', 'required'],
                ['number', 'total_calls', '总调用次数', '请输入总调用次数', '0'],
                ['number', 'activate_count', '开通VIP次数', '请输入开通VIP次数', '0'],
                ['number', 'deactivate_count', '关闭VIP次数', '请输入关闭VIP次数', '0'],
                ['number', 'success_count', '成功次数', '请输入操作成功次数', '0'],
                ['number', 'failed_count', '失败次数', '请输入操作失败次数', '0'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_time'] = date('Y-m-d H:i:s');
            
            if (VipOperationDailyStatsModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = VipOperationDailyStatsModel::where('id', $id)->find();
        if (!$info) {
            $this->error('统计记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑VIP操作日统计') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['date', 'day_id', '统计日期', '请选择统计日期', '', 'required'],
                ['number', 'total_calls', '总调用次数', '请输入总调用次数', '0'],
                ['number', 'activate_count', '开通VIP次数', '请输入开通VIP次数', '0'],
                ['number', 'deactivate_count', '关闭VIP次数', '请输入关闭VIP次数', '0'],
                ['number', 'success_count', '成功次数', '请输入操作成功次数', '0'],
                ['number', 'failed_count', '失败次数', '请输入操作失败次数', '0'],
                ['datetime', 'created_time', '创建时间', '创建时间'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = VipOperationDailyStatsModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 
