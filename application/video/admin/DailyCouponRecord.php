<?php
// 每日优惠券派发记录管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DailyCouponRecordModel;
use app\video\model\CouponTemplateModel;

class DailyCouponRecord extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = DailyCouponRecordModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_daily_coupon_record', $map);
        
        // 统计每日派发记录信息
        $stats = [
            'total_records' => DailyCouponRecordModel::count(),
            'today_records' => DailyCouponRecordModel::whereTime('date', 'today')->count(),
            'yesterday_records' => DailyCouponRecordModel::whereTime('date', 'yesterday')->count(),
            'this_week_records' => DailyCouponRecordModel::whereTime('date', 'week')->count(),
            'this_month_records' => DailyCouponRecordModel::whereTime('date', 'month')->count(),
            'unique_users' => DailyCouponRecordModel::count('DISTINCT user_id'),
            'active_users_today' => DailyCouponRecordModel::whereTime('date', 'today')->count('DISTINCT user_id'),
            'active_users_week' => DailyCouponRecordModel::whereTime('date', 'week')->count('DISTINCT user_id'),
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("总记录数：{$stats['total_records']} | 今日记录：{$stats['today_records']} | 昨日记录：{$stats['yesterday_records']} | 本周记录：{$stats['this_week_records']} | 本月记录：{$stats['this_month_records']} | 参与用户数：{$stats['unique_users']} | 今日活跃：{$stats['active_users_today']} | 本周活跃：{$stats['active_users_week']}")
            ->setTableName('video/DailyCouponRecordModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['date', '日期'],
                ['template_codes', '领取的优惠券', 'callback', function($value){
                    if(empty($value)) return '无记录';
                    $codes = json_decode($value, true);
                    if (!is_array($codes)) return '数据格式错误';
                    
                    $count = count($codes);
                    $display_codes = array_slice($codes, 0, 3);
                    $display_text = implode(', ', $display_codes);
                    
                    if ($count > 3) {
                        $display_text .= "... (共{$count}个)";
                    }
                    
                    return "<span title='" . implode(', ', $codes) . "'>{$display_text}</span>";
                }],
                ['created_at', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['date', 'date', '日期'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'date', '日期范围', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增记录']) // 添加新增按钮
            ->addTopButton('custom', ['title' => '统计分析', 'href' => url('statistics')]) // 添加统计分析按钮
            ->addTopButton('custom', ['title' => '清理数据', 'href' => url('cleanup'), 'class' => 'btn btn-warning']) // 添加清理数据按钮
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
                if (strpos($param, 'date=') === 0) {
                    $daterangeValue = substr($param, strlen('date='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
        } else {
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d');
        }

        $data_list_time = DailyCouponRecordModel::whereBetween('date', [$startDate, $endDate])
        ->field([
            'date as axisValue',
            'COUNT(*) as record_count',
            'COUNT(DISTINCT user_id) as unique_users'
        ])
        ->group('date')
        ->order('date asc')
        ->select();

        $x_data = array();
        $y_data_records = array();
        $y_data_users = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_records, $value['record_count']);
            array_push($y_data_users, $value['unique_users']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_records_json = json_encode($y_data_records);
        $y_data_users_json = json_encode($y_data_users);

        $display_date = $daterangeValue ? $daterangeValue : ($startDate . ' - ' . $endDate);
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '每日优惠券派发记录趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['派发记录数', '活跃用户数']
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
                    name: '派发记录数',
                    type: 'line',
                    data: {$y_data_records_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {color: '#409eff'}
                },
                {
                    name: '活跃用户数',
                    type: 'line',
                    data: {$y_data_users_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {color: '#67c23a'}
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
            
            // 验证用户ID和日期的唯一性
            $exists = DailyCouponRecordModel::where('user_id', $data['user_id'])
                ->where('date', $data['date'])
                ->find();
            if ($exists) {
                $this->error('该用户在此日期已有记录，请编辑现有记录');
            }
            
            // 验证template_codes JSON格式
            if (!empty($data['template_codes'])) {
                $codes = json_decode($data['template_codes'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('优惠券模板代码JSON格式错误');
                }
                if (!is_array($codes)) {
                    $this->error('优惠券模板代码必须是数组格式');
                }
            } else {
                $data['template_codes'] = '[]'; // 默认空数组
            }
            
            if (DailyCouponRecordModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增每日派发记录') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['date', 'date', '日期', '请选择日期', date('Y-m-d'), 'required'],
                ['textarea', 'template_codes', '优惠券模板代码JSON', '请输入已领取的优惠券模板代码列表<br>JSON格式示例：["COUPON001", "COUPON002"]', '[]', '', 'rows="4"'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            
            // 验证用户ID和日期的唯一性（排除当前记录）
            $exists = DailyCouponRecordModel::where('user_id', $data['user_id'])
                ->where('date', $data['date'])
                ->where('id', 'neq', $id)
                ->find();
            if ($exists) {
                $this->error('该用户在此日期已有其他记录');
            }
            
            // 验证template_codes JSON格式
            if (!empty($data['template_codes'])) {
                $codes = json_decode($data['template_codes'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('优惠券模板代码JSON格式错误');
                }
                if (!is_array($codes)) {
                    $this->error('优惠券模板代码必须是数组格式');
                }
            }
            
            if (DailyCouponRecordModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DailyCouponRecordModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑每日派发记录') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['date', 'date', '日期', '请选择日期', '', 'required'],
                ['textarea', 'template_codes', '优惠券模板代码JSON', '请输入已领取的优惠券模板代码列表<br>JSON格式示例：["COUPON001", "COUPON002"]', '', '', 'rows="4"'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = DailyCouponRecordModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function statistics()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            
            // 基础统计
            $basic_stats = [
                'total_records' => DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])->count(),
                'unique_users' => DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])->count('DISTINCT user_id'),
                'avg_daily_records' => 0,
                'max_daily_records' => 0,
                'min_daily_records' => 0,
            ];
            
            // 每日统计
            $daily_stats = DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])
                ->field([
                    'date',
                    'COUNT(*) as daily_count',
                    'COUNT(DISTINCT user_id) as daily_users'
                ])
                ->group('date')
                ->order('date asc')
                ->select();
            
            if (!empty($daily_stats)) {
                $daily_counts = array_column($daily_stats->toArray(), 'daily_count');
                $basic_stats['avg_daily_records'] = round(array_sum($daily_counts) / count($daily_counts), 2);
                $basic_stats['max_daily_records'] = max($daily_counts);
                $basic_stats['min_daily_records'] = min($daily_counts);
            }
            
            // 用户活跃度统计
            $user_activity = DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])
                ->field([
                    'user_id',
                    'COUNT(*) as activity_days',
                    'COUNT(DISTINCT date) as unique_days'
                ])
                ->group('user_id')
                ->order('activity_days desc')
                ->limit(10)
                ->select();
            
            // 优惠券模板统计
            $template_stats = [];
            $records = DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])
                ->field('template_codes')
                ->select();
            
            $template_count = [];
            foreach ($records as $record) {
                $codes = json_decode($record['template_codes'], true);
                if (is_array($codes)) {
                    foreach ($codes as $code) {
                        $template_count[$code] = isset($template_count[$code]) ? $template_count[$code] + 1 : 1;
                    }
                }
            }
            arsort($template_count);
            $template_stats = array_slice($template_count, 0, 10, true);
            
            $result = [
                'basic_stats' => $basic_stats,
                'daily_stats' => $daily_stats,
                'user_activity' => $user_activity,
                'template_stats' => $template_stats
            ];
            
            $this->success('统计完成', '', $result);
        }

        return ZBuilder::make('form')
            ->setPageTitle('派发记录统计分析')
            ->addFormItems([
                ['date', 'start_date', '开始日期', '请选择开始日期', date('Y-m-d', strtotime('-30 days')), 'required'],
                ['date', 'end_date', '结束日期', '请选择结束日期', date('Y-m-d'), 'required'],
                ['static', 'info', '说明', '统计指定日期范围内的派发记录数据，包括：<br>
                    • 基础统计：总记录数、参与用户数、日均记录数等<br>
                    • 每日明细：每日记录数和活跃用户数<br>
                    • 用户活跃度：最活跃的前10名用户<br>
                    • 优惠券模板：最受欢迎的前10个模板'],
            ])
            ->setAjax(false)
            ->fetch();
    }

    public function cleanup()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $action = $data['action'];
            $days = (int)$data['days'];
            
            if ($days <= 0) {
                $this->error('清理天数必须大于0');
            }
            
            $cleanup_date = date('Y-m-d', strtotime("-{$days} days"));
            
            if ($action == 'check') {
                // 检查要清理的记录数量
                $count = DailyCouponRecordModel::where('date', '<', $cleanup_date)->count();
                $this->success("发现 {$count} 条 {$cleanup_date} 之前的记录");
            } elseif ($action == 'cleanup') {
                // 执行清理
                $result = DailyCouponRecordModel::where('date', '<', $cleanup_date)->delete();
                $this->success("成功清理 {$result} 条历史记录", url('index'));
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('清理历史数据')
            ->addFormItems([
                ['number', 'days', '清理天数', '清理多少天前的数据', 90, 'required'],
                ['radio', 'action', '操作类型', '选择要执行的操作', ['check' => '检查数量', 'cleanup' => '执行清理'], 'check'],
                ['static', 'warning', '警告', '<span style="color:red;">清理操作不可逆，请谨慎操作！<br>建议先选择"检查数量"确认要清理的数据量</span>'],
            ])
            ->fetch();
    }

    public function getUserCouponHistory($user_id)
    {
        // 获取用户的优惠券派发历史
        $records = DailyCouponRecordModel::where('user_id', $user_id)
            ->order('date desc')
            ->limit(30)
            ->select();
        
        $history = [];
        foreach ($records as $record) {
            $codes = json_decode($record['template_codes'], true);
            $history[] = [
                'date' => $record['date'],
                'coupon_count' => is_array($codes) ? count($codes) : 0,
                'template_codes' => is_array($codes) ? $codes : [],
                'created_at' => $record['created_at']
            ];
        }
        
        return json($history);
    }

    public function getPopularTemplates($days = 7)
    {
        // 获取最受欢迎的优惠券模板
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');
        
        $records = DailyCouponRecordModel::whereBetween('date', [$start_date, $end_date])
            ->field('template_codes')
            ->select();
        
        $template_count = [];
        foreach ($records as $record) {
            $codes = json_decode($record['template_codes'], true);
            if (is_array($codes)) {
                foreach ($codes as $code) {
                    $template_count[$code] = isset($template_count[$code]) ? $template_count[$code] + 1 : 1;
                }
            }
        }
        
        arsort($template_count);
        $popular_templates = array_slice($template_count, 0, 10, true);
        
        return json($popular_templates);
    }
} 
