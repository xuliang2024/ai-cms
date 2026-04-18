<?php
// 扣子账号管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CozeAccountModel;

class CozeAccount extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = CozeAccountModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_coze_account', $map);
        
        // 账号状态定义
        $status_types = [
            0 => '禁用',
            1 => '启用'
        ];
        
        return ZBuilder::make('table')
            ->setTableName('video/CozeAccountModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['account_name', '账号名称'],
                ['coze_key', '扣子密钥', 'callback', function($value){
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['workflow_id', '工作流ID'],
                ['status', '账号状态', 'switch'],
                ['total_requests', '总请求数'],
                ['success_requests', '成功请求数'],
                ['failed_requests', '失败请求数' , 'text.edit'],
                ['qps_limit', '日请求限制' , 'text.edit'],
                ['last_error', '最后错误', 'callback', function($value){
                    if(empty($value)) return '无错误';
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['error_count', '连续错误次数' , 'text.edit'],
                ['last_used_at', '最后使用时间'],
                ['last_success_at', '最后成功时间'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['description', '账号描述', 'callback', function($value){
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'account_name', '账号名称'],
                ['select', 'status', '账号状态', '', $status_types],
                ['text', 'workflow_id', '工作流ID'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->addRightButton('test', ['title' => '测试', 'href' => url('test', ['id' => '__id__']), 'class' => 'btn btn-xs btn-success']) // 添加测试按钮
            ->setHeight('auto')
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

        $data_list_time = CozeAccountModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as status_0',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as status_1',
            'SUM(total_requests) as total_requests',
            'SUM(success_requests) as success_requests',
            'SUM(failed_requests) as failed_requests'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_0 = array();
        $y_data_status_1 = array();
        $y_data_total_requests = array();
        $y_data_success_requests = array();
        $y_data_failed_requests = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_0, $value['status_0']);
            array_push($y_data_status_1, $value['status_1']);
            array_push($y_data_total_requests, $value['total_requests']);
            array_push($y_data_success_requests, $value['success_requests']);
            array_push($y_data_failed_requests, $value['failed_requests']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_0_json = json_encode($y_data_status_0);
        $y_data_status_1_json = json_encode($y_data_status_1);
        $y_data_total_requests_json = json_encode($y_data_total_requests);
        $y_data_success_requests_json = json_encode($y_data_success_requests);
        $y_data_failed_requests_json = json_encode($y_data_failed_requests);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '扣子账号{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['账号总数', '禁用账号', '启用账号', '总请求数', '成功请求数', '失败请求数']
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
                    name: '启用账号',
                    type: 'line',
                    data: {$y_data_status_1_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '总请求数',
                    type: 'line',
                    data: {$y_data_total_requests_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '成功请求数',
                    type: 'line',
                    data: {$y_data_success_requests_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败请求数',
                    type: 'line',
                    data: {$y_data_failed_requests_json},
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
            
            if (CozeAccountModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增扣子账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'account_name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'coze_key', '扣子密钥', '请输入Coze API密钥', '', 'required'],
                ['text', 'workflow_id', '工作流ID', '请输入Coze工作流ID', '', 'required'],
                ['radio', 'status', '账号状态', '选择账号状态', ['0' => '禁用', '1' => '启用'], 1],
                ['number', 'qps_limit', '日请求限制', '每日最大请求数限制', 4],
                ['textarea', 'description', '账号描述', '请输入账号备注说明'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (CozeAccountModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CozeAccountModel::where('id', $id)->find();
        if (!$info) {
            $this->error('账号不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑扣子账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'account_name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'coze_key', '扣子密钥', '请输入Coze API密钥', '', 'required'],
                ['text', 'workflow_id', '工作流ID', '请输入Coze工作流ID', '', 'required'],
                ['radio', 'status', '账号状态', '选择账号状态', ['0' => '禁用', '1' => '启用']],
                ['number', 'total_requests', '总请求数', '账号累计处理的请求总数'],
                ['number', 'success_requests', '成功请求数', '账号成功处理的请求数'],
                ['number', 'failed_requests', '失败请求数', '账号失败的请求数'],
                ['number', 'qps_limit', '日请求限制', '每日最大请求数限制'],
                ['textarea', 'last_error', '最后错误', '最后一次出错的详细信息'],
                ['number', 'error_count', '连续错误次数', '连续出错的次数'],
                ['datetime', 'last_used_at', '最后使用时间', '账号最后一次被使用的时间'],
                ['datetime', 'last_success_at', '最后成功时间', '账号最后一次成功处理的时间'],
                ['textarea', 'description', '账号描述', '请输入账号备注说明'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function test($id = null)
    {
        if (!$id) {
            $this->error('账号ID不能为空');
        }
        
        $account = CozeAccountModel::where('id', $id)->find();
        if (!$account) {
            $this->error('账号不存在');
        }
        
        $coze_key = $account['coze_key'];
        $workflow_id = $account['workflow_id'];
        
        $url = 'https://api.coze.cn/v1/workflow/run';
        $headers = [
            'Authorization: Bearer ' . $coze_key,
            'Content-Type: application/json'
        ];
        
        $data = [
            'parameters' => [
                'prompt' => 'cat',
                'size' => '1:1'
            ],
            'workflow_id' => $workflow_id
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->error('请求失败: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($http_code == 200) {
            $this->success('测试成功' . $result['data'], null, ['result' => $result]);
        } else {
            $this->error('测试失败，HTTP状态码: ' . $http_code . '，响应: ' . $response);
        }
    }
    
    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CozeAccountModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 