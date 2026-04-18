<?php
// Fal 任务实时监控仪表盘
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalTasksModel;

class FalTasksMonitor extends Admin {

    /**
     * 渲染监控页面
     */
    public function index()
    {
        // 获取时间范围参数，默认 60 分钟
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [30, 60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        // 查询最近的任务列表用于底部表格
        $dataList = FalTasksModel::where('created_at', '>=', $startTime)
            ->order('created_at desc')
            ->limit(200)
            ->select();

        // 构建图表容器 HTML
        $contentHtml = $this->buildTimeRangeHtml($minutes) . $this->buildDashboardHtml();

        // 构建前端 JS（ECharts 初始化 + 自动刷新）
        $js = $this->buildDashboardJs($minutes);

        return ZBuilder::make('table')
            ->setPageTitle('Fal 任务实时监控')
            ->setPageTips("自动每 5 秒刷新，展示最近 {$minutes} 分钟的任务数据", 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['app_name', '模型名称', 'callback', function($value){
                    $short = preg_replace('#^fal-ai/#', '', $value ?: '(未知)');
                    return "<span style='font-weight:bold;color:#409eff;'>{$short}</span>";
                }],
                ['money', '金额(分)', 'callback', function($value){
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['status', '状态', 'callback', function($value){
                    $colors = ['completed'=>'#67c23a','failed'=>'#f56c6c','pending'=>'#e6a23c','processing'=>'#409eff'];
                    $color = $colors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
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

    /**
     * AJAX 数据接口 - 返回指定时间范围的统计数据
     */
    public function ajaxData()
    {
        $minutes = input('param.minutes', 60, 'intval');
        if (!in_array($minutes, [30, 60, 120, 360, 1440])) {
            $minutes = 60;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        // 1. 概览指标
        $overview = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status IN ("pending","processing") THEN 1 ELSE 0 END) as waiting',
                'IFNULL(SUM(money), 0) as totalMoney',
                'IFNULL(SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END), 0) as refundMoney',
            ])
            ->find();

        $total = intval($overview['total']);
        $success = intval($overview['success']);
        $failed = intval($overview['failed']);
        $waiting = intval($overview['waiting']);
        $totalMoney = intval($overview['totalMoney']);
        $refundMoney = intval($overview['refundMoney']);
        $netMoney = $totalMoney - $refundMoney;
        $successRate = $total > 0 ? round($success / $total * 100, 1) : 0;
        $failRate = $total > 0 ? round($failed / $total * 100, 1) : 0;

        // 1.5 新增用户统计
        $newUsers = Db::connect('translate')->table('ts_users')
            ->where('time', '>=', $startTime)
            ->count();

        $newGoogleUsers = Db::connect('translate')->table('ts_users')
            ->where('time', '>=', $startTime)
            ->where(function($query) {
                $query->where('open_id', '')->whereOr('open_id', null);
            })
            ->where(function($query) {
                $query->where('unionid', '')->whereOr('unionid', null);
            })
            ->count();

        $newWechatUsers = $newUsers - $newGoogleUsers;
        $googleRate = $newUsers > 0 ? round($newGoogleUsers / $newUsers * 100, 1) : 0;

        // 1.6 收入统计（支付订单）
        $incomeStats = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startTime)
            ->field([
                'IFNULL(SUM(money), 0) as totalIncome',
                'COUNT(*) as orderCount',
            ])
            ->find();

        $totalIncome = intval($incomeStats['totalIncome']);
        $orderCount = intval($incomeStats['orderCount']);

        // 2. 模型分布（Top 10）
        $modelDist = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'app_name',
                'COUNT(*) as count',
            ])
            ->group('app_name')
            ->order('count desc')
            ->limit(10)
            ->select();

        $modelData = [];
        foreach ($modelDist as $item) {
            $name = $item['app_name'] ?: '未知';
            $name = preg_replace('#^fal-ai/#', '', $name);
            $modelData[] = [
                'name' => $name,
                'value' => intval($item['count']),
            ];
        }

        // 3. 状态分布
        $statusDist = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'status',
                'COUNT(*) as count',
            ])
            ->group('status')
            ->select();

        $statusData = [];
        foreach ($statusDist as $item) {
            $statusData[] = [
                'name' => $item['status'] ?: '未知',
                'value' => intval($item['count']),
            ];
        }

        // 4. 任务趋势 - 根据时间范围选择合适的分组粒度
        if ($minutes <= 120) {
            // 2小时以内按分钟分组
            $groupExpr = 'DATE_FORMAT(created_at, "%H:%i")';
        } elseif ($minutes <= 1440) {
            // 24小时以内按5分钟分组
            $groupExpr = 'DATE_FORMAT(created_at, "%H:%i") DIV 5';
            // 用更可读的表示：按小时:分钟，取5分钟整
            $groupExpr = 'CONCAT(DATE_FORMAT(created_at, "%H"), ":", LPAD(FLOOR(MINUTE(created_at)/5)*5, 2, "0"))';
        } else {
            $groupExpr = 'DATE_FORMAT(created_at, "%m-%d %H:00")';
        }

        $trendRaw = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                "{$groupExpr} as time_minute",
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status IN ("pending","processing") THEN 1 ELSE 0 END) as waiting',
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

        // 5. 模型维度统计
        $modelStats = FalTasksModel::where('created_at', '>=', $startTime)
            ->field([
                'app_name',
                'COUNT(*) as total',
                'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed',
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_cnt',
                'SUM(CASE WHEN status IN ("processing","generating","submitting") THEN 1 ELSE 0 END) as processing_cnt',
                'IFNULL(SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END), 0) as net_money',
                'ROUND(AVG(CASE WHEN status = "completed" AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, completed_at) END), 1) as avg_success_sec',
                'ROUND(AVG(CASE WHEN status IN ("processing","generating","submitting") THEN TIMESTAMPDIFF(SECOND, created_at, NOW()) END), 1) as avg_waiting_sec',
            ])
            ->group('app_name')
            ->order('total desc')
            ->select();

        $modelStatsData = [];
        foreach ($modelStats as $ms) {
            $t = intval($ms['total']);
            $s = intval($ms['success']);
            $f = intval($ms['failed']);
            $avgSuccessSec = floatval($ms['avg_success_sec']);
            $avgWaitingSec = floatval($ms['avg_waiting_sec']);
            $modelStatsData[] = [
                'app_name'       => preg_replace('#^fal-ai/#', '', $ms['app_name'] ?: '(未知)'),
                'total'          => $t,
                'success'        => $s,
                'failed'         => $f,
                'pending'        => intval($ms['pending_cnt']),
                'processing'     => intval($ms['processing_cnt']),
                'successRate'    => $t > 0 ? round($s / $t * 100, 1) : 0,
                'failRate'       => $t > 0 ? round($f / $t * 100, 1) : 0,
                'netMoney'       => intval($ms['net_money']),
                'netMoneyYuan'   => round(intval($ms['net_money']) / 100, 2),
                'avgSuccessSec'  => $avgSuccessSec,
                'avgWaitingSec'  => $avgWaitingSec,
            ];
        }

        // 6. 最新任务列表（用于刷新表格）
        $recentTasks = FalTasksModel::where('created_at', '>=', $startTime)
            ->field('id, user_id, app_name, money, status, is_refund, created_at, completed_at')
            ->order('created_at desc')
            ->limit(200)
            ->select();

        $taskRows = [];
        foreach ($recentTasks as $task) {
            $taskRows[] = [
                'id'           => $task['id'],
                'user_id'      => $task['user_id'],
                'app_name'     => $task['app_name'],
                'money'        => $task['money'],
                'status'       => $task['status'],
                'is_refund'    => $task['is_refund'],
                'created_at'   => $task['created_at'],
                'completed_at' => $task['completed_at'],
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'total'       => $total,
                    'success'     => $success,
                    'failed'      => $failed,
                    'waiting'     => $waiting,
                    'successRate' => $successRate,
                    'failRate'    => $failRate,
                    'totalMoney'  => $totalMoney,
                    'refundMoney' => $refundMoney,
                    'netMoney'    => $netMoney,
                    'netMoneyYuan' => round($netMoney / 100, 2),
                    'newUsers'       => $newUsers,
                    'newGoogleUsers' => $newGoogleUsers,
                    'newWechatUsers' => $newWechatUsers,
                    'googleRate'     => $googleRate,
                    'totalIncome'     => $totalIncome,
                    'totalIncomeYuan' => round($totalIncome / 100, 2),
                    'orderCount'      => $orderCount,
                ],
                'modelDist'  => $modelData,
                'statusDist' => $statusData,
                'trend'      => [
                    'time'    => $trendTime,
                    'total'   => $trendTotal,
                    'success' => $trendSuccess,
                    'failed'  => $trendFailed,
                    'waiting' => $trendWaiting,
                    'money'   => $trendMoney,
                ],
                'modelStats' => $modelStatsData,
                'tasks' => $taskRows,
            ],
            'minutes' => $minutes,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 构建时间范围选择器 HTML
     */
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

    /**
     * 构建仪表盘 HTML 容器
     */
    private function buildDashboardHtml()
    {
        return <<<'HTML'
<style>
.monitor-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.monitor-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.monitor-card {
    flex: 1; min-width: 140px; padding: 16px 20px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.monitor-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.monitor-card .card-value { font-size: 28px; font-weight: 700; line-height: 1.2; }
.monitor-card .card-label { font-size: 13px; color: #909399; margin-top: 4px; }
.monitor-card.success { border-top-color: #67c23a; }
.monitor-card.success .card-value { color: #67c23a; }
.monitor-card.danger { border-top-color: #f56c6c; }
.monitor-card.danger .card-value { color: #f56c6c; }
.monitor-card.warning { border-top-color: #e6a23c; }
.monitor-card.warning .card-value { color: #e6a23c; }
.monitor-card.info { border-top-color: #409eff; }
.monitor-card.info .card-value { color: #409eff; }
.monitor-card.rate-success { border-top-color: #67c23a; }
.monitor-card.rate-success .card-value { color: #67c23a; }
.monitor-card.rate-danger { border-top-color: #f56c6c; }
.monitor-card.rate-danger .card-value { color: #f56c6c; }
.monitor-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.monitor-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.monitor-trend-row { margin-bottom: 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.monitor-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.monitor-status-bar .status-left { display: flex; align-items: center; gap: 12px; }
.monitor-status-bar .status-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #67c23a;
    display: inline-block; animation: pulse-dot 1.5s infinite;
}
.monitor-status-bar .status-dot.paused { background: #e6a23c; animation: none; }
@keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.monitor-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px;
    transition: all .2s;
}
.monitor-btn:hover { border-color: #409eff; color: #409eff; }
.monitor-btn.active { background: #409eff; color: #fff; border-color: #409eff; }
</style>

<div class="monitor-wrap">
    <!-- 概览统计卡片 -->
    <div class="monitor-cards" id="monitor-cards">
        <div class="monitor-card info">
            <div class="card-value" id="card-total">-</div>
            <div class="card-label">总任务数</div>
        </div>
        <div class="monitor-card" style="border-top-color:#9b59b6;">
            <div class="card-value" style="color:#9b59b6;" id="card-new-users">-</div>
            <div class="card-label">新增用户</div>
        </div>
        <div class="monitor-card" style="border-top-color:#4285f4;">
            <div class="card-value" style="color:#4285f4;" id="card-google-users">-</div>
            <div class="card-label">谷歌登录</div>
        </div>
        <div class="monitor-card success">
            <div class="card-value" id="card-success">-</div>
            <div class="card-label">成功</div>
        </div>
        <div class="monitor-card danger">
            <div class="card-value" id="card-failed">-</div>
            <div class="card-label">失败</div>
        </div>
        <div class="monitor-card warning">
            <div class="card-value" id="card-waiting">-</div>
            <div class="card-label">等待中</div>
        </div>
        <div class="monitor-card rate-success">
            <div class="card-value" id="card-success-rate">-</div>
            <div class="card-label">成功率</div>
        </div>
        <div class="monitor-card rate-danger">
            <div class="card-value" id="card-fail-rate">-</div>
            <div class="card-label">失败率</div>
        </div>
    </div>
    <!-- 积分消耗卡片 -->
    <div class="monitor-cards" id="monitor-cards-money">
        <div class="monitor-card" style="border-top-color:#fc8452;">
            <div class="card-value" style="color:#fc8452;" id="card-total-money">-</div>
            <div class="card-label">总扣费(积分)</div>
        </div>
        <div class="monitor-card" style="border-top-color:#f56c6c;">
            <div class="card-value" style="color:#f56c6c;" id="card-refund-money">-</div>
            <div class="card-label">已退款(积分)</div>
        </div>
        <div class="monitor-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="card-net-money">-</div>
            <div class="card-label">净消耗(积分)</div>
        </div>
        <div class="monitor-card" style="border-top-color:#67c23a;">
            <div class="card-value" style="color:#67c23a;" id="card-net-money-yuan">-</div>
            <div class="card-label">净消耗(元)</div>
        </div>
        <div class="monitor-card" style="border-top-color:#e74c3c;">
            <div class="card-value" style="color:#e74c3c;" id="card-income">-</div>
            <div class="card-label">收入(元)</div>
        </div>
        <div class="monitor-card" style="border-top-color:#3498db;">
            <div class="card-value" style="color:#3498db;" id="card-order-count">-</div>
            <div class="card-label">支付订单数</div>
        </div>
    </div>

    <!-- 饼图行：模型分布 + 状态分布 -->
    <div class="monitor-charts-row">
        <div class="chart-box">
            <div id="chart-model" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="chart-status" style="width:100%;height:340px;"></div>
        </div>
    </div>

    <!-- 趋势折线图 -->
    <div class="monitor-trend-row">
        <div id="chart-trend" style="width:100%;height:360px;"></div>
    </div>

    <!-- 模型统计表格 -->
    <div style="margin-bottom:16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px;">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">模型数据统计</div>
        <table id="model-stats-table" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f7fa;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">模型名称</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">总任务</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功耗时</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">失败</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#e6a23c;font-weight:600;">等待中</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#409eff;font-weight:600;">处理中</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#409eff;font-weight:600;">处理耗时</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">成功率</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">失败率</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#fc8452;font-weight:600;">净消耗(元)</th>
                </tr>
            </thead>
            <tbody id="model-stats-body">
                <tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 自动刷新状态栏 -->
    <div class="monitor-status-bar">
        <div class="status-left">
            <span class="status-dot" id="status-dot"></span>
            <span id="status-text">正在加载数据...</span>
            <span style="color:#909399;" id="last-update-time"></span>
        </div>
        <div>
            <button class="monitor-btn active" id="btn-toggle" onclick="toggleAutoRefresh()">暂停刷新</button>
            <button class="monitor-btn" id="btn-refresh" onclick="manualRefresh()">立即刷新</button>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * 构建仪表盘前端 JS
     */
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
        $trendTitle = '任务趋势 (' . ($minutesLabels[$minutes] ?? "最近 {$minutes} 分钟") . ')';

        return <<<JS
<script type="text/javascript">
(function() {
    // ===== 图表实例 =====
    var chartModel  = echarts.init(document.getElementById('chart-model'));
    var chartStatus = echarts.init(document.getElementById('chart-status'));
    var chartTrend  = echarts.init(document.getElementById('chart-trend'));

    // 窗口自适应
    window.addEventListener('resize', function() {
        chartModel.resize();
        chartStatus.resize();
        chartTrend.resize();
    });

    // ===== 状态颜色映射 =====
    var statusColorMap = {
        'completed':  '#67c23a',
        'failed':     '#f56c6c',
        'pending':    '#e6a23c',
        'processing': '#409eff'
    };

    // ===== 初始化图表选项 =====
    chartModel.setOption({
        title: { text: '模型分布 (Top 10)', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 }, type: 'scroll' },
        series: [{
            name: '任务数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false, position: 'center' },
            emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            labelLine: { show: false },
            data: []
        }]
    });

    chartStatus.setOption({
        title: { text: '状态分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{
            name: '任务数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false, position: 'center' },
            emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            labelLine: { show: false },
            data: []
        }]
    });

    chartTrend.setOption({
        title: { text: '{$trendTitle}', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis' },
        legend: { data: ['总数', '成功', '失败', '等待中', '消耗(元)'], top: 5, right: 20 },
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
            { name: '等待中', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#e6a23c' }, lineStyle: { color: '#e6a23c', width: 2 } },
            { name: '消耗(元)', type: 'bar', yAxisIndex: 1, data: [], itemStyle: { color: 'rgba(252,132,82,0.6)' }, barMaxWidth: 20 }
        ]
    });

    // ===== 模型统计表格渲染 =====
    function renderModelStats(models) {
        var tbody = $('#model-stats-body');
        tbody.empty();

        if (!models || models.length === 0) {
            tbody.append('<tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        function fmtSec(s) {
            if (!s || s <= 0) return '-';
            if (s < 60) return s.toFixed(1) + 's';
            if (s < 3600) return (s / 60).toFixed(1) + 'min';
            return (s / 3600).toFixed(1) + 'h';
        }

        for (var i = 0; i < models.length; i++) {
            var m = models[i];
            var successRateColor = m.successRate >= 80 ? '#67c23a' : (m.successRate >= 50 ? '#e6a23c' : '#f56c6c');
            var failRateColor = m.failRate >= 30 ? '#f56c6c' : (m.failRate >= 10 ? '#e6a23c' : '#67c23a');
            var bgColor = i % 2 === 0 ? '#fff' : '#fafafa';
            var avgSuccessStr = fmtSec(m.avgSuccessSec);
            var avgWaitStr = fmtSec(m.avgWaitingSec);
            var waitColor = m.avgWaitingSec > 300 ? '#f56c6c' : (m.avgWaitingSec > 60 ? '#e6a23c' : '#409eff');

            var row = '<tr style="background:' + bgColor + ';transition:background .2s;" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bgColor + '\'">'
                + '<td style="padding:9px 12px;border-bottom:1px solid #ebeef5;font-weight:bold;color:#409eff;">' + m.app_name + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;font-weight:bold;">' + m.total + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;font-weight:bold;">' + m.success + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;">' + avgSuccessStr + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.failed > 0 ? '#f56c6c' : '#909399') + ';font-weight:bold;">' + m.failed + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.pending > 0 ? '#e6a23c' : '#909399') + ';">' + m.pending + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.processing > 0 ? '#409eff' : '#909399') + ';">' + m.processing + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + waitColor + ';">' + avgWaitStr + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + successRateColor + ';font-weight:bold;">' + m.successRate + '%</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + failRateColor + ';font-weight:bold;">' + m.failRate + '%</td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;color:#fc8452;font-weight:bold;">¥' + m.netMoneyYuan + '</td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    // ===== 任务列表表格渲染 =====
    var statusColors = { 'completed': '#67c23a', 'failed': '#f56c6c', 'pending': '#e6a23c', 'processing': '#409eff', 'generating': '#909399', 'submitting': '#909399' };

    function renderTable(tasks) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();

        // 检测表头是否有复选框列
        var hasCheckbox = $('#builder-table-head thead th').first().find('input[type=checkbox]').length > 0;
        var colCount = hasCheckbox ? 9 : 8;

        if (!tasks || tasks.length === 0) {
            tbody.append('<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        for (var i = 0; i < tasks.length; i++) {
            var t = tasks[i];
            var appShort = (t.app_name || '(未知)').replace(/^fal-ai\//, '');
            var sColor = statusColors[t.status] || '#909399';
            var moneyColor = t.money > 0 ? '#67c23a' : '#909399';
            var refundHtml = t.is_refund == 1 ? '<span style="color:#f56c6c">已退款</span>' : '-';

            var checkboxTd = hasCheckbox ? '<td><div class="table-cell"><input type="checkbox" name="ids[]" value="' + t.id + '"></div></td>' : '';

            var row = '<tr>'
                + checkboxTd
                + '<td><div class="table-cell">' + t.id + '</div></td>'
                + '<td><div class="table-cell">' + (t.user_id || '') + '</div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#409eff;">' + appShort + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + moneyColor + '">' + (t.money || 0) + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + sColor + ';font-weight:bold">' + (t.status || '') + '</span></div></td>'
                + '<td><div class="table-cell">' + refundHtml + '</div></td>'
                + '<td><div class="table-cell">' + (t.created_at || '') + '</div></td>'
                + '<td><div class="table-cell">' + (t.completed_at || '-') + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    // ===== 数据刷新 =====
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

                // 更新概览卡片
                $('#card-total').text(d.overview.total);
                $('#card-new-users').text(d.overview.newUsers);
                $('#card-google-users').text(d.overview.newGoogleUsers);
                $('#card-success').text(d.overview.success);
                $('#card-failed').text(d.overview.failed);
                $('#card-waiting').text(d.overview.waiting);
                $('#card-success-rate').text(d.overview.successRate + '%');
                $('#card-fail-rate').text(d.overview.failRate + '%');
                // 更新积分消耗卡片
                $('#card-total-money').text(d.overview.totalMoney);
                $('#card-refund-money').text(d.overview.refundMoney);
                $('#card-net-money').text(d.overview.netMoney);
                $('#card-net-money-yuan').text('¥' + d.overview.netMoneyYuan);
                $('#card-income').text('¥' + d.overview.totalIncomeYuan);
                $('#card-order-count').text(d.overview.orderCount);

                // 更新模型分布饼图
                chartModel.setOption({
                    series: [{ data: d.modelDist }]
                });

                // 更新状态分布饼图（带颜色映射）
                var statusPieData = [];
                for (var i = 0; i < d.statusDist.length; i++) {
                    var item = d.statusDist[i];
                    statusPieData.push({
                        name: item.name,
                        value: item.value,
                        itemStyle: { color: statusColorMap[item.name] || '#909399' }
                    });
                }
                chartStatus.setOption({
                    series: [{ data: statusPieData }]
                });

                // 更新趋势折线图
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

                // 更新模型统计表格
                renderModelStats(d.modelStats);

                // 更新任务列表表格
                renderTable(d.tasks);

                // 更新状态栏
                $('#last-update-time').text('最后更新: ' + res.time);
                $('#status-text').text('数据已更新');
            },
            error: function() {
                $('#status-text').text('数据请求失败，将自动重试');
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
        $('#status-text').text('下次刷新: ' + countdown + ' 秒');
    }

    // 暴露到全局
    window.toggleAutoRefresh = function() {
        autoRefresh = !autoRefresh;
        var btn = document.getElementById('btn-toggle');
        var dot = document.getElementById('status-dot');
        if (autoRefresh) {
            btn.textContent = '暂停刷新';
            btn.className = 'monitor-btn active';
            dot.className = 'status-dot';
            countdown = 5;
            $('#status-text').text('已恢复自动刷新');
        } else {
            btn.textContent = '恢复刷新';
            btn.className = 'monitor-btn';
            dot.className = 'status-dot paused';
            $('#status-text').text('自动刷新已暂停');
        }
    };

    window.manualRefresh = function() {
        fetchData();
        countdown = 5;
    };

    // 首次加载
    fetchData();
    timer = setInterval(tick, 1000);
})();
</script>
JS;
    }
}
