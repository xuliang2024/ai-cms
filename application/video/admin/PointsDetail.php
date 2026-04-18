<?php
// 积分明细管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use think\facade\Log;
use app\video\model\PointsDetailModel;

class PointsDetail extends Admin {

    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = PointsDetailModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_points_detail', $map);
        
        // 统计积分信息
        $stats = [
            'total_records' => PointsDetailModel::count(),
            'total_increase' => PointsDetailModel::where('points', '>', 0)->sum('points'),
            'total_decrease' => abs(PointsDetailModel::where('points', '<', 0)->sum('points')),
            'recharge_count' => PointsDetailModel::where('source_type', 'recharge')->count(),
            'consume_count' => PointsDetailModel::where('source_type', 'consume')->count(),
            'gift_count' => PointsDetailModel::where('source_type', 'gift')->count(),
            'today_increase' => PointsDetailModel::whereTime('created_at', 'today')->where('points', '>', 0)->sum('points'),
            'today_decrease' => abs(PointsDetailModel::whereTime('created_at', 'today')->where('points', '<', 0)->sum('points'))
        ];
        
        // 来源类型定义
        $source_types = [
            'recharge' => '充值',
            'consume' => '消费',
            'gift' => '赠送',
            'refund' => '退款',
            'reward' => '奖励',
            'system' => '系统调整'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("总记录：{$stats['total_records']} | 总增加：{$stats['total_increase']} | 总减少：{$stats['total_decrease']} | 充值：{$stats['recharge_count']}次 | 消费：{$stats['consume_count']}次 | 赠送：{$stats['gift_count']}次 | 今日增加：{$stats['today_increase']} | 今日减少：{$stats['today_decrease']}")
            ->setTableName('video/PointsDetailModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['points', '积分变动', 'callback', function($value){
                    if ($value > 0) {
                        return '<span style="color:green">+' . $value . '</span>';
                    } elseif ($value < 0) {
                        return '<span style="color:red">' . $value . '</span>';
                    } else {
                        return $value;
                    }
                }],
                ['order_id', '订单号', 'callback', function($value){
                    if(empty($value)) return '-';
                    return mb_strlen($value) > 25 ? mb_substr($value, 0, 25).'...' : $value;
                }],
                ['title', '交易说明', 'callback', function($value){
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['source_type', '来源类型', 'callback', function($value){
                    $types = [
                        'recharge' => '<span class="label label-primary">充值</span>',
                        'consume' => '<span class="label label-warning">消费</span>',
                        'gift' => '<span class="label label-success">赠送</span>',
                        'refund' => '<span class="label label-info">退款</span>',
                        'reward' => '<span class="label label-success">奖励</span>',
                        'system' => '<span class="label label-default">系统调整</span>'
                    ];
                    return isset($types[$value]) ? $types[$value] : $value;
                }],
                ['balance_before', '交易前余额'],
                ['balance_after', '交易后余额'],
                ['created_at', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['text', 'user_id', '用户ID'],
                ['text', 'order_id', '订单号'],
                ['text', 'title', '交易说明'],
                ['select', 'source_type', '来源类型', '', $source_types],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addTopButton('download', [
                'title' => '导出Excel',
                'class' => 'btn btn-success js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/points_detail/export_excel.html?' . $this->request->query()
            ]) // 添加导出Excel按钮
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
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
        $groupBy = $isSameDay ? 'HOUR(created_at)' : 'DATE(created_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = PointsDetailModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as increase_points',
            'SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END) as decrease_points',
            'SUM(CASE WHEN source_type = "recharge" THEN 1 ELSE 0 END) as recharge_count',
            'SUM(CASE WHEN source_type = "consume" THEN 1 ELSE 0 END) as consume_count',
            'SUM(CASE WHEN source_type = "gift" THEN 1 ELSE 0 END) as gift_count'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_count = array();
        $y_data_increase = array();
        $y_data_decrease = array();
        $y_data_recharge = array();
        $y_data_consume = array();
        $y_data_gift = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_count, $value['count']);
            array_push($y_data_increase, $value['increase_points']);
            array_push($y_data_decrease, $value['decrease_points']);
            array_push($y_data_recharge, $value['recharge_count']);
            array_push($y_data_consume, $value['consume_count']);
            array_push($y_data_gift, $value['gift_count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_count_json = json_encode($y_data_count);
        $y_data_increase_json = json_encode($y_data_increase);
        $y_data_decrease_json = json_encode($y_data_decrease);
        $y_data_recharge_json = json_encode($y_data_recharge);
        $y_data_consume_json = json_encode($y_data_consume);
        $y_data_gift_json = json_encode($y_data_gift);

        $display_date = $daterangeValue ? $daterangeValue : $startDate . ' - ' . date('Y-m-d', strtotime($endDate . ' -1 day'));
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '积分明细{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['记录总数', '增加积分', '减少积分', '充值次数', '消费次数', '赠送次数']
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
                    name: '记录总数',
                    type: 'line',
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '增加积分',
                    type: 'line',
                    data: {$y_data_increase_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '减少积分',
                    type: 'line',
                    data: {$y_data_decrease_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '充值次数',
                    type: 'line',
                    data: {$y_data_recharge_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '消费次数',
                    type: 'line',
                    data: {$y_data_consume_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '赠送次数',
                    type: 'line',
                    data: {$y_data_gift_json},
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
            
            // 计算交易后余额
            if (isset($data['balance_before']) && isset($data['points'])) {
                $data['balance_after'] = $data['balance_before'] + $data['points'];
            }
            
            $data['created_at'] = date('Y-m-d H:i:s');
            
            if (PointsDetailModel::create($data)) {
                $this->updateUserPoints($data['user_id'] ?? null, floatval($data['points'] ?? 0));
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增积分明细') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'points', '积分变动', '正数为增加，负数为减少', '', 'required'],
                ['text', 'order_id', '订单号', '请输入关联订单号', date('YmdHis')],
                ['text', 'title', '交易说明', '请输入交易说明', '手动补充', 'required'],
                ['select', 'source_type', '来源类型', '选择来源类型', [
                    'recharge' => '充值',
                    'consume' => '消费',
                    'gift' => '赠送',
                    'refund' => '退款',
                    'reward' => '奖励',
                    'system' => '系统调整'
                ], 'recharge', 'required'],
                ['number', 'balance_before', '交易前余额', '请输入交易前余额', '', 'required'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $oldRecord = PointsDetailModel::where('id', $id)->find();
            $userId = $data['user_id'] ?? ($oldRecord ? $oldRecord['user_id'] : null);
            
            if (isset($data['balance_before']) && isset($data['points'])) {
                $data['balance_after'] = $data['balance_before'] + $data['points'];
            }
            
            if (PointsDetailModel::where('id', $id)->update($data)) {
                $oldPoints = $oldRecord ? floatval($oldRecord['points']) : 0;
                $newPoints = floatval($data['points'] ?? $oldPoints);
                $diff = $newPoints - $oldPoints;
                if ($diff != 0) {
                    $this->updateUserPoints($userId, $diff);
                }
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = PointsDetailModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑积分明细') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'points', '积分变动', '正数为增加，负数为减少', '', 'required'],
                ['text', 'order_id', '订单号', '请输入关联订单号'],
                ['text', 'title', '交易说明', '请输入交易说明', '', 'required'],
                ['select', 'source_type', '来源类型', '选择来源类型', [
                    'recharge' => '充值',
                    'consume' => '消费',
                    'gift' => '赠送',
                    'refund' => '退款',
                    'reward' => '奖励',
                    'system' => '系统调整'
                ], '', 'required'],
                ['number', 'balance_before', '交易前余额', '请输入交易前余额', '', 'required'],
                ['static', 'balance_after', '交易后余额', '由系统自动计算'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $records = PointsDetailModel::whereIn('id', $ids)->select();
        $userPointsMap = [];
        foreach ($records as $record) {
            $uid = $record['user_id'];
            if (!empty($uid)) {
                $userPointsMap[$uid] = ($userPointsMap[$uid] ?? 0) + floatval($record['points']);
            }
        }
        
        $result = PointsDetailModel::whereIn('id', $ids)->delete();
        if ($result) {
            foreach ($userPointsMap as $userId => $totalPoints) {
                $this->updateUserPoints($userId, -$totalPoints);
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 直接更新用户的 points_balance 和 balance
     */
    protected function updateUserPoints($userId, $amount)
    {
        if (empty($userId) || $amount == 0) {
            return;
        }

        try {
            $connection = Db::connect('translate');
            $connection->table('ts_users')->where('id', $userId)->update([
                'points_balance' => Db::raw('points_balance + (' . floatval($amount) . ')'),
                'balance' => Db::raw('balance + (' . floatval($amount) . ')')
            ]);
        } catch (\Exception $e) {
            Log::record('[UpdateUserPoints] user=' . $userId . ' amount=' . $amount . ' error=' . $e->getMessage(), 'error');
        }
    }

    /**
     * 导出Excel
     */
    public function export_excel()
    {
        // 优先按选择的ids导出，其次按筛选条件导出
        $ids = input('get.ids', '', 'trim');
        $map = [];
        if ($ids !== '') {
            $ids_array = array_filter(explode(',', $ids));
            if (!empty($ids_array)) {
                $map[] = ['id', 'in', $ids_array];
            }
        } else {
            $map = cookie('ts_points_detail') ?: [];
        }
        
        // 获取数据
        $data_list = PointsDetailModel::where($map)
            ->order('id desc')
            ->select()
            ->toArray();
        
        if (empty($data_list)) {
            $this->error('没有数据可以导出');
        }
        
        // 来源类型映射
        $source_type_map = [
            'recharge' => '充值',
            'consume' => '消费',
            'gift' => '赠送',
            'refund' => '退款',
            'reward' => '奖励',
            'system' => '系统调整'
        ];
        
        // 处理数据，转换来源类型
        foreach ($data_list as &$item) {
            $item['source_type'] = isset($source_type_map[$item['source_type']]) ? $source_type_map[$item['source_type']] : $item['source_type'];
            $item['order_id'] = $item['order_id'] ?: '-';
        }
        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id', 10, 'ID'],
            ['user_id', 15, '用户ID'],
            ['points', 15, '积分变动'],
            ['order_id', 25, '订单号'],
            ['title', 30, '交易说明'],
            ['source_type', 15, '来源类型'],
            ['balance_before', 15, '交易前余额'],
            ['balance_after', 15, '交易后余额'],
            ['created_at', 20, '创建时间']
        ];
        
        // 调用插件导出，直接输出下载
        plugin_action('Excel/Excel/export', ['积分明细_' . date('Y-m-d_H-i-s'), $cellName, $data_list]);
    }
}

