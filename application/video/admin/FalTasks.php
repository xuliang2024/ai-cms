<?php
// Fal API任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalTasksModel;

class FalTasks extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = FalTasksModel::where($map)
        ->order('created_at desc')
        ->paginate();

        $js = $this->getChartjs();
        $pie_js = $this->getAppNamePieChart();
        $content_html =  "";
        if($js != "" || $pie_js != ""){
            $content_html = '<div style="display: flex; width: 100%;">
                <div id="main" style="width: 70%;height:300px;"></div>
                <div id="pie_chart" style="width: 30%;height:300px;"></div>
            </div>';    
        }

        cookie('ts_fal_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/FalTasksModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['task_id', '系统任务ID'],
                ['online_task_id', 'Fal平台任务ID'],
                ['app_name', 'Fal应用名称'],
                ['money','金额'],
                ['is_public', '公开', 'switch'],
                ['is_save','转存'],
                ['is_refund','退款'],
                ['status', '任务状态'],
                ['created_at', '创建时间'],
                ['completed_at', '完成时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'task_id', '系统任务ID'],
                ['text', 'online_task_id', 'Fal平台任务ID'],
                ['text', 'api_key', '使用的API密钥'],
                ['text', 'app_name', 'Fal应用名称'],
                ['text', 'status', '任务状态'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->setExtraJs($js . $pie_js)
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

        $data_list_time = FalTasksModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as status_completed',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as status_failed',
            'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as status_pending',
            'ROUND(SUM(CASE WHEN status = "completed" THEN money ELSE 0 END)/100) as money_sum'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_completed = array();
        $y_data_failed = array();
        $y_data_pending = array();
        $y_data_money = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_completed, $value['status_completed']);
            array_push($y_data_failed, $value['status_failed']);
            array_push($y_data_pending, $value['status_pending']);
            array_push($y_data_money, $value['money_sum']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_completed_json = json_encode($y_data_completed);
        $y_data_failed_json = json_encode($y_data_failed);
        $y_data_pending_json = json_encode($y_data_pending);
        $y_data_money_json = json_encode($y_data_money);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'Fal API任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['任务总数', '已完成', '失败', '等待中', '金额统计']
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
                    name: '任务总数',
                    type: 'line',
                    data: {$y_data_time_json},
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
                    name: '已完成',
                    type: 'line',
                    data: {$y_data_completed_json},
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
                    name: '失败',
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
                },
                {
                    name: '等待中',
                    type: 'line',
                    data: {$y_data_pending_json},
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
                    name: '金额统计',
                    type: 'line',
                    data: {$y_data_money_json},
                    itemStyle: {
                        color: '#fc7d02'
                    },
                    lineStyle: {
                        color: '#fc7d02'
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
            
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (FalTasksModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增Fal API任务') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'task_id', '系统任务ID', '请输入系统任务ID', '', 'required'],
                ['text', 'online_task_id', 'Fal平台任务ID', '请输入Fal平台任务ID'],
                ['text', 'app_name', 'Fal应用名称', '请输入Fal应用名称'],
                ['text', 'api_key', '使用的API密钥', '请输入API密钥'],
                ['text', 'status', '任务状态', '请输入任务状态'],
                ['textarea', 'input_params', '输入参数', '请输入JSON格式的输入参数'],
                ['textarea', 'output_params', '输出结果', '请输入JSON格式的输出结果'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (FalTasksModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = FalTasksModel::where('id', $id)->find();
        if (!$info) {
            $this->error('任务不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑Fal API任务') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'task_id', '系统任务ID', '请输入系统任务ID', '', 'required'],
                ['text', 'online_task_id', 'Fal平台任务ID', '请输入Fal平台任务ID'],
                ['text', 'app_name', 'Fal应用名称', '请输入Fal应用名称'],
                ['text', 'api_key', '使用的API密钥', '请输入API密钥'],
                ['text', 'status', '任务状态', '请输入任务状态'],
                ['textarea', 'input_params', '输入参数', '请输入JSON格式的输入参数'],
                ['textarea', 'output_params', '输出结果', '请输入JSON格式的输出结果'],
                ['datetime', 'completed_at', '完成时间', '请选择完成时间'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = FalTasksModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function getAppNamePieChart() {
        // 获取当前搜索条件
        $map = $this->getMap();
        
        // 查询 app_name 统计数据
        $app_name_data = FalTasksModel::where($map)
            ->field([
                'app_name',
                'COUNT(*) as count'
            ])
            ->group('app_name')
            ->order('count desc')
            ->select();

        if (empty($app_name_data)) {
            return "";
        }

        $pie_data = array();
        foreach ($app_name_data as $item) {
            $app_name = $item['app_name'] ?: '未知应用';
            $pie_data[] = [
                'name' => $app_name,
                'value' => $item['count']
            ];
        }

        $pie_data_json = json_encode($pie_data);

        $js = "
        <script type='text/javascript'>
        var pieChart = echarts.init(document.getElementById('pie_chart'));
        var pieOption = {
            title: {
                text: 'App应用占比',
                left: 'center',
                textStyle: {
                    fontSize: 14
                }
            },
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 'left',
                textStyle: {
                    fontSize: 10
                }
            },
            series: [
                {
                    name: '任务数量',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    avoidLabelOverlap: false,
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: '10',
                            fontWeight: 'bold'
                        }
                    },
                    labelLine: {
                        show: false
                    },
                    data: {$pie_data_json}
                }
            ]
        };
        pieChart.setOption(pieOption);
        </script>";

        return $js;
    }
} 