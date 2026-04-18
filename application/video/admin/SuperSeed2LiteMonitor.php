<?php
// SuperSeed2 Lite 模型实时监控仪表盘
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalTasksModel;

class SuperSeed2LiteMonitor extends Admin {

    protected $appName = 'st-ai/super-seed2-lite';

    public function index()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [30, 60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $rawList = FalTasksModel::where('app_name', $this->appName)
            ->where('created_at', '>=', $startTime)
            ->order('created_at desc')
            ->limit(200)
            ->select();

        $dataList = [];
        foreach ($rawList as $row) {
            $item = $row->toArray();
            $item['_detail'] = $this->parseDetail($item['status'], $item['output_params'] ?? '');
            $dataList[] = $item;
        }

        $contentHtml = $this->buildTimeRangeHtml($minutes) . $this->buildDashboardHtml();
        $js = $this->buildDashboardJs($minutes);

        return ZBuilder::make('table')
            ->setPageTitle('SuperSeed2 Lite 实时监控')
            ->setPageTips("自动每 5 秒刷新，展示最近 {$minutes} 分钟的 SuperSeed2 Lite 任务数据", 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['id', 'ID'],
                ['task_id', 'Task ID', 'callback', function($value){
                    $escaped = htmlspecialchars($value);
                    return "<span style='display:inline-flex;align-items:center;gap:4px;'><span class='ss2l-taskid' title='{$escaped}' style='max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;font-size:12px;font-family:monospace;'>{$escaped}</span><button type='button' class='ss2l-copy-btn' data-copy='{$escaped}' style='border:none;background:#ecf5ff;color:#409eff;cursor:pointer;border-radius:3px;padding:1px 6px;font-size:12px;line-height:1.5;' title='复制'>复制</button></span>";
                }],
                ['user_id', '用户ID'],
                ['money', '金额(分)', 'callback', function($value){
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['status', '状态', 'callback', function($value){
                    $colors = ['completed'=>'#67c23a','failed'=>'#f56c6c','pending'=>'#e6a23c','submitting'=>'#e6a23c','generating'=>'#409eff','collecting'=>'#9b59b6','transferring'=>'#3498db'];
                    $color = $colors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['_detail', '详情', 'callback', function($value){
                    return $value ?: '-';
                }],
                ['is_refund', '退款', 'callback', function($value){
                    return $value ? '<span style="color:#f56c6c">已退款</span>' : '-';
                }],
                ['created_at', '创建时间'],
                ['completed_at', '完成时间'],
            ])
            ->setRowList($dataList)
            ->setExtraJs($js)
            ->fetch();
    }

    public function ajaxData()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [30, 60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $overview = FalTasksModel::where('app_name', $this->appName)
            ->where('created_at', '>=', $startTime)
            ->field([
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as waiting',
                'SUM(CASE WHEN status IN ("submitting","generating","collecting","transferring") THEN 1 ELSE 0 END) as processing',
                'IFNULL(SUM(money), 0) as totalMoney',
                'IFNULL(SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END), 0) as refundMoney',
                'ROUND(AVG(CASE WHEN status = "completed" AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, completed_at) END), 1) as avgSuccessSec',
                'ROUND(AVG(CASE WHEN status IN ("pending","submitting","generating","collecting","transferring") THEN TIMESTAMPDIFF(SECOND, created_at, NOW()) END), 1) as avgWaitingSec',
            ])
            ->find();

        $total = intval($overview['total']);
        $success = intval($overview['success']);
        $failed = intval($overview['failed']);
        $waiting = intval($overview['waiting']);
        $processing = intval($overview['processing']);
        $totalMoney = intval($overview['totalMoney']);
        $refundMoney = intval($overview['refundMoney']);
        $netMoney = $totalMoney - $refundMoney;
        $successRate = $total > 0 ? round($success / $total * 100, 1) : 0;
        $failRate = $total > 0 ? round($failed / $total * 100, 1) : 0;
        $avgSuccessSec = floatval($overview['avgSuccessSec']);
        $avgWaitingSec = floatval($overview['avgWaitingSec']);

        $uniqueUsers = FalTasksModel::where('app_name', $this->appName)
            ->where('created_at', '>=', $startTime)
            ->field('COUNT(DISTINCT user_id) as cnt')
            ->find();
        $uniqueUserCount = intval($uniqueUsers['cnt']);

        if ($minutes <= 120) {
            $groupExpr = 'DATE_FORMAT(created_at, "%H:%i")';
        } elseif ($minutes <= 1440) {
            $groupExpr = 'CONCAT(DATE_FORMAT(created_at, "%H"), ":", LPAD(FLOOR(MINUTE(created_at)/5)*5, 2, "0"))';
        } else {
            $groupExpr = 'DATE_FORMAT(created_at, "%m-%d %H:00")';
        }

        $trendRaw = FalTasksModel::where('app_name', $this->appName)
            ->where('created_at', '>=', $startTime)
            ->field([
                "{$groupExpr} as time_minute",
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status IN ("submitting","generating","collecting","transferring") THEN 1 ELSE 0 END) as waiting',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
            ])
            ->group('time_minute')
            ->order('time_minute asc')
            ->select();

        $trendTime = [];
        $trendTotal = [];
        $trendSuccess = [];
        $trendFailed = [];
        $trendWaiting = [];
        $trendMoney = [];

        foreach ($trendRaw as $row) {
            $trendTime[] = $row['time_minute'];
            $trendTotal[] = intval($row['total']);
            $trendSuccess[] = intval($row['success']);
            $trendFailed[] = intval($row['failed']);
            $trendWaiting[] = intval($row['waiting']);
            $trendMoney[] = round(intval($row['net_money']) / 100, 1);
        }

        $recentTasks = FalTasksModel::where('app_name', $this->appName)
            ->where('created_at', '>=', $startTime)
            ->field('id, task_id, user_id, money, status, is_refund, created_at, completed_at, output_params')
            ->order('created_at desc')
            ->limit(200)
            ->select();

        $taskRows = [];
        foreach ($recentTasks as $task) {
            $output = $task['output_params'] ? json_decode($task['output_params'], true) : null;
            $detail = '';
            if ($task['status'] === 'failed' && $output && isset($output['error'])) {
                $detail = $output['error'];
            } elseif (in_array($task['status'], ['generating', 'submitting']) && $output && isset($output['queue_info'])) {
                $q = $output['queue_info'];
                $detail = json_encode($q, JSON_UNESCAPED_UNICODE);
            }
            $taskRows[] = [
                'id'           => $task['id'],
                'task_id'      => $task['task_id'] ?? '',
                'user_id'      => $task['user_id'],
                'money'        => $task['money'],
                'status'       => $task['status'],
                'is_refund'    => $task['is_refund'],
                'created_at'   => $task['created_at'],
                'completed_at' => $task['completed_at'],
                'detail'       => $detail,
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'total'        => $total,
                    'success'      => $success,
                    'failed'       => $failed,
                    'waiting'      => $waiting,
                    'processing'   => $processing,
                    'successRate'  => $successRate,
                    'failRate'     => $failRate,
                    'totalMoney'   => $totalMoney,
                    'refundMoney'  => $refundMoney,
                    'netMoney'     => $netMoney,
                    'netMoneyYuan' => round($netMoney / 100, 2),
                    'uniqueUsers'  => $uniqueUserCount,
                    'avgSuccessSec' => $avgSuccessSec,
                    'avgWaitingSec' => $avgWaitingSec,
                ],
                'trend'      => [
                    'time'    => $trendTime,
                    'total'   => $trendTotal,
                    'success' => $trendSuccess,
                    'failed'  => $trendFailed,
                    'waiting' => $trendWaiting,
                    'money'   => $trendMoney,
                ],
                'tasks' => $taskRows,
            ],
            'minutes' => $minutes,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    private function parseDetail($status, $outputParams)
    {
        if (empty($outputParams)) return '';
        $json = json_decode($outputParams, true);
        if (!$json) return '';

        if ($status === 'failed' && isset($json['error'])) {
            $err = htmlspecialchars($json['error']);
            return "<span style='color:#f56c6c;font-size:12px;' title='{$err}'>{$err}</span>";
        }
        if (in_array($status, ['generating', 'submitting']) && isset($json['queue_info'])) {
            $q = $json['queue_info'];
            $idx = $q['queue_idx'] ?? '-';
            $len = $q['queue_length'] ?? '-';
            $genCost = isset($q['forecast_generate_cost']) ? round($q['forecast_generate_cost']) . 's' : '-';
            $queueCost = isset($q['forecast_queue_cost']) ? round($q['forecast_queue_cost']) . 's' : '-';
            return "<span style='font-size:12px;color:#409eff;'>排队 {$idx}/{$len} | 预计生成 {$genCost} 排队 {$queueCost}</span>";
        }
        return '';
    }

    private function buildTimeRangeHtml($currentMinutes)
    {
        $options = [
            30   => '最近30分钟',
            60   => '最近1小时',
            120  => '最近2小时',
            360  => '最近6小时',
            1440 => '最近24小时',
        ];

        $html = '<div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;">';
        $html .= '<span style="margin-right:10px;font-weight:bold;font-size:13px;">时间范围：</span>';
        foreach ($options as $m => $label) {
            $active = ($m == $currentMinutes);
            $style = $active
                ? 'background:#409eff;color:#fff;border-color:#409eff;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['minutes' => $m]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;transition:all .2s;{$style}'>{$label}</a>";
        }
        $html .= '</div>';
        return $html;
    }

    private function buildDashboardHtml()
    {
        return <<<'HTML'
<style>
.ss2l-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.ss2l-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ss2l-card {
    flex: 1; min-width: 130px; padding: 16px 20px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.ss2l-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ss2l-card .card-value { font-size: 28px; font-weight: 700; line-height: 1.2; }
.ss2l-card .card-label { font-size: 13px; color: #909399; margin-top: 4px; }
.ss2l-card.success { border-top-color: #67c23a; }
.ss2l-card.success .card-value { color: #67c23a; }
.ss2l-card.danger { border-top-color: #f56c6c; }
.ss2l-card.danger .card-value { color: #f56c6c; }
.ss2l-card.warning { border-top-color: #e6a23c; }
.ss2l-card.warning .card-value { color: #e6a23c; }
.ss2l-card.info { border-top-color: #409eff; }
.ss2l-card.info .card-value { color: #409eff; }
.ss2l-trend-row { margin-bottom: 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ss2l-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.ss2l-status-bar .status-left { display: flex; align-items: center; gap: 12px; }
.ss2l-status-bar .status-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #67c23a;
    display: inline-block; animation: ss2l-pulse 1.5s infinite;
}
.ss2l-status-bar .status-dot.paused { background: #e6a23c; animation: none; }
@keyframes ss2l-pulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.ss2l-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px;
    transition: all .2s;
}
.ss2l-btn:hover { border-color: #409eff; color: #409eff; }
.ss2l-btn.active { background: #409eff; color: #fff; border-color: #409eff; }
</style>

<div class="ss2l-wrap">
    <div class="ss2l-cards">
        <div class="ss2l-card info">
            <div class="card-value" id="ss2l-total">-</div>
            <div class="card-label">总任务数</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#9b59b6;">
            <div class="card-value" style="color:#9b59b6;" id="ss2l-users">-</div>
            <div class="card-label">使用用户数</div>
        </div>
        <div class="ss2l-card success">
            <div class="card-value" id="ss2l-success">-</div>
            <div class="card-label">成功</div>
        </div>
        <div class="ss2l-card danger">
            <div class="card-value" id="ss2l-failed">-</div>
            <div class="card-label">失败</div>
        </div>
        <div class="ss2l-card warning">
            <div class="card-value" id="ss2l-waiting">-</div>
            <div class="card-label">等待中</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#409eff;">
            <div class="card-value" style="color:#409eff;" id="ss2l-processing">-</div>
            <div class="card-label">处理中</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ss2l-success-rate">-</div>
            <div class="card-label">成功率</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="ss2l-fail-rate">-</div>
            <div class="card-label">失败率</div>
        </div>
    </div>
    <div class="ss2l-cards">
        <div class="ss2l-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ss2l-avg-success-sec">-</div>
            <div class="card-label">平均成功耗时</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#e6a23c;">
            <div class="card-value" style="color:#e6a23c;" id="ss2l-avg-wait-sec">-</div>
            <div class="card-label">平均等待耗时</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#fc8452;">
            <div class="card-value" style="color:#fc8452;" id="ss2l-total-money">-</div>
            <div class="card-label">总扣费(积分)</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="ss2l-refund-money">-</div>
            <div class="card-label">已退款(积分)</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ss2l-net-money">-</div>
            <div class="card-label">净消耗(积分)</div>
        </div>
        <div class="ss2l-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ss2l-net-money-yuan">-</div>
            <div class="card-label">净消耗(元)</div>
        </div>
    </div>

    <div class="ss2l-trend-row">
        <div id="ss2l-chart-trend" style="width:100%;height:360px;"></div>
    </div>

    <div class="ss2l-status-bar">
        <div class="status-left">
            <span class="status-dot" id="ss2l-status-dot"></span>
            <span id="ss2l-status-text">正在加载数据...</span>
            <span style="color:#909399;" id="ss2l-last-update"></span>
        </div>
        <div>
            <button class="ss2l-btn active" id="ss2l-btn-toggle" onclick="ss2lToggleRefresh()">暂停刷新</button>
            <button class="ss2l-btn" id="ss2l-btn-refresh" onclick="ss2lManualRefresh()">立即刷新</button>
        </div>
    </div>
</div>
HTML;
    }

    private function buildDashboardJs($minutes)
    {
        $ajaxUrl = url('ajaxData', ['minutes' => $minutes]);

        $minutesLabels = [
            30   => '最近 30 分钟',
            60   => '最近 1 小时',
            120  => '最近 2 小时',
            360  => '最近 6 小时',
            1440 => '最近 24 小时',
        ];
        $trendTitle = 'SuperSeed2 Lite 任务趋势 (' . ($minutesLabels[$minutes] ?? "最近 {$minutes} 分钟") . ')';

        return <<<JS
<script type="text/javascript">
(function() {
    var chartTrend  = echarts.init(document.getElementById('ss2l-chart-trend'));

    window.addEventListener('resize', function() {
        chartTrend.resize();
    });

    chartTrend.setOption({
        title: { text: '{$trendTitle}', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis' },
        legend: { data: ['总数', '成功', '失败', '处理中', '消耗(元)'], top: 5, right: 20 },
        grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
        toolbox: { feature: { saveAsImage: {} } },
        xAxis: { type: 'category', boundaryGap: false, data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: [
            { type: 'value', minInterval: 1, name: '任务数', nameTextStyle: { fontSize: 11 } },
            { type: 'value', name: '元', nameTextStyle: { fontSize: 11 }, splitLine: { show: false }, axisLabel: { formatter: '¥{value}' } }
        ],
        series: [
            { name: '总数', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#409eff' }, lineStyle: { color: '#409eff', width: 2 }, areaStyle: { color: 'rgba(64,158,255,0.08)' } },
            { name: '成功', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#67c23a' }, lineStyle: { color: '#67c23a', width: 2 } },
            { name: '失败', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#f56c6c' }, lineStyle: { color: '#f56c6c', width: 2 } },
            { name: '处理中', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#e6a23c' }, lineStyle: { color: '#e6a23c', width: 2 } },
            { name: '消耗(元)', type: 'bar', yAxisIndex: 1, data: [], itemStyle: { color: 'rgba(252,132,82,0.6)' }, barMaxWidth: 20 }
        ]
    });

    function fmtSec(s) {
        if (!s || s <= 0) return '-';
        if (s < 60) return s.toFixed(1) + 's';
        if (s < 3600) return (s / 60).toFixed(1) + 'min';
        return (s / 3600).toFixed(1) + 'h';
    }

    var statusColors = { 'completed': '#67c23a', 'failed': '#f56c6c', 'pending': '#e6a23c', 'submitting': '#e6a23c', 'generating': '#409eff', 'collecting': '#9b59b6', 'transferring': '#3498db' };

    function renderDetail(t) {
        if (!t.detail) return '-';
        if (t.status === 'failed') {
            var err = $('<span>').text(t.detail).html();
            return '<span style="color:#f56c6c;font-size:12px;" title="' + err + '">' + err + '</span>';
        }
        if (t.status === 'generating' || t.status === 'submitting') {
            try {
                var q = (typeof t.detail === 'string') ? JSON.parse(t.detail) : t.detail;
                var idx = q.queue_idx || '-';
                var len = q.queue_length || '-';
                var genCost = q.forecast_generate_cost ? Math.round(q.forecast_generate_cost) + 's' : '-';
                var qCost = q.forecast_queue_cost ? Math.round(q.forecast_queue_cost) + 's' : '-';
                return '<span style="font-size:12px;color:#409eff;">排队 ' + idx + '/' + len + ' | 预计生成 ' + genCost + ' 排队 ' + qCost + '</span>';
            } catch(e) {
                return '<span style="font-size:12px;color:#909399;">' + t.detail + '</span>';
            }
        }
        return '-';
    }

    function renderTable(tasks) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();

        var hasCheckbox = $('#builder-table-head thead th').first().find('input[type=checkbox]').length > 0;
        var colCount = hasCheckbox ? 10 : 9;

        if (!tasks || tasks.length === 0) {
            tbody.append('<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        for (var i = 0; i < tasks.length; i++) {
            var t = tasks[i];
            var sColor = statusColors[t.status] || '#909399';
            var moneyColor = t.money > 0 ? '#67c23a' : '#909399';
            var refundHtml = t.is_refund == 1 ? '<span style="color:#f56c6c">已退款</span>' : '-';
            var checkboxTd = hasCheckbox ? '<td><div class="table-cell"><input type="checkbox" name="ids[]" value="' + t.id + '"></div></td>' : '';
            var detailHtml = renderDetail(t);

            var taskIdEsc = $('<span>').text(t.task_id || '').html();
            var taskIdCell = '<span style="display:inline-flex;align-items:center;gap:4px;">'
                + '<span style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;font-size:12px;font-family:monospace;" title="' + taskIdEsc + '">' + taskIdEsc + '</span>'
                + '<button type="button" class="ss2l-copy-btn" data-copy="' + taskIdEsc + '" style="border:none;background:#ecf5ff;color:#409eff;cursor:pointer;border-radius:3px;padding:1px 6px;font-size:12px;line-height:1.5;" title="复制">复制</button>'
                + '</span>';

            var row = '<tr>'
                + checkboxTd
                + '<td><div class="table-cell">' + t.id + '</div></td>'
                + '<td><div class="table-cell">' + taskIdCell + '</div></td>'
                + '<td><div class="table-cell">' + (t.user_id || '') + '</div></td>'
                + '<td><div class="table-cell"><span style="color:' + moneyColor + '">' + (t.money || 0) + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + sColor + ';font-weight:bold">' + (t.status || '') + '</span></div></td>'
                + '<td><div class="table-cell">' + detailHtml + '</div></td>'
                + '<td><div class="table-cell">' + refundHtml + '</div></td>'
                + '<td><div class="table-cell">' + (t.created_at || '') + '</div></td>'
                + '<td><div class="table-cell">' + (t.completed_at || '-') + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var autoRefresh = true;
    var countdown = 5;
    var timer = null;

    function fetchData() {
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;

                $('#ss2l-total').text(d.overview.total);
                $('#ss2l-users').text(d.overview.uniqueUsers);
                $('#ss2l-success').text(d.overview.success);
                $('#ss2l-failed').text(d.overview.failed);
                $('#ss2l-waiting').text(d.overview.waiting);
                $('#ss2l-processing').text(d.overview.processing);
                $('#ss2l-success-rate').text(d.overview.successRate + '%');
                $('#ss2l-fail-rate').text(d.overview.failRate + '%');
                $('#ss2l-avg-success-sec').text(fmtSec(d.overview.avgSuccessSec));
                $('#ss2l-avg-wait-sec').text(fmtSec(d.overview.avgWaitingSec));
                $('#ss2l-total-money').text(d.overview.totalMoney);
                $('#ss2l-refund-money').text(d.overview.refundMoney);
                $('#ss2l-net-money').text(d.overview.netMoney);
                $('#ss2l-net-money-yuan').text('¥' + d.overview.netMoneyYuan);

                chartTrend.setOption({
                    xAxis: { data: d.trend.time },
                    series: [
                        { data: d.trend.total },
                        { data: d.trend.success },
                        { data: d.trend.failed },
                        { data: d.trend.waiting },
                        { data: d.trend.money }
                    ]
                });

                renderTable(d.tasks);

                $('#ss2l-last-update').text('最后更新: ' + res.time);
                $('#ss2l-status-text').text('数据已更新');
            },
            error: function() {
                $('#ss2l-status-text').text('数据请求失败，将自动重试');
            }
        });
    }

    function tick() {
        if (!autoRefresh) return;
        countdown--;
        if (countdown <= 0) {
            countdown = 5;
            fetchData();
        }
        $('#ss2l-status-text').text('下次刷新: ' + countdown + ' 秒');
    }

    window.ss2lToggleRefresh = function() {
        autoRefresh = !autoRefresh;
        var btn = document.getElementById('ss2l-btn-toggle');
        var dot = document.getElementById('ss2l-status-dot');
        if (autoRefresh) {
            btn.textContent = '暂停刷新';
            btn.className = 'ss2l-btn active';
            dot.className = 'status-dot';
            countdown = 5;
            $('#ss2l-status-text').text('已恢复自动刷新');
        } else {
            btn.textContent = '恢复刷新';
            btn.className = 'ss2l-btn';
            dot.className = 'status-dot paused';
            $('#ss2l-status-text').text('自动刷新已暂停');
        }
    };

    window.ss2lManualRefresh = function() {
        fetchData();
        countdown = 5;
    };

    $(document).on('click', '.ss2l-copy-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var text = $(this).data('copy');
        var btn = $(this);
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                btn.text('已复制').css({ background: '#f0f9eb', color: '#67c23a' });
                setTimeout(function() { btn.text('复制').css({ background: '#ecf5ff', color: '#409eff' }); }, 1500);
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            btn.text('已复制').css({ background: '#f0f9eb', color: '#67c23a' });
            setTimeout(function() { btn.text('复制').css({ background: '#ecf5ff', color: '#409eff' }); }, 1500);
        }
    });

    fetchData();
    timer = setInterval(tick, 1000);
})();
</script>
JS;
    }
}
