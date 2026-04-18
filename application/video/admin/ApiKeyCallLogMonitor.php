<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\ApiKeyCallLogModel;
use app\video\model\ApiKeyDailyStatsModel;

class ApiKeyCallLogMonitor extends Admin
{
    public function index()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [30, 60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $dataList = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->order('created_at desc')
            ->limit(200)
            ->select();

        $contentHtml = $this->buildTimeRangeHtml($minutes) . $this->buildDashboardHtml();
        $js = $this->buildDashboardJs($minutes);

        return ZBuilder::make('table')
            ->setPageTitle('API Key 调用量监控')
            ->setPageTips("自动每 5 秒刷新，展示最近 {$minutes} 分钟的调用数据", 'info')
            ->setTableName('video/ApiKeyCallLogModel', 2)
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['model', '模型', 'callback', function ($value) {
                    $short = preg_replace('#^(fal-ai/|st-ai/)#', '', $value ?: '(未知)');
                    return "<span style='font-weight:bold;color:#409eff;'>{$short}</span>";
                }],
                ['channel', '渠道', 'callback', function ($value) {
                    $colors = ['api' => '#409eff', 'mcp' => '#67c23a', 'openrouter' => '#e6a23c'];
                    $color = $colors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['price', '费用(积分)', 'callback', function ($value) {
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['status', '状态', 'callback', function ($value) {
                    $colors = ['success' => '#67c23a', 'failed' => '#f56c6c', 'pending' => '#e6a23c'];
                    $color = $colors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['latency_ms', '延迟(ms)'],
                ['created_at', '创建时间'],
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

        // 1. 概览
        $overview = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field([
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending',
                'IFNULL(SUM(price), 0) as totalPrice',
                'IFNULL(SUM(CASE WHEN status = "success" THEN price ELSE 0 END), 0) as successPrice',
                'IFNULL(AVG(CASE WHEN status = "success" THEN latency_ms END), 0) as avgLatency',
                'COUNT(DISTINCT user_id) as activeUsers',
                'COUNT(DISTINCT api_key_id) as activeKeys',
            ])
            ->find();

        $total = intval($overview['total']);
        $success = intval($overview['success']);
        $failed = intval($overview['failed']);
        $pending = intval($overview['pending']);
        $totalPrice = floatval($overview['totalPrice']);
        $successPrice = floatval($overview['successPrice']);
        $avgLatency = intval($overview['avgLatency']);
        $activeUsers = intval($overview['activeUsers']);
        $activeKeys = intval($overview['activeKeys']);
        $successRate = $total > 0 ? round($success / $total * 100, 1) : 0;
        $failRate = $total > 0 ? round($failed / $total * 100, 1) : 0;

        // 2. 渠道分布
        $channelDist = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field(['channel', 'COUNT(*) as count', 'IFNULL(SUM(price), 0) as totalPrice'])
            ->group('channel')
            ->order('count desc')
            ->select();

        $channelData = [];
        foreach ($channelDist as $item) {
            $channelData[] = [
                'name'  => $item['channel'] ?: '未知',
                'value' => intval($item['count']),
                'price' => floatval($item['totalPrice']),
            ];
        }

        // 3. 模型分布 (Top 15)
        $modelDist = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field(['model', 'COUNT(*) as count'])
            ->group('model')
            ->order('count desc')
            ->limit(15)
            ->select();

        $modelData = [];
        foreach ($modelDist as $item) {
            $name = preg_replace('#^(fal-ai/|st-ai/)#', '', $item['model'] ?: '未知');
            $modelData[] = ['name' => $name, 'value' => intval($item['count'])];
        }

        // 4. 状态分布
        $statusDist = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field(['status', 'COUNT(*) as count'])
            ->group('status')
            ->select();

        $statusData = [];
        foreach ($statusDist as $item) {
            $statusData[] = ['name' => $item['status'] ?: '未知', 'value' => intval($item['count'])];
        }

        // 5. 调用趋势
        if ($minutes <= 120) {
            $groupExpr = 'DATE_FORMAT(created_at, "%H:%i")';
        } elseif ($minutes <= 1440) {
            $groupExpr = 'CONCAT(DATE_FORMAT(created_at, "%H"), ":", LPAD(FLOOR(MINUTE(created_at)/5)*5, 2, "0"))';
        } else {
            $groupExpr = 'DATE_FORMAT(created_at, "%m-%d %H:00")';
        }

        $trendRaw = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field([
                "{$groupExpr} as time_slot",
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending',
                'IFNULL(SUM(price), 0) as price',
            ])
            ->group('time_slot')
            ->order('time_slot asc')
            ->select();

        $trendTime = $trendTotal = $trendSuccess = $trendFailed = $trendPending = $trendPrice = [];
        foreach ($trendRaw as $row) {
            $trendTime[]    = $row['time_slot'];
            $trendTotal[]   = intval($row['total']);
            $trendSuccess[] = intval($row['success']);
            $trendFailed[]  = intval($row['failed']);
            $trendPending[] = intval($row['pending']);
            $trendPrice[]   = round(floatval($row['price']), 2);
        }

        // 6. 模型维度统计
        $modelStats = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field([
                'model',
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_cnt',
                'IFNULL(SUM(price), 0) as totalPrice',
                'IFNULL(SUM(CASE WHEN status = "success" THEN price ELSE 0 END), 0) as successPrice',
                'ROUND(AVG(CASE WHEN status = "success" THEN latency_ms END), 0) as avgLatency',
                'COUNT(DISTINCT user_id) as users',
            ])
            ->group('model')
            ->order('total desc')
            ->select();

        $modelStatsData = [];
        foreach ($modelStats as $ms) {
            $t = intval($ms['total']);
            $s = intval($ms['success']);
            $f = intval($ms['failed']);
            $modelStatsData[] = [
                'model'        => preg_replace('#^(fal-ai/|st-ai/)#', '', $ms['model'] ?: '(未知)'),
                'total'        => $t,
                'success'      => $s,
                'failed'       => $f,
                'pending'      => intval($ms['pending_cnt']),
                'successRate'  => $t > 0 ? round($s / $t * 100, 1) : 0,
                'failRate'     => $t > 0 ? round($f / $t * 100, 1) : 0,
                'totalPrice'   => floatval($ms['totalPrice']),
                'successPrice' => floatval($ms['successPrice']),
                'avgLatency'   => intval($ms['avgLatency']),
                'users'        => intval($ms['users']),
            ];
        }

        // 7. 最新调用列表
        $recentLogs = ApiKeyCallLogModel::where('created_at', '>=', $startTime)
            ->field('id, user_id, model, channel, price, status, latency_ms, created_at')
            ->order('created_at desc')
            ->limit(200)
            ->select();

        $logRows = [];
        foreach ($recentLogs as $log) {
            $logRows[] = [
                'id'         => $log['id'],
                'user_id'    => $log['user_id'],
                'model'      => $log['model'],
                'channel'    => $log['channel'],
                'price'      => $log['price'],
                'status'     => $log['status'],
                'latency_ms' => $log['latency_ms'],
                'created_at' => $log['created_at'],
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'overview'    => [
                    'total'        => $total,
                    'success'      => $success,
                    'failed'       => $failed,
                    'pending'      => $pending,
                    'successRate'  => $successRate,
                    'failRate'     => $failRate,
                    'totalPrice'   => $totalPrice,
                    'successPrice' => $successPrice,
                    'avgLatency'   => $avgLatency,
                    'activeUsers'  => $activeUsers,
                    'activeKeys'   => $activeKeys,
                ],
                'channelDist' => $channelData,
                'modelDist'   => $modelData,
                'statusDist'  => $statusData,
                'trend'       => [
                    'time'    => $trendTime,
                    'total'   => $trendTotal,
                    'success' => $trendSuccess,
                    'failed'  => $trendFailed,
                    'pending' => $trendPending,
                    'price'   => $trendPrice,
                ],
                'modelStats'  => $modelStatsData,
                'logs'        => $logRows,
            ],
            'minutes' => $minutes,
            'time'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function ajaxDailyData()
    {
        $days = input('param.days', 30, 'intval');
        if (!in_array($days, [7, 14, 30, 60, 90])) {
            $days = 30;
        }
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // 1. 每日趋势
        $dailyTrend = ApiKeyDailyStatsModel::where('date', '>=', $startDate)
            ->field([
                'date',
                'SUM(total_calls) as calls',
                'SUM(success_calls) as success',
                'SUM(failed_calls) as failed',
                'SUM(total_cost) as cost',
                'SUM(api_calls) as api_calls',
                'SUM(mcp_calls) as mcp_calls',
                'COUNT(DISTINCT user_id) as users',
            ])
            ->group('date')
            ->order('date asc')
            ->select();

        $trendDate = $trendCalls = $trendSuccess = $trendFailed = $trendCost = $trendUsers = [];
        $totalCalls = $totalCost = $totalUsers = 0;
        foreach ($dailyTrend as $row) {
            $trendDate[]    = $row['date'];
            $trendCalls[]   = intval($row['calls']);
            $trendSuccess[] = intval($row['success']);
            $trendFailed[]  = intval($row['failed']);
            $trendCost[]    = round(floatval($row['cost']), 2);
            $trendUsers[]   = intval($row['users']);
            $totalCalls    += intval($row['calls']);
            $totalCost     += floatval($row['cost']);
        }
        $trendArr = is_array($dailyTrend) ? $dailyTrend : $dailyTrend->toArray();
        $totalUsers = count(array_unique(array_column($trendArr, 'users')));

        // 汇总
        $summary = ApiKeyDailyStatsModel::where('date', '>=', $startDate)
            ->field([
                'SUM(total_calls) as calls',
                'SUM(success_calls) as success',
                'SUM(failed_calls) as failed',
                'SUM(total_cost) as cost',
                'SUM(api_calls) as api_calls',
                'SUM(mcp_calls) as mcp_calls',
                'COUNT(DISTINCT user_id) as users',
                'COUNT(DISTINCT api_key_id) as `keys`',
            ])
            ->find();

        // 2. 模型排行（从 model_stats JSON 中解析）
        $allRecords = ApiKeyDailyStatsModel::where('date', '>=', $startDate)
            ->field('model_stats')
            ->select();

        $modelAgg = [];
        foreach ($allRecords as $rec) {
            $stats = json_decode($rec['model_stats'], true);
            if (!is_array($stats)) continue;
            foreach ($stats as $modelName => $info) {
                $short = preg_replace('#^(fal-ai/|st-ai/)#', '', $modelName);
                if (!isset($modelAgg[$short])) {
                    $modelAgg[$short] = ['calls' => 0, 'cost' => 0];
                }
                $modelAgg[$short]['calls'] += intval($info['calls'] ?? 0);
                $modelAgg[$short]['cost']  += floatval($info['cost'] ?? 0);
            }
        }
        arsort($modelAgg);
        $modelRank = [];
        foreach (array_slice($modelAgg, 0, 20, true) as $name => $data) {
            $modelRank[] = ['model' => $name, 'calls' => $data['calls'], 'cost' => $data['cost']];
        }

        // 3. 模型饼图数据
        $modelPie = [];
        foreach (array_slice($modelAgg, 0, 15, true) as $name => $data) {
            $modelPie[] = ['name' => $name, 'value' => $data['calls']];
        }

        return json([
            'code' => 0,
            'data' => [
                'summary' => [
                    'calls'   => intval($summary['calls']),
                    'success' => intval($summary['success']),
                    'failed'  => intval($summary['failed']),
                    'cost'    => round(floatval($summary['cost']), 2),
                    'apiCalls' => intval($summary['api_calls']),
                    'mcpCalls' => intval($summary['mcp_calls']),
                    'users'   => intval($summary['users']),
                    'keys'    => intval($summary['keys']),
                ],
                'trend' => [
                    'date'    => $trendDate,
                    'calls'   => $trendCalls,
                    'success' => $trendSuccess,
                    'failed'  => $trendFailed,
                    'cost'    => $trendCost,
                    'users'   => $trendUsers,
                ],
                'modelRank' => $modelRank,
                'modelPie'  => $modelPie,
            ],
            'days' => $days,
            'time' => date('Y-m-d H:i:s'),
        ]);
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
.ak-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.ak-tabs { display: flex; gap: 0; margin-bottom: 16px; border-bottom: 2px solid #e4e7ed; }
.ak-tab {
    padding: 10px 24px; cursor: pointer; font-size: 14px; font-weight: 600;
    color: #909399; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .2s;
}
.ak-tab:hover { color: #409eff; }
.ak-tab.active { color: #409eff; border-bottom-color: #409eff; }
.ak-panel { display: none; }
.ak-panel.active { display: block; }
.ak-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ak-card {
    flex: 1; min-width: 130px; padding: 16px 20px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.ak-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ak-card .card-value { font-size: 28px; font-weight: 700; line-height: 1.2; }
.ak-card .card-label { font-size: 13px; color: #909399; margin-top: 4px; }
.ak-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.ak-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ak-trend-row { margin-bottom: 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ak-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.ak-status-bar .status-left { display: flex; align-items: center; gap: 12px; }
.ak-status-bar .status-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #67c23a;
    display: inline-block; animation: ak-pulse 1.5s infinite;
}
.ak-status-bar .status-dot.paused { background: #e6a23c; animation: none; }
@keyframes ak-pulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.ak-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px; transition: all .2s;
}
.ak-btn:hover { border-color: #409eff; color: #409eff; }
.ak-btn.active { background: #409eff; color: #fff; border-color: #409eff; }
</style>

<div class="ak-wrap">
    <!-- Tab 切换 -->
    <div class="ak-tabs">
        <div class="ak-tab active" onclick="akSwitchTab('realtime')">实时监控</div>
        <div class="ak-tab" onclick="akSwitchTab('daily')">每日报表</div>
    </div>

    <!-- ========== 实时监控面板 ========== -->
    <div id="ak-panel-realtime" class="ak-panel active">

    <!-- 概览卡片 -->
    <div class="ak-cards">
        <div class="ak-card" style="border-top-color:#409eff;">
            <div class="card-value" style="color:#409eff;" id="ak-total">-</div>
            <div class="card-label">总调用数</div>
        </div>
        <div class="ak-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ak-success">-</div>
            <div class="card-label">成功</div>
        </div>
        <div class="ak-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="ak-failed">-</div>
            <div class="card-label">失败</div>
        </div>
        <div class="ak-card" style="border-top-color:#e6a23c;">
            <div class="card-value" style="color:#e6a23c;" id="ak-pending">-</div>
            <div class="card-label">处理中</div>
        </div>
        <div class="ak-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ak-success-rate">-</div>
            <div class="card-label">成功率</div>
        </div>
        <div class="ak-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="ak-fail-rate">-</div>
            <div class="card-label">失败率</div>
        </div>
    </div>
    <!-- 消耗与活跃卡片 -->
    <div class="ak-cards">
        <div class="ak-card" style="border-top-color:#fc8452;">
            <div class="card-value" style="color:#fc8452;" id="ak-total-price">-</div>
            <div class="card-label">总消耗(积分)</div>
        </div>
        <div class="ak-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="ak-success-price">-</div>
            <div class="card-label">成功消耗(积分)</div>
        </div>
        <div class="ak-card" style="border-top-color:#409eff;">
            <div class="card-value" style="color:#409eff;" id="ak-avg-latency">-</div>
            <div class="card-label">平均延迟(ms)</div>
        </div>
        <div class="ak-card" style="border-top-color:#9b59b6;">
            <div class="card-value" style="color:#9b59b6;" id="ak-active-users">-</div>
            <div class="card-label">活跃用户数</div>
        </div>
        <div class="ak-card" style="border-top-color:#3498db;">
            <div class="card-value" style="color:#3498db;" id="ak-active-keys">-</div>
            <div class="card-label">活跃Key数</div>
        </div>
    </div>

    <!-- 饼图行：模型分布 + 状态分布 + 渠道分布 -->
    <div class="ak-charts-row">
        <div class="chart-box">
            <div id="ak-chart-model" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="ak-chart-status" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="ak-chart-channel" style="width:100%;height:340px;"></div>
        </div>
    </div>

    <!-- 趋势折线图 -->
    <div class="ak-trend-row">
        <div id="ak-chart-trend" style="width:100%;height:360px;"></div>
    </div>

    <!-- 模型统计表格 -->
    <div style="margin-bottom:16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px;">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">模型调用统计</div>
        <table id="ak-model-stats-table" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f7fa;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">模型名称</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">总调用</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">失败</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#e6a23c;font-weight:600;">处理中</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功率</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">失败率</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#409eff;font-weight:600;">平均延迟</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#9b59b6;font-weight:600;">用户数</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#fc8452;font-weight:600;">消耗(积分)</th>
                </tr>
            </thead>
            <tbody id="ak-model-stats-body">
                <tr><td colspan="10" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 自动刷新状态栏 -->
    <div class="ak-status-bar">
        <div class="status-left">
            <span class="status-dot" id="ak-status-dot"></span>
            <span id="ak-status-text">正在加载数据...</span>
            <span style="color:#909399;" id="ak-last-update-time"></span>
        </div>
        <div>
            <button class="ak-btn active" id="ak-btn-toggle" onclick="akToggleRefresh()">暂停刷新</button>
            <button class="ak-btn" id="ak-btn-refresh" onclick="akManualRefresh()">立即刷新</button>
        </div>
    </div>

    </div><!-- /ak-panel-realtime -->

    <!-- ========== 每日报表面板 ========== -->
    <div id="ak-panel-daily" class="ak-panel">

    <!-- 天数选择器 -->
    <div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;">
        <span style="margin-right:10px;font-weight:bold;font-size:13px;">统计范围：</span>
        <a href="javascript:;" onclick="akSetDays(7)" class="ak-day-btn" data-days="7" style="display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#fff;color:#606266;">最近7天</a>
        <a href="javascript:;" onclick="akSetDays(14)" class="ak-day-btn" data-days="14" style="display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#fff;color:#606266;">最近14天</a>
        <a href="javascript:;" onclick="akSetDays(30)" class="ak-day-btn active" data-days="30" style="display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#409eff;color:#fff;border-color:#409eff;">最近30天</a>
        <a href="javascript:;" onclick="akSetDays(60)" class="ak-day-btn" data-days="60" style="display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#fff;color:#606266;">最近60天</a>
        <a href="javascript:;" onclick="akSetDays(90)" class="ak-day-btn" data-days="90" style="display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#fff;color:#606266;">最近90天</a>
    </div>

    <!-- 汇总卡片 -->
    <div class="ak-cards">
        <div class="ak-card" style="border-top-color:#409eff;">
            <div class="card-value" style="color:#409eff;" id="daily-total-calls">-</div>
            <div class="card-label">总调用数</div>
        </div>
        <div class="ak-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="daily-success">-</div>
            <div class="card-label">成功</div>
        </div>
        <div class="ak-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="daily-failed">-</div>
            <div class="card-label">失败</div>
        </div>
        <div class="ak-card" style="border-top-color:#fc8452;">
            <div class="card-value" style="color:#fc8452;" id="daily-total-cost">-</div>
            <div class="card-label">总消耗(积分)</div>
        </div>
        <div class="ak-card" style="border-top-color:#9b59b6;">
            <div class="card-value" style="color:#9b59b6;" id="daily-users">-</div>
            <div class="card-label">活跃用户数</div>
        </div>
        <div class="ak-card" style="border-top-color:#3498db;">
            <div class="card-value" style="color:#3498db;" id="daily-keys">-</div>
            <div class="card-label">活跃Key数</div>
        </div>
    </div>

    <!-- 每日调用趋势 -->
    <div class="ak-trend-row">
        <div id="daily-chart-trend" style="width:100%;height:380px;"></div>
    </div>

    <!-- 每日消耗趋势 -->
    <div class="ak-trend-row">
        <div id="daily-chart-cost" style="width:100%;height:360px;"></div>
    </div>

    <!-- 模型分布 + 模型排行 -->
    <div class="ak-charts-row">
        <div class="chart-box">
            <div id="daily-chart-model" style="width:100%;height:380px;"></div>
        </div>
        <div class="chart-box" style="flex:1.2;">
            <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;padding:8px 12px;">模型消耗排行 (Top 20)</div>
            <table id="daily-model-rank" style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f5f7fa;">
                        <th style="padding:8px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">#</th>
                        <th style="padding:8px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">模型名称</th>
                        <th style="padding:8px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#409eff;font-weight:600;">调用次数</th>
                        <th style="padding:8px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#fc8452;font-weight:600;">消耗(积分)</th>
                    </tr>
                </thead>
                <tbody id="daily-model-rank-body">
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    </div><!-- /ak-panel-daily -->

</div>
HTML;
    }

    private function buildDashboardJs($minutes)
    {
        $ajaxUrl = url('ajaxData', ['minutes' => $minutes]);
        $dailyAjaxUrl = url('ajaxDailyData', ['days' => 30]);
        $minutesLabels = [
            30   => '最近 30 分钟',
            60   => '最近 1 小时',
            120  => '最近 2 小时',
            360  => '最近 6 小时',
            1440 => '最近 24 小时',
        ];
        $trendTitle = '调用趋势 (' . ($minutesLabels[$minutes] ?? "最近 {$minutes} 分钟") . ')';

        return <<<JS
<script type="text/javascript">
(function() {
    var chartModel   = echarts.init(document.getElementById('ak-chart-model'));
    var chartStatus  = echarts.init(document.getElementById('ak-chart-status'));
    var chartChannel = echarts.init(document.getElementById('ak-chart-channel'));
    var chartTrend   = echarts.init(document.getElementById('ak-chart-trend'));

    window.addEventListener('resize', function() {
        chartModel.resize(); chartStatus.resize(); chartChannel.resize(); chartTrend.resize();
    });

    var statusColorMap = { 'success': '#67c23a', 'failed': '#f56c6c', 'pending': '#e6a23c' };
    var channelColorMap = { 'api': '#409eff', 'mcp': '#67c23a', 'openrouter': '#e6a23c' };

    chartModel.setOption({
        title: { text: '模型分布 (Top 15)', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 }, type: 'scroll' },
        series: [{ name: '调用数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false }, emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            data: []
        }]
    });

    chartStatus.setOption({
        title: { text: '状态分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{ name: '调用数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false }, emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            data: []
        }]
    });

    chartChannel.setOption({
        title: { text: '渠道分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{ name: '调用数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false }, emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            data: []
        }]
    });

    chartTrend.setOption({
        title: { text: '{$trendTitle}', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis' },
        legend: { data: ['总数', '成功', '失败', '处理中', '消耗(积分)'], top: 5, right: 20 },
        grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
        toolbox: { feature: { saveAsImage: {} } },
        xAxis: { type: 'category', boundaryGap: false, data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: [
            { type: 'value', minInterval: 1, name: '调用数', nameTextStyle: { fontSize: 11 } },
            { type: 'value', name: '积分', nameTextStyle: { fontSize: 11 }, splitLine: { show: false } }
        ],
        series: [
            { name: '总数', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#409eff' }, lineStyle: { width: 2 }, areaStyle: { color: 'rgba(64,158,255,0.08)' } },
            { name: '成功', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#67c23a' }, lineStyle: { width: 2 } },
            { name: '失败', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#f56c6c' }, lineStyle: { width: 2 } },
            { name: '处理中', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#e6a23c' }, lineStyle: { width: 2 } },
            { name: '消耗(积分)', type: 'bar', yAxisIndex: 1, data: [], itemStyle: { color: 'rgba(252,132,82,0.6)' }, barMaxWidth: 20 }
        ]
    });

    function renderModelStats(models) {
        var tbody = $('#ak-model-stats-body');
        tbody.empty();
        if (!models || models.length === 0) {
            tbody.append('<tr><td colspan="10" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }
        function fmtMs(ms) {
            if (!ms || ms <= 0) return '-';
            if (ms < 1000) return ms + 'ms';
            return (ms / 1000).toFixed(1) + 's';
        }
        for (var i = 0; i < models.length; i++) {
            var m = models[i];
            var srColor = m.successRate >= 80 ? '#67c23a' : (m.successRate >= 50 ? '#e6a23c' : '#f56c6c');
            var frColor = m.failRate >= 30 ? '#f56c6c' : (m.failRate >= 10 ? '#e6a23c' : '#67c23a');
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var row = '<tr style="background:' + bg + ';transition:background .2s;" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="padding:9px 12px;border-bottom:1px solid #ebeef5;font-weight:bold;color:#409eff;">' + m.model + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;font-weight:bold;">' + m.total + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;font-weight:bold;">' + m.success + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.failed > 0 ? '#f56c6c' : '#909399') + ';font-weight:bold;">' + m.failed + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.pending > 0 ? '#e6a23c' : '#909399') + ';">' + m.pending + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + srColor + ';font-weight:bold;">' + m.successRate + '%</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + frColor + ';font-weight:bold;">' + m.failRate + '%</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#409eff;">' + fmtMs(m.avgLatency) + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#9b59b6;">' + m.users + '</td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;color:#fc8452;font-weight:bold;">' + m.totalPrice + '</td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var statusColors = { 'success': '#67c23a', 'failed': '#f56c6c', 'pending': '#e6a23c' };
    var channelColors = { 'api': '#409eff', 'mcp': '#67c23a', 'openrouter': '#e6a23c' };

    function renderTable(logs) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();
        var hasCheckbox = $('#builder-table-head thead th').first().find('input[type=checkbox]').length > 0;
        var colCount = hasCheckbox ? 10 : 9;
        if (!logs || logs.length === 0) {
            tbody.append('<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }
        for (var i = 0; i < logs.length; i++) {
            var t = logs[i];
            var modelShort = (t.model || '(未知)').replace(/^(fal-ai\/|st-ai\/)/, '');
            var sColor = statusColors[t.status] || '#909399';
            var cColor = channelColors[t.channel] || '#909399';
            var priceColor = t.price > 0 ? '#67c23a' : '#909399';
            var checkboxTd = hasCheckbox ? '<td><div class="table-cell"><input type="checkbox" name="ids[]" value="' + t.id + '"></div></td>' : '';
            var row = '<tr>'
                + checkboxTd
                + '<td><div class="table-cell">' + t.id + '</div></td>'
                + '<td><div class="table-cell">' + (t.user_id || '') + '</div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#409eff;">' + modelShort + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + cColor + ';font-weight:bold">' + (t.channel || '') + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + priceColor + '">' + (t.price || 0) + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + sColor + ';font-weight:bold">' + (t.status || '') + '</span></div></td>'
                + '<td><div class="table-cell">' + (t.latency_ms || 0) + '</div></td>'
                + '<td><div class="table-cell">' + (t.created_at || '') + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var autoRefresh = true;
    var countdown = 5;

    function fetchData() {
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;
                $('#ak-total').text(d.overview.total);
                $('#ak-success').text(d.overview.success);
                $('#ak-failed').text(d.overview.failed);
                $('#ak-pending').text(d.overview.pending);
                $('#ak-success-rate').text(d.overview.successRate + '%');
                $('#ak-fail-rate').text(d.overview.failRate + '%');
                $('#ak-total-price').text(d.overview.totalPrice);
                $('#ak-success-price').text(d.overview.successPrice);
                $('#ak-avg-latency').text(d.overview.avgLatency);
                $('#ak-active-users').text(d.overview.activeUsers);
                $('#ak-active-keys').text(d.overview.activeKeys);

                chartModel.setOption({ series: [{ data: d.modelDist }] });

                var statusPie = [];
                for (var i = 0; i < d.statusDist.length; i++) {
                    statusPie.push({ name: d.statusDist[i].name, value: d.statusDist[i].value,
                        itemStyle: { color: statusColorMap[d.statusDist[i].name] || '#909399' } });
                }
                chartStatus.setOption({ series: [{ data: statusPie }] });

                var channelPie = [];
                for (var i = 0; i < d.channelDist.length; i++) {
                    channelPie.push({ name: d.channelDist[i].name, value: d.channelDist[i].value,
                        itemStyle: { color: channelColorMap[d.channelDist[i].name] || '#909399' } });
                }
                chartChannel.setOption({ series: [{ data: channelPie }] });

                chartTrend.setOption({
                    xAxis: { data: d.trend.time },
                    series: [
                        { data: d.trend.total },
                        { data: d.trend.success },
                        { data: d.trend.failed },
                        { data: d.trend.pending },
                        { data: d.trend.price }
                    ]
                });

                renderModelStats(d.modelStats);
                renderTable(d.logs);
                $('#ak-last-update-time').text('最后更新: ' + res.time);
                $('#ak-status-text').text('数据已更新');
            },
            error: function() {
                $('#ak-status-text').text('数据请求失败，将自动重试');
            }
        });
    }

    function tick() {
        if (!autoRefresh) return;
        countdown--;
        if (countdown <= 0) { countdown = 5; fetchData(); }
        $('#ak-status-text').text('下次刷新: ' + countdown + ' 秒');
    }

    window.akToggleRefresh = function() {
        autoRefresh = !autoRefresh;
        var btn = document.getElementById('ak-btn-toggle');
        var dot = document.getElementById('ak-status-dot');
        if (autoRefresh) {
            btn.textContent = '暂停刷新'; btn.className = 'ak-btn active';
            dot.className = 'status-dot'; countdown = 5;
            $('#ak-status-text').text('已恢复自动刷新');
        } else {
            btn.textContent = '恢复刷新'; btn.className = 'ak-btn';
            dot.className = 'status-dot paused';
            $('#ak-status-text').text('自动刷新已暂停');
        }
    };

    window.akManualRefresh = function() { fetchData(); countdown = 5; };

    fetchData();
    setInterval(tick, 1000);

    // ========== Tab 切换 ==========
    window.akSwitchTab = function(tab) {
        $('.ak-tab').removeClass('active');
        $('.ak-panel').removeClass('active');
        if (tab === 'realtime') {
            $('.ak-tab').eq(0).addClass('active');
            $('#ak-panel-realtime').addClass('active');
            chartModel.resize(); chartStatus.resize(); chartChannel.resize(); chartTrend.resize();
        } else {
            $('.ak-tab').eq(1).addClass('active');
            $('#ak-panel-daily').addClass('active');
            if (!dailyInited) { initDailyCharts(); dailyInited = true; fetchDailyData(); }
            else { dailyChartTrend.resize(); dailyChartCost.resize(); dailyChartModel.resize(); }
        }
    };

    // ========== 每日报表 ==========
    var dailyInited = false;
    var dailyDays = 30;
    var dailyChartTrend, dailyChartCost, dailyChartModel;

    function initDailyCharts() {
        dailyChartTrend = echarts.init(document.getElementById('daily-chart-trend'));
        dailyChartCost  = echarts.init(document.getElementById('daily-chart-cost'));
        dailyChartModel = echarts.init(document.getElementById('daily-chart-model'));

        window.addEventListener('resize', function() {
            if (dailyChartTrend) dailyChartTrend.resize();
            if (dailyChartCost) dailyChartCost.resize();
            if (dailyChartModel) dailyChartModel.resize();
        });

        dailyChartTrend.setOption({
            title: { text: '每日调用趋势', textStyle: { fontSize: 14, color: '#303133' } },
            tooltip: { trigger: 'axis' },
            legend: { data: ['总调用', '成功', '失败', '活跃用户'], top: 5, right: 20 },
            grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
            toolbox: { feature: { saveAsImage: {} } },
            xAxis: { type: 'category', data: [], axisLabel: { fontSize: 11, rotate: 30 } },
            yAxis: [
                { type: 'value', minInterval: 1, name: '调用数' },
                { type: 'value', name: '用户数', splitLine: { show: false } }
            ],
            series: [
                { name: '总调用', type: 'bar', yAxisIndex: 0, data: [], itemStyle: { color: 'rgba(64,158,255,0.7)' }, barMaxWidth: 30 },
                { name: '成功', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#67c23a' }, lineStyle: { width: 2 } },
                { name: '失败', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#f56c6c' }, lineStyle: { width: 2 } },
                { name: '活跃用户', type: 'line', smooth: true, yAxisIndex: 1, data: [], itemStyle: { color: '#9b59b6' }, lineStyle: { width: 2, type: 'dashed' } }
            ]
        });

        dailyChartCost.setOption({
            title: { text: '每日消耗趋势 (积分)', textStyle: { fontSize: 14, color: '#303133' } },
            tooltip: { trigger: 'axis', formatter: function(p) { return p[0].axisValue + '<br/>' + p[0].marker + '消耗: ' + p[0].value.toLocaleString() + ' 积分'; } },
            grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
            toolbox: { feature: { saveAsImage: {} } },
            xAxis: { type: 'category', data: [], axisLabel: { fontSize: 11, rotate: 30 } },
            yAxis: { type: 'value', name: '积分' },
            series: [{
                name: '消耗', type: 'bar', data: [],
                itemStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{offset: 0, color: '#fc8452'}, {offset: 1, color: '#ffd7c0'}]) },
                barMaxWidth: 30
            }]
        });

        dailyChartModel.setOption({
            title: { text: '模型调用分布 (Top 15)', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 }, type: 'scroll' },
            series: [{ name: '调用数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
                itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
                label: { show: false }, emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
                data: []
            }]
        });
    }

    function fetchDailyData() {
        var dailyUrl = '{$dailyAjaxUrl}'.replace(/days=\d+/, 'days=' + dailyDays);
        $.ajax({
            url: dailyUrl,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;

                $('#daily-total-calls').text(d.summary.calls.toLocaleString());
                $('#daily-success').text(d.summary.success.toLocaleString());
                $('#daily-failed').text(d.summary.failed.toLocaleString());
                $('#daily-total-cost').text(d.summary.cost.toLocaleString());
                $('#daily-users').text(d.summary.users);
                $('#daily-keys').text(d.summary.keys);

                dailyChartTrend.setOption({
                    xAxis: { data: d.trend.date },
                    series: [
                        { data: d.trend.calls },
                        { data: d.trend.success },
                        { data: d.trend.failed },
                        { data: d.trend.users }
                    ]
                });

                dailyChartCost.setOption({
                    xAxis: { data: d.trend.date },
                    series: [{ data: d.trend.cost }]
                });

                dailyChartModel.setOption({ series: [{ data: d.modelPie }] });

                var tbody = $('#daily-model-rank-body');
                tbody.empty();
                if (d.modelRank && d.modelRank.length > 0) {
                    for (var i = 0; i < d.modelRank.length; i++) {
                        var m = d.modelRank[i];
                        var bg = i % 2 === 0 ? '#fff' : '#fafafa';
                        var rankColor = i < 3 ? '#fc8452' : '#606266';
                        tbody.append('<tr style="background:' + bg + ';" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                            + '<td style="padding:8px 12px;border-bottom:1px solid #ebeef5;color:' + rankColor + ';font-weight:bold;">' + (i+1) + '</td>'
                            + '<td style="padding:8px 12px;border-bottom:1px solid #ebeef5;color:#409eff;font-weight:bold;">' + m.model + '</td>'
                            + '<td style="padding:8px 8px;text-align:center;border-bottom:1px solid #ebeef5;">' + m.calls.toLocaleString() + '</td>'
                            + '<td style="padding:8px 8px;text-align:right;border-bottom:1px solid #ebeef5;color:#fc8452;font-weight:bold;">' + m.cost.toLocaleString() + '</td>'
                            + '</tr>');
                    }
                }
            }
        });
    }

    window.akSetDays = function(days) {
        dailyDays = days;
        $('.ak-day-btn').each(function() {
            if ($(this).data('days') == days) {
                $(this).css({background:'#409eff', color:'#fff', borderColor:'#409eff'});
            } else {
                $(this).css({background:'#fff', color:'#606266', borderColor:'#dcdfe6'});
            }
        });
        fetchDailyData();
    };

})();
</script>
JS;
    }
}
