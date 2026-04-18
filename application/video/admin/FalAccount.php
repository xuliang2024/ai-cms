<?php
// Fal API账号管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalAccountModel;

class FalAccount extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = FalAccountModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_fal_account', $map);
        
        // 统计账号信息
        $stats = [
            'online_count' => FalAccountModel::where('status', 1)->count(),
            'offline_count' => FalAccountModel::where('status', 0)->count(),
            'offline_balance_gt1_count' => FalAccountModel::where(['status' => 0])->where('balance', '>', 1)->count(),
            'total_balance' => FalAccountModel::where('balance', '>', 0)->sum('balance'),
            'online_balance' => FalAccountModel::where(['status' => 1])->where('balance', '>', 0)->sum('balance'),
            'offline_balance' => FalAccountModel::where(['status' => 0])->where('balance', '>', 0)->sum('balance')
        ];
        
        // 账号状态定义
        $status_types = [
            0 => '禁用',
            1 => '正常'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("在线账号：{$stats['online_count']} | 离线账号：{$stats['offline_count']} | 离线余额>1账号：{$stats['offline_balance_gt1_count']} | 总余额：$" . number_format($stats['total_balance'], 2) . " (>0) | 在线余额：$" . number_format($stats['online_balance'], 2) . " (>0) | 离线余额：$" . number_format($stats['offline_balance'], 2) . " (>0)")
            ->setTableName('video/FalAccountModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '账号名称'],
                ['api_key', 'API密钥', 'callback', function($value){
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['balance', '余额'],
                ['status', '账号状态', 'switch'],
                ['use_cnt', '使用次数'],
                ['recovered_points', '回收点数'],
                ['error_cnt', '错误次数'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['remark', '备注', 'callback', function($value){
                    if(empty($value)) return '无备注';
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['text', 'name', '账号名称'],
                ['text', 'status', '账号状态'],
                ['text', 'api_key', 'API密钥'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('custom', ['title' => '批量加入', 'href' => url('batchAdd')]) // 添加批量加入按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
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

        $data_list_time = FalAccountModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as status_0',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as status_1',
            'SUM(use_cnt) as use_cnt',
            'SUM(error_cnt) as error_cnt',
            'SUM(balance) as total_balance'
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
        $y_data_balance = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_0, $value['status_0']);
            array_push($y_data_status_1, $value['status_1']);
            array_push($y_data_use_cnt, $value['use_cnt']);
            array_push($y_data_error_cnt, $value['error_cnt']);
            array_push($y_data_balance, $value['total_balance']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_0_json = json_encode($y_data_status_0);
        $y_data_status_1_json = json_encode($y_data_status_1);
        $y_data_use_cnt_json = json_encode($y_data_use_cnt);
        $y_data_error_cnt_json = json_encode($y_data_error_cnt);
        $y_data_balance_json = json_encode($y_data_balance);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'Fal API账号{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['账号总数', '禁用账号', '正常账号', '使用次数', '错误次数', '总余额']
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
                },
                {
                    name: '总余额',
                    type: 'line',
                    data: {$y_data_balance_json},
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
            
            if (FalAccountModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增Fal API账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'api_key', 'API密钥', '请输入Fal API密钥', '', 'required'],
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
            
            if (FalAccountModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = FalAccountModel::where('id', $id)->find();
        if (!$info) {
            $this->error('账号不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑Fal API账号') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'name', '账号名称', '请输入账号名称', '', 'required'],
                ['text', 'api_key', 'API密钥', '请输入Fal API密钥', '', 'required'],
                ['text', 'balance', '余额', '账号当前余额'],
                ['radio', 'status', '账号状态', '选择账号状态', ['0' => '禁用', '1' => '正常']],
                ['number', 'use_cnt', '使用次数', '账号累计使用次数'],
                ['number', 'error_cnt', '错误次数', '账号累计错误次数'],
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
        
        $result = FalAccountModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function batchAdd()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $batch_data = trim($data['batch_data']);
            
            if (empty($batch_data)) {
                $this->error('请输入要批量添加的数据');
            }
            
            $lines = explode("\n", $batch_data);
            $success_count = 0;
            $error_lines = [];
            
            foreach ($lines as $line_num => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // 将多个空格替换成一个空格
                $line = preg_replace('/\s+/', ' ', $line);
                $parts = explode(' ', $line);
                
                if (count($parts) < 3) {
                    $error_lines[] = "第" . ($line_num + 1) . "行格式错误";
                    continue;
                }
                
                $account_data = [
                    'name' => $parts[0],
                    'api_key' => $parts[2],
                    'remark' => $line,
                    'status' => 1,
                    'balance' => 0,
                    'use_cnt' => 0,
                    'error_cnt' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    if (FalAccountModel::create($account_data)) {
                        $success_count++;
                    }
                } catch (Exception $e) {
                    $error_lines[] = "第" . ($line_num + 1) . "行添加失败：" . $e->getMessage();
                }
            }
            
            $message = "成功添加 {$success_count} 个账号";
            if (!empty($error_lines)) {
                $message .= "，以下行处理失败：<br>" . implode('<br>', $error_lines);
            }
            
            $this->success($message, url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('批量加入Fal API账号')
            ->addFormItems([
                ['textarea', 'batch_data', '批量数据', '每行一个账号，格式：邮箱 密码 API密钥 数字<br>示例：luktdfil@aizf.uk qwer1234 b1a70d3d-3146-4657-87c9-30b58e52534b:c1c9456ed732e603350a3ecb0bbd381a 10', '', 'required', 'rows="10"']
            ])
            ->fetch();
    }
} 