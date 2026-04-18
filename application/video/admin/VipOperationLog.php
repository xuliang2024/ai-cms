<?php
// VIP操作日志管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\VipOperationLogModel;

class VipOperationLog extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = VipOperationLogModel::where($map)
        ->order('operation_time desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_vip_operation_log', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/VipOperationLogModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['phone', '手机号'],
                ['operation_type', '操作类型', 'select', '', [
                    'activate' => '开通',
                    'deactivate' => '关闭'
                ]],
                ['old_vip_level', '操作前等级'],
                ['new_vip_level', '操作后等级'],
                ['old_vip_time', '操作前到期时间'],
                ['new_vip_time', '操作后到期时间'],
                ['out_trade_no', '订单号'],
                ['ip_address', 'IP地址'],
                ['operation_result', '操作结果', 'select', '', [
                    'success' => '成功',
                    'failed' => '失败'
                ]],
                ['operation_time', '操作时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'phone', '手机号'],
                ['select', 'operation_type', '操作类型', '', [
                    'activate' => '开通',
                    'deactivate' => '关闭'
                ]],
                ['text', 'out_trade_no', '订单号'],
                ['text', 'ip_address', 'IP地址'],
                ['select', 'operation_result', '操作结果', '', [
                    'success' => '成功',
                    'failed' => '失败'
                ]],
                ['daterange', 'operation_time', '操作时间', '', '', ['format' => 'YYYY-MM-DD']],
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
                if (strpos($param, 'operation_time=') === 0) {
                    $daterangeValue = substr($param, strlen('operation_time='));
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
        $groupBy = $isSameDay ? 'HOUR(operation_time)' : 'DATE(operation_time)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = VipOperationLogModel::whereTime('operation_time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN operation_type = "activate" THEN 1 ELSE 0 END) as type_activate',
            'SUM(CASE WHEN operation_type = "deactivate" THEN 1 ELSE 0 END) as type_deactivate',
            'SUM(CASE WHEN operation_result = "success" THEN 1 ELSE 0 END) as result_success',
            'SUM(CASE WHEN operation_result = "failed" THEN 1 ELSE 0 END) as result_failed'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_total = array();
        $y_data_activate = array();
        $y_data_deactivate = array();
        $y_data_success = array();
        $y_data_failed = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['count']);
            array_push($y_data_activate, $value['type_activate']);
            array_push($y_data_deactivate, $value['type_deactivate']);
            array_push($y_data_success, $value['result_success']);
            array_push($y_data_failed, $value['result_failed']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_activate_json = json_encode($y_data_activate);
        $y_data_deactivate_json = json_encode($y_data_deactivate);
        $y_data_success_json = json_encode($y_data_success);
        $y_data_failed_json = json_encode($y_data_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'VIP操作日志{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['操作总数', '开通VIP', '关闭VIP', '成功操作', '失败操作']
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
                    name: '操作总数',
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
            
            $data['operation_time'] = date('Y-m-d H:i:s');
            
            if (VipOperationLogModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增VIP操作日志') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'phone', '手机号', '请输入手机号', '', 'required'],
                ['select', 'operation_type', '操作类型', '请选择操作类型', [
                    'activate' => '开通',
                    'deactivate' => '关闭'
                ], 'required'],
                ['number', 'old_vip_level', '操作前等级', '请输入操作前的会员等级', '0'],
                ['number', 'new_vip_level', '操作后等级', '请输入操作后的会员等级', '0'],
                ['datetime', 'old_vip_time', '操作前到期时间', '请选择操作前的会员到期时间'],
                ['datetime', 'new_vip_time', '操作后到期时间', '请选择操作后的会员到期时间'],
                ['text', 'out_trade_no', '订单号', '请输入订单号'],
                ['text', 'ip_address', 'IP地址', '请输入操作IP地址'],
                ['textarea', 'user_agent', '用户代理', '请输入用户代理信息'],
                ['select', 'operation_result', '操作结果', '请选择操作结果', [
                    'success' => '成功',
                    'failed' => '失败'
                ], 'required'],
                ['textarea', 'error_message', '错误信息', '请输入错误信息（失败时填写）'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            
            if (VipOperationLogModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = VipOperationLogModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑VIP操作日志') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'phone', '手机号', '请输入手机号', '', 'required'],
                ['select', 'operation_type', '操作类型', '请选择操作类型', [
                    'activate' => '开通',
                    'deactivate' => '关闭'
                ], 'required'],
                ['number', 'old_vip_level', '操作前等级', '请输入操作前的会员等级', '0'],
                ['number', 'new_vip_level', '操作后等级', '请输入操作后的会员等级', '0'],
                ['datetime', 'old_vip_time', '操作前到期时间', '请选择操作前的会员到期时间'],
                ['datetime', 'new_vip_time', '操作后到期时间', '请选择操作后的会员到期时间'],
                ['text', 'out_trade_no', '订单号', '请输入订单号'],
                ['text', 'ip_address', 'IP地址', '请输入操作IP地址'],
                ['textarea', 'user_agent', '用户代理', '请输入用户代理信息'],
                ['select', 'operation_result', '操作结果', '请选择操作结果', [
                    'success' => '成功',
                    'failed' => '失败'
                ], 'required'],
                ['textarea', 'error_message', '错误信息', '请输入错误信息（失败时填写）'],
                ['datetime', 'operation_time', '操作时间', '请选择操作时间', '', 'required'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = VipOperationLogModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 
