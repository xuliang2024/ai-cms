<?php
// Flux API账号管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FluxAccountModel;

class FluxAccount extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = FluxAccountModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_flux_account', $map);
        
        // 账号状态定义
        $status_types = [
            0 => '禁用',
            1 => '正常'
        ];
        
        return ZBuilder::make('table')
            ->setTableName('video/FluxAccountModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '账号名称'],
                ['x_key', 'API密钥', 'callback', function($value){
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['status', '账号状态', 'switch'],
                ['use_cnt', '使用次数'],
                ['error_cnt', '错误次数'],
                ['last_used_at', '最后使用时间'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['remark', '备注', 'callback', function($value){
                    if(empty($value)) return '无备注';
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '账号名称'],
                ['select', 'status', '账号状态', '', $status_types],
                ['text', 'x_key', 'API密钥'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
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

        $data_list_time = FluxAccountModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as status_0',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as status_1',
            'SUM(use_cnt) as use_cnt',
            'SUM(error_cnt) as error_cnt'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_0 = array();
        $y_data_status_1 = array();
        $y_data_use_cnt = array();
        $y_data_error_cnt = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_0, $value['status_0']);
            array_push($y_data_status_1, $value['status_1']);
            array_push($y_data_use_cnt, $value['use_cnt']);
            array_push($y_data_error_cnt, $value['error_cnt']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_0_json = json_encode($y_data_status_0);
        $y_data_status_1_json = json_encode($y_data_status_1);
        $y_data_use_cnt_json = json_encode($y_data_use_cnt);
        $y_data_error_cnt_json = json_encode($y_data_error_cnt);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'Flux API账号{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['账号总数', '禁用账号', '正常账号', '使用次数', '错误次数']
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
                    name: '账号总数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '禁用账号',
                    type: 'line',
                    data: {$y_data_status_0_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '正常账号',
                    type: 'line',
                    data: {$y_data_status_1_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '使用次数',
                    type: 'line',
                    data: {$y_data_use_cnt_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '错误次数',
                    type: 'line',
                    data: {$y_data_error_cnt_json},
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
            
            if (FluxAccountModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增Flux API账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'x_key', 'API密钥', '请输入Flux API密钥', '', 'required'],
                ['radio', 'status', '账号状态', '选择账号状态', ['0' => '禁用', '1' => '正常'], 1],
                ['textarea', 'remark', '备注', '请输入账号备注说明'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (FluxAccountModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = FluxAccountModel::where('id', $id)->find();
        if (!$info) {
            $this->error('账号不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑Flux API账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'x_key', 'API密钥', '请输入Flux API密钥', '', 'required'],
                ['radio', 'status', '账号状态', '选择账号状态', ['0' => '禁用', '1' => '正常']],
                ['number', 'use_cnt', '使用次数', '账号累计使用次数'],
                ['number', 'error_cnt', '错误次数', '账号累计错误次数'],
                ['datetime', 'last_used_at', '最后使用时间', '账号最后一次被使用的时间'],
                ['textarea', 'remark', '备注', '请输入账号备注说明'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = FluxAccountModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 