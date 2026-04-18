<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalTasksModel;

class FalUserStats extends Admin {

    public function index()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $dataList = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'user_id',
                'COUNT(*) as task_count',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count',
                'SUM(CASE WHEN status IN ("pending","processing","generating","submitting") THEN 1 ELSE 0 END) as waiting_count',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
                'IFNULL(SUM(money), 0) as total_money',
                'IFNULL(SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END), 0) as refund_money',
                'MAX(created_at) as last_task_time',
            ])
            ->group('user_id')
            ->order('task_count desc')
            ->limit(500)
            ->select();

        $contentHtml = $this->buildTimeRangeHtml($minutes) . $this->buildDashboardHtml();
        $js = $this->buildDashboardJs($minutes);

        return ZBuilder::make('table')
            ->setPageTitle('Fal 用户消耗统计')
            ->setPageTips("按 user_id 分组统计最近时段内的 Fal 任务数与消耗", 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['user_id', '用户ID'],
                ['task_count', '任务总数', 'callback', function($value){
                    return "<span style='font-weight:bold;color:#409eff;'>{$value}</span>";
                }],
                ['success_count', '成功', 'callback', function($value){
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['failed_count', '失败', 'callback', function($value){
                    $color = $value > 0 ? '#f56c6c' : '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['waiting_count', '进行中', 'callback', function($value){
                    $color = $value > 0 ? '#e6a23c' : '#909399';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['total_money', '总扣费', 'callback', function($value){
                    $yuan = round($value / 100, 2);
                    return "<span style='color:#fc8452;font-weight:bold'>¥{$yuan}</span>";
                }],
                ['refund_money', '退款', 'callback', function($value){
                    $yuan = round($value / 100, 2);
                    $color = $value > 0 ? '#f56c6c' : '#909399';
                    return "<span style='color:{$color};font-weight:bold'>¥{$yuan}</span>";
                }],
                ['net_money', '净消耗', 'callback', function($value){
                    $yuan = round($value / 100, 2);
                    return "<span style='color:#67c23a;font-weight:bold'>¥{$yuan}</span>";
                }],
                ['last_task_time', '最近任务时间'],
            ])
            ->setRowList($dataList)
            ->setExtraJs($js)
            ->fetch();
    }

    public function ajaxData()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        // 概览统计
        $overview = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'COUNT(DISTINCT user_id) as user_count',
                'COUNT(*) as total_tasks',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status IN ("pending","processing","generating","submitting") THEN 1 ELSE 0 END) as waiting',
                'IFNULL(SUM(money), 0) as total_money',
                'IFNULL(SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END), 0) as refund_money',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
            ])
            ->find();

        $totalTasks = intval($overview['total_tasks']);
        $success = intval($overview['success']);
        $failed = intval($overview['failed']);

        // 用户排行 Top 30（按任务数）
        $userRankByTasks = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'user_id',
                'COUNT(*) as task_count',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
            ])
            ->group('user_id')
            ->order('task_count desc')
            ->limit(30)
            ->select();

        $taskRankLabels = [];
        $taskRankValues = [];
        $taskRankSuccess = [];
        $taskRankFailed = [];
        foreach ($userRankByTasks as $row) {
            $taskRankLabels[] = 'U' . $row['user_id'];
            $taskRankValues[] = intval($row['task_count']);
            $taskRankSuccess[] = intval($row['success_count']);
            $taskRankFailed[] = intval($row['failed_count']);
        }

        // 用户排行 Top 30（按消耗金额）
        $userRankByMoney = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'user_id',
                'COUNT(*) as task_count',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
            ])
            ->group('user_id')
            ->order('net_money desc')
            ->limit(30)
            ->select();

        $moneyRankLabels = [];
        $moneyRankValues = [];
        foreach ($userRankByMoney as $row) {
            $moneyRankLabels[] = 'U' . $row['user_id'];
            $moneyRankValues[] = round(floatval($row['net_money']) / 100, 2);
        }

        // 用户维度详细表格（Top 200）
        $userStats = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'user_id',
                'COUNT(*) as task_count',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count',
                'SUM(CASE WHEN status IN ("pending","processing","generating","submitting") THEN 1 ELSE 0 END) as waiting_count',
                'IFNULL(SUM(money), 0) as total_money',
                'IFNULL(SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END), 0) as refund_money',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
                'MAX(created_at) as last_task_time',
            ])
            ->group('user_id')
            ->order('task_count desc')
            ->limit(500)
            ->select();

        $userIds = array_column($userStats->toArray(), 'user_id');
        $userNames = [];
        if (!empty($userIds)) {
            $users = Db::connect('translate')->table('ts_users')
                ->whereIn('id', $userIds)
                ->column('name', 'id');
            $userNames = $users;
        }

        $userStatsData = [];
        foreach ($userStats as $u) {
            $t = intval($u['task_count']);
            $s = intval($u['success_count']);
            $f = intval($u['failed_count']);
            $uid = intval($u['user_id']);
            $userStatsData[] = [
                'user_id'         => $uid,
                'user_name'       => isset($userNames[$uid]) ? $userNames[$uid] : '-',
                'task_count'      => $t,
                'success_count'   => $s,
                'failed_count'    => $f,
                'waiting_count'   => intval($u['waiting_count']),
                'success_rate'    => $t > 0 ? round($s / $t * 100, 1) : 0,
                'total_money'     => floatval($u['total_money']),
                'total_money_yuan'=> round(floatval($u['total_money']) / 100, 2),
                'refund_money'    => floatval($u['refund_money']),
                'refund_money_yuan'=> round(floatval($u['refund_money']) / 100, 2),
                'net_money'       => floatval($u['net_money']),
                'net_money_yuan'  => round(floatval($u['net_money']) / 100, 2),
                'last_task_time'  => $u['last_task_time'],
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'user_count'     => intval($overview['user_count']),
                    'total_tasks'    => $totalTasks,
                    'success'        => $success,
                    'failed'         => $failed,
                    'waiting'        => intval($overview['waiting']),
                    'success_rate'   => $totalTasks > 0 ? round($success / $totalTasks * 100, 1) : 0,
                    'total_money'      => intval($overview['total_money']),
                    'total_money_yuan' => round(intval($overview['total_money']) / 100, 2),
                    'refund_money'     => intval($overview['refund_money']),
                    'refund_money_yuan'=> round(intval($overview['refund_money']) / 100, 2),
                    'net_money'        => intval($overview['net_money']),
                    'net_money_yuan'   => round(intval($overview['net_money']) / 100, 2),
                ],
                'taskRank' => [
                    'labels'  => $taskRankLabels,
                    'values'  => $taskRankValues,
                    'success' => $taskRankSuccess,
                    'failed'  => $taskRankFailed,
                ],
                'moneyRank' => [
                    'labels' => $moneyRankLabels,
                    'values' => $moneyRankValues,
                ],
                'userStats' => $userStatsData,
            ],
            'minutes' => $minutes,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    private function buildTimeRangeHtml($currentMinutes)
    {
        $options = [
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
.ustats-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.ustats-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ustats-card {
    flex: 1; min-width: 130px; padding: 16px 20px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.ustats-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ustats-card .val { font-size: 28px; font-weight: 700; line-height: 1.2; }
.ustats-card .lbl { font-size: 13px; color: #909399; margin-top: 4px; }
.ustats-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.ustats-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ustats-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.ustats-status-bar .status-left { display: flex; align-items: center; gap: 12px; }
.ustats-status-bar .status-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #67c23a;
    display: inline-block; animation: ustats-pulse 1.5s infinite;
}
.ustats-status-bar .status-dot.paused { background: #e6a23c; animation: none; }
@keyframes ustats-pulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.ustats-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px; transition: all .2s;
}
.ustats-btn:hover { border-color: #409eff; color: #409eff; }
.ustats-btn.active { background: #409eff; color: #fff; border-color: #409eff; }
</style>

<div class="ustats-wrap">
    <div class="ustats-cards" id="ustats-cards">
        <div class="ustats-card" style="border-top-color:#9b59b6;">
            <div class="val" style="color:#9b59b6;" id="uc-users">-</div>
            <div class="lbl">活跃用户数</div>
        </div>
        <div class="ustats-card" style="border-top-color:#409eff;">
            <div class="val" style="color:#409eff;" id="uc-tasks">-</div>
            <div class="lbl">总任务数</div>
        </div>
        <div class="ustats-card" style="border-top-color:#67c23a;">
            <div class="val" style="color:#67c23a;" id="uc-success">-</div>
            <div class="lbl">成功</div>
        </div>
        <div class="ustats-card" style="border-top-color:#f56c6c;">
            <div class="val" style="color:#f56c6c;" id="uc-failed">-</div>
            <div class="lbl">失败</div>
        </div>
        <div class="ustats-card" style="border-top-color:#e6a23c;">
            <div class="val" style="color:#e6a23c;" id="uc-waiting">-</div>
            <div class="lbl">进行中</div>
        </div>
        <div class="ustats-card" style="border-top-color:#67c23a;">
            <div class="val" style="color:#67c23a;" id="uc-rate">-</div>
            <div class="lbl">成功率</div>
        </div>
        <div class="ustats-card" style="border-top-color:#fc8452;">
            <div class="val" style="color:#fc8452;" id="uc-total-money">-</div>
            <div class="lbl">总扣费(元)</div>
        </div>
        <div class="ustats-card" style="border-top-color:#f56c6c;">
            <div class="val" style="color:#f56c6c;" id="uc-refund">-</div>
            <div class="lbl">退款(元)</div>
        </div>
        <div class="ustats-card" style="border-top-color:#67c23a;">
            <div class="val" style="color:#67c23a;" id="uc-net">-</div>
            <div class="lbl">净消耗(元)</div>
        </div>
    </div>

    <!-- 用户任务排行 -->
    <div class="ustats-charts-row">
        <div class="chart-box">
            <div id="chart-task-rank" style="width:100%;height:400px;"></div>
        </div>
        <div class="chart-box">
            <div id="chart-money-rank" style="width:100%;height:400px;"></div>
        </div>
    </div>

    <!-- 用户详细表格 -->
    <div style="margin-bottom:16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div style="font-size:14px;font-weight:bold;color:#303133;">用户消耗明细</div>
            <div>
                <input type="text" id="user-search" placeholder="搜索 user_id / 用户名..." style="padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:180px;outline:none;" oninput="filterUserTable()">
            </div>
        </div>
        <table id="user-stats-table" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f7fa;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;cursor:pointer;" onclick="sortUserTable('user_id')">用户ID ↕</th>
                    <th style="padding:10px 8px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">用户名称</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;cursor:pointer;" onclick="sortUserTable('task_count')">任务数 ↕</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;cursor:pointer;" onclick="sortUserTable('success_count')">成功 ↕</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;cursor:pointer;" onclick="sortUserTable('failed_count')">失败 ↕</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#e6a23c;font-weight:600;">进行中</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功率</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#fc8452;font-weight:600;cursor:pointer;" onclick="sortUserTable('total_money')">总扣费 ↕</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;cursor:pointer;" onclick="sortUserTable('refund_money')">退款 ↕</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;cursor:pointer;" onclick="sortUserTable('net_money')">净消耗 ↕</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#909399;font-weight:600;">最近任务</th>
                </tr>
            </thead>
            <tbody id="user-stats-body">
                <tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ustats-status-bar">
        <div class="status-left">
            <span class="status-dot" id="ustats-dot"></span>
            <span id="ustats-status-text">正在加载数据...</span>
            <span style="color:#909399;" id="ustats-update-time"></span>
        </div>
        <div>
            <button class="ustats-btn active" id="ustats-btn-toggle" onclick="uToggleRefresh()">暂停刷新</button>
            <button class="ustats-btn" onclick="uManualRefresh()">立即刷新</button>
        </div>
    </div>
</div>
HTML;
    }

    private function buildDashboardJs($minutes)
    {
        $ajaxUrl = url('ajaxData', ['minutes' => $minutes]);

        $minutesLabels = [
            60   => '最近 1 小时',
            120  => '最近 2 小时',
            360  => '最近 6 小时',
            1440 => '最近 24 小时',
        ];
        $rangeLabel = $minutesLabels[$minutes] ?? "最近 {$minutes} 分钟";

        return <<<JS
<script type="text/javascript">
(function() {
    var chartTaskRank  = echarts.init(document.getElementById('chart-task-rank'));
    var chartMoneyRank = echarts.init(document.getElementById('chart-money-rank'));

    window.addEventListener('resize', function() {
        chartTaskRank.resize();
        chartMoneyRank.resize();
    });

    chartTaskRank.setOption({
        title: { text: '用户任务数排行 Top 30 ({$rangeLabel})', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
        legend: { data: ['成功', '失败'], top: 5, right: 20 },
        grid: { left: '3%', right: '4%', bottom: '3%', top: 50, containLabel: true },
        xAxis: { type: 'category', data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: { type: 'value', minInterval: 1 },
        series: [
            { name: '成功', type: 'bar', stack: 'total', data: [], itemStyle: { color: '#67c23a' } },
            { name: '失败', type: 'bar', stack: 'total', data: [], itemStyle: { color: '#f56c6c' } }
        ]
    });

    chartMoneyRank.setOption({
        title: { text: '用户消耗排行 Top 30 ({$rangeLabel})', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' }, formatter: function(p) { return p[0].name + '<br/>' + p[0].seriesName + ': ¥' + p[0].value; } },
        grid: { left: '3%', right: '4%', bottom: '3%', top: 50, containLabel: true },
        xAxis: { type: 'category', data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: { type: 'value', axisLabel: { formatter: '¥{value}' } },
        series: [
            { name: '净消耗(元)', type: 'bar', data: [], itemStyle: { color: '#fc8452' }, barMaxWidth: 30 }
        ]
    });

    var allUserStats = [];
    var currentSort = { field: 'task_count', asc: false };

    function renderUserTable(data) {
        var tbody = document.getElementById('user-stats-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>';
            return;
        }

        for (var i = 0; i < data.length; i++) {
            var u = data[i];
            var srColor = u.success_rate >= 80 ? '#67c23a' : (u.success_rate >= 50 ? '#e6a23c' : '#f56c6c');
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var refundColor = u.refund_money > 0 ? '#f56c6c' : '#909399';
            var row = '<tr style="background:' + bg + ';" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="padding:9px 12px;border-bottom:1px solid #ebeef5;font-weight:bold;color:#409eff;">' + u.user_id + '</td>'
                + '<td style="padding:9px 8px;border-bottom:1px solid #ebeef5;color:#303133;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + (u.user_name || '-') + '">' + (u.user_name || '-') + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;font-weight:bold;">' + u.task_count + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;font-weight:bold;">' + u.success_count + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (u.failed_count > 0 ? '#f56c6c' : '#909399') + ';font-weight:bold;">' + u.failed_count + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (u.waiting_count > 0 ? '#e6a23c' : '#909399') + ';">' + u.waiting_count + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + srColor + ';font-weight:bold;">' + u.success_rate + '%</td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;"><span style="color:#fc8452;font-weight:bold;">¥' + u.total_money_yuan + '</span></td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;"><span style="color:' + refundColor + ';font-weight:bold;">¥' + u.refund_money_yuan + '</span></td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;"><span style="color:#67c23a;font-weight:bold;">¥' + u.net_money_yuan + '</span></td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#909399;font-size:12px;">' + (u.last_task_time || '-') + '</td>'
                + '</tr>';
            tbody.innerHTML += row;
        }
    }

    function getFilteredSorted() {
        var keyword = (document.getElementById('user-search').value || '').trim();
        var filtered = allUserStats;
        if (keyword) {
            filtered = allUserStats.filter(function(u) {
                return String(u.user_id).indexOf(keyword) !== -1 || (u.user_name && u.user_name.toLowerCase().indexOf(keyword.toLowerCase()) !== -1);
            });
        }
        var field = currentSort.field;
        var asc = currentSort.asc;
        filtered.sort(function(a, b) {
            var va = a[field], vb = b[field];
            return asc ? (va - vb) : (vb - va);
        });
        return filtered;
    }

    window.sortUserTable = function(field) {
        if (currentSort.field === field) {
            currentSort.asc = !currentSort.asc;
        } else {
            currentSort.field = field;
            currentSort.asc = false;
        }
        renderUserTable(getFilteredSorted());
    };

    window.filterUserTable = function() {
        renderUserTable(getFilteredSorted());
    };

    // ZBuilder table update
    function updateZBuilderTable(data) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.append('<tr><td colspan="9" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        for (var i = 0; i < data.length; i++) {
            var u = data[i];
            var refundColor = u.refund_money > 0 ? '#f56c6c' : '#909399';
            var row = '<tr>'
                + '<td><div class="table-cell">' + u.user_id + '</div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#409eff;">' + u.task_count + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + (u.success_count > 0 ? '#67c23a' : '#909399') + ';font-weight:bold">' + u.success_count + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + (u.failed_count > 0 ? '#f56c6c' : '#909399') + ';font-weight:bold">' + u.failed_count + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + (u.waiting_count > 0 ? '#e6a23c' : '#909399') + '">' + u.waiting_count + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:#fc8452;font-weight:bold">¥' + u.total_money_yuan + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + refundColor + ';font-weight:bold">¥' + u.refund_money_yuan + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:#67c23a;font-weight:bold">¥' + u.net_money_yuan + '</span></div></td>'
                + '<td><div class="table-cell">' + (u.last_task_time || '-') + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var autoRefresh = true;
    var countdown = 10;

    function fetchData() {
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;
                var o = d.overview;

                $('#uc-users').text(o.user_count);
                $('#uc-tasks').text(o.total_tasks);
                $('#uc-success').text(o.success);
                $('#uc-failed').text(o.failed);
                $('#uc-waiting').text(o.waiting);
                $('#uc-rate').text(o.success_rate + '%');
                $('#uc-total-money').text('¥' + o.total_money_yuan);
                $('#uc-refund').text('¥' + o.refund_money_yuan);
                $('#uc-net').text('¥' + o.net_money_yuan);

                chartTaskRank.setOption({
                    xAxis: { data: d.taskRank.labels },
                    series: [
                        { data: d.taskRank.success },
                        { data: d.taskRank.failed }
                    ]
                });

                chartMoneyRank.setOption({
                    xAxis: { data: d.moneyRank.labels },
                    series: [{ data: d.moneyRank.values }]
                });

                allUserStats = d.userStats;
                renderUserTable(getFilteredSorted());
                updateZBuilderTable(d.userStats);

                $('#ustats-update-time').text('最后更新: ' + res.time);
                $('#ustats-status-text').text('数据已更新');
            },
            error: function() {
                $('#ustats-status-text').text('数据请求失败，将自动重试');
            }
        });
    }

    function tick() {
        if (!autoRefresh) return;
        countdown--;
        if (countdown <= 0) {
            countdown = 10;
            fetchData();
        }
        $('#ustats-status-text').text('下次刷新: ' + countdown + ' 秒');
    }

    window.uToggleRefresh = function() {
        autoRefresh = !autoRefresh;
        var btn = document.getElementById('ustats-btn-toggle');
        var dot = document.getElementById('ustats-dot');
        if (autoRefresh) {
            btn.textContent = '暂停刷新';
            btn.className = 'ustats-btn active';
            dot.className = 'status-dot';
            countdown = 10;
        } else {
            btn.textContent = '恢复刷新';
            btn.className = 'ustats-btn';
            dot.className = 'status-dot paused';
            $('#ustats-status-text').text('自动刷新已暂停');
        }
    };

    window.uManualRefresh = function() {
        fetchData();
        countdown = 10;
    };

    fetchData();
    setInterval(tick, 1000);
})();
</script>
JS;
    }
}
