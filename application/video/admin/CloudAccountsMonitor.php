<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

class CloudAccountsMonitor extends Admin {

    private $apiBase = 'http://127.0.0.1:8002/api/cloud-accounts';

    private function apiGet($path)
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $json = @file_get_contents($this->apiBase . $path, false, $ctx);
        return $json ? json_decode($json, true) : null;
    }

    public function index()
    {
        $listData  = $this->apiGet('/list');
        $accounts  = $listData['accounts'] ?? [];

        $dataList = [];
        foreach ($accounts as $acc) {
            $dataList[] = [
                'id'              => $acc['id'],
                'machine_name'    => $acc['machine_name'],
                'name'            => $acc['name'] ?? '-',
                'token'           => $acc['token'] ?? '',
                'account_id'      => $acc['account_id'],
                'status'          => $acc['status'],
                'balance'         => $acc['balance'],
                'max_concurrency' => $acc['max_concurrency'],
                'active_tasks'    => $acc['active_tasks'],
                'completed_count' => $acc['completed_count'],
                'failed_count'    => $acc['failed_count'],
                'earned_money'    => $acc['earned_money'],
                'reported_at'     => $acc['reported_at'],
            ];
        }

        $contentHtml = $this->buildDashboardHtml();
        $js = $this->buildDashboardJs();

        return ZBuilder::make('table')
            ->setPageTitle('云账号池实时监控')
            ->setPageTips('自动每 10 秒刷新，展示所有作业机器上报的账号池状态', 'info')
            ->setTableName('cloud_accounts_monitor')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['id', 'ID'],
                ['machine_name', '机器名'],
                ['name', '账号名'],
                ['token', 'Token', 'callback', function($value){
                    if (!$value) return '-';
                    $short = substr($value, 0, 8) . '...';
                    return "<span style='font-family:monospace;font-size:12px;color:#606266;' title='{$value}'>{$short}</span> <a href='javascript:void(0)' onclick=\"navigator.clipboard.writeText('{$value}');this.textContent='已复制';this.style.color='#67c23a';var a=this;setTimeout(function(){a.textContent='复制';a.style.color=''},1200)\" style='font-size:12px;color:#409eff;text-decoration:none;cursor:pointer;'>复制</a>";
                }],
                ['status', '状态', 'callback', function($value){
                    $colors = ['active'=>'#67c23a','disabled'=>'#f56c6c','deleted'=>'#909399'];
                    $labels = ['active'=>'运行中','disabled'=>'已停用','deleted'=>'已删除'];
                    $color = $colors[$value] ?? '#909399';
                    $label = $labels[$value] ?? $value;
                    return "<span style='color:{$color};font-weight:bold'>{$label}</span>";
                }],
                ['balance', '余额', 'callback', function($value){
                    $color = $value > 100 ? '#67c23a' : ($value > 0 ? '#e6a23c' : '#f56c6c');
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['max_concurrency', '最大并发'],
                ['active_tasks', '活跃任务', 'callback', function($value){
                    $color = $value > 0 ? '#409eff' : '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$value}</span>";
                }],
                ['completed_count', '累计完成'],
                ['failed_count', '累计失败'],
                ['earned_money', '累计收入', 'callback', function($value){
                    return "<span style='color:#fc8452;font-weight:bold'>¥" . number_format($value / 100, 2) . "</span>";
                }],
                ['reported_at', '最后上报'],
            ])
            ->setRowList($dataList)
            ->setExtraJs($js)
            ->fetch();
    }

    public function ajaxData()
    {
        $stats = $this->apiGet('/stats');
        $list  = $this->apiGet('/list');

        if (!$stats || !$list) {
            return json(['code' => 1, 'msg' => 'API 请求失败']);
        }

        $accounts = $list['accounts'] ?? [];

        $statusDist = [];
        $machineDist = [];
        $machineDetails = [];
        $totalBalance = 0;
        $totalActive = 0;
        $totalCompleted = 0;
        $totalFailed = 0;
        $totalEarned = 0;

        $statusCount = [];
        $machineCount = [];

        foreach ($accounts as $acc) {
            $s = $acc['status'] ?? 'unknown';
            $statusCount[$s] = ($statusCount[$s] ?? 0) + 1;

            $m = $acc['machine_name'] ?? 'unknown';
            if (!isset($machineCount[$m])) {
                $machineCount[$m] = ['total' => 0, 'active' => 0, 'balance' => 0, 'active_tasks' => 0, 'completed' => 0, 'failed' => 0, 'earned' => 0, 'last_reported' => '', 'ip_addresses' => []];
            }
            $ip = $acc['ip_address'] ?? '';
            if ($ip && !in_array($ip, $machineCount[$m]['ip_addresses'])) {
                $machineCount[$m]['ip_addresses'][] = $ip;
            }
            $machineCount[$m]['total']++;
            if ($s === 'active') $machineCount[$m]['active']++;
            $machineCount[$m]['balance'] += $acc['balance'] ?? 0;
            $machineCount[$m]['active_tasks'] += $acc['active_tasks'] ?? 0;
            $machineCount[$m]['completed'] += $acc['completed_count'] ?? 0;
            $machineCount[$m]['failed'] += $acc['failed_count'] ?? 0;
            $machineCount[$m]['earned'] += $acc['earned_money'] ?? 0;
            if (($acc['reported_at'] ?? '') > $machineCount[$m]['last_reported']) {
                $machineCount[$m]['last_reported'] = $acc['reported_at'];
            }

            if ($s === 'active') {
                $totalBalance += $acc['balance'] ?? 0;
                $totalActive++;
            }
            $totalCompleted += $acc['completed_count'] ?? 0;
            $totalFailed += $acc['failed_count'] ?? 0;
            $totalEarned += $acc['earned_money'] ?? 0;
        }

        foreach ($statusCount as $k => $v) {
            $statusDist[] = ['name' => $k, 'value' => $v];
        }

        foreach ($machineCount as $k => $v) {
            $machineDist[] = ['name' => $k, 'value' => $v['total']];
            $machineDetails[] = array_merge(['machine_name' => $k], $v);
        }

        $totalAccounts = count($accounts);
        $totalDisabled = $totalAccounts - $totalActive;
        $successRate = ($totalCompleted + $totalFailed) > 0
            ? round($totalCompleted / ($totalCompleted + $totalFailed) * 100, 1) : 0;

        $accountRows = [];
        foreach ($accounts as $acc) {
            $accountRows[] = [
                'id'              => $acc['id'],
                'machine_name'    => $acc['machine_name'],
                'name'            => $acc['name'] ?? '-',
                'token'           => $acc['token'] ?? '',
                'account_id'      => $acc['account_id'],
                'status'          => $acc['status'],
                'balance'         => $acc['balance'],
                'max_concurrency' => $acc['max_concurrency'],
                'active_tasks'    => $acc['active_tasks'],
                'completed_count' => $acc['completed_count'],
                'failed_count'    => $acc['failed_count'],
                'earned_money'    => $acc['earned_money'],
                'reported_at'     => $acc['reported_at'],
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'totalAccounts' => $totalAccounts,
                    'activeAccounts' => $totalActive,
                    'disabledAccounts' => $totalDisabled,
                    'totalBalance' => $totalBalance,
                    'totalActiveTasks' => $stats['total_active_tasks'] ?? 0,
                    'totalCompleted' => $totalCompleted,
                    'totalFailed' => $totalFailed,
                    'totalEarned' => $totalEarned,
                    'successRate' => $successRate,
                ],
                'statusDist' => $statusDist,
                'machineDist' => $machineDist,
                'machineDetails' => $machineDetails,
                'accounts' => $accountRows,
            ],
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteMachine()
    {
        $machineName = input('machine_name', '');
        if (!$machineName) {
            return json(['code' => 1, 'msg' => '缺少机器名']);
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'DELETE',
                'timeout' => 5,
            ],
        ]);
        $url  = $this->apiBase . '/delete-by-machine/' . urlencode($machineName);
        $json = @file_get_contents($url, false, $ctx);
        $res  = $json ? json_decode($json, true) : null;

        if ($res && !empty($res['ok'])) {
            return json(['code' => 0, 'msg' => '已删除 ' . ($res['deleted'] ?? 0) . ' 条记录']);
        }
        return json(['code' => 1, 'msg' => '删除失败']);
    }

    private function buildDashboardHtml()
    {
        return <<<'HTML'
<style>
.ca-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.ca-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ca-card {
    flex: 1; min-width: 130px; padding: 16px 20px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.ca-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ca-card .val { font-size: 28px; font-weight: 700; line-height: 1.2; }
.ca-card .lbl { font-size: 13px; color: #909399; margin-top: 4px; }
.ca-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.ca-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ca-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.ca-status-bar .sl { display: flex; align-items: center; gap: 12px; }
.ca-status-bar .dot {
    width: 8px; height: 8px; border-radius: 50%; background: #67c23a;
    display: inline-block; animation: ca-pulse 1.5s infinite;
}
.ca-status-bar .dot.paused { background: #e6a23c; animation: none; }
@keyframes ca-pulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.ca-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px; transition: all .2s;
}
.ca-btn:hover { border-color: #409eff; color: #409eff; }
.ca-btn.active { background: #409eff; color: #fff; border-color: #409eff; }
</style>

<div class="ca-wrap">
    <div class="ca-cards">
        <div class="ca-card" style="border-top-color:#409eff;">
            <div class="val" style="color:#409eff;" id="ca-total">-</div>
            <div class="lbl">总账号数</div>
        </div>
        <div class="ca-card" style="border-top-color:#67c23a;">
            <div class="val" style="color:#67c23a;" id="ca-active">-</div>
            <div class="lbl">Active 在线</div>
        </div>
        <div class="ca-card" style="border-top-color:#f56c6c;">
            <div class="val" style="color:#f56c6c;" id="ca-disabled">-</div>
            <div class="lbl">Disabled 停用</div>
        </div>
        <div class="ca-card" style="border-top-color:#e6a23c;">
            <div class="val" style="color:#e6a23c;" id="ca-balance">-</div>
            <div class="lbl">总余额</div>
        </div>
        <div class="ca-card" style="border-top-color:#9b59b6;">
            <div class="val" style="color:#9b59b6;" id="ca-tasks">-</div>
            <div class="lbl">当前活跃任务</div>
        </div>
        <div class="ca-card" style="border-top-color:#67c23a;">
            <div class="val" style="color:#67c23a;" id="ca-completed">-</div>
            <div class="lbl">累计完成</div>
        </div>
        <div class="ca-card" style="border-top-color:#f56c6c;">
            <div class="val" style="color:#f56c6c;" id="ca-failed">-</div>
            <div class="lbl">累计失败</div>
        </div>
        <div class="ca-card" style="border-top-color:#fc8452;">
            <div class="val" style="color:#fc8452;" id="ca-earned">-</div>
            <div class="lbl">累计收入</div>
        </div>
        <div class="ca-card" style="border-top-color:#3498db;">
            <div class="val" style="color:#3498db;" id="ca-rate">-</div>
            <div class="lbl">成功率</div>
        </div>
    </div>

    <div class="ca-charts-row">
        <div class="chart-box">
            <div id="ca-chart-status" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="ca-chart-machine" style="width:100%;height:340px;"></div>
        </div>
    </div>

    <!-- 机器维度统计表格 -->
    <div style="margin-bottom:16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px;">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">机器维度统计</div>
        <table id="ca-machine-table" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f5f7fa;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">机器名</th>
                    <th style="padding:10px 8px;text-align:left;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">IP 地址</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#606266;font-weight:600;">账号数</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">Active</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#e6a23c;font-weight:600;">总余额</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#9b59b6;font-weight:600;">活跃任务</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#67c23a;font-weight:600;">累计完成</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">累计失败</th>
                    <th style="padding:10px 8px;text-align:right;border-bottom:2px solid #ebeef5;color:#fc8452;font-weight:600;">累计收入</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#909399;font-weight:600;">最后上报</th>
                    <th style="padding:10px 8px;text-align:center;border-bottom:2px solid #ebeef5;color:#f56c6c;font-weight:600;">操作</th>
                </tr>
            </thead>
            <tbody id="ca-machine-body">
                <tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 搜索栏 -->
    <div style="margin-bottom:16px;padding:12px 16px;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-weight:bold;font-size:13px;color:#303133;">筛选：</span>
        <input type="text" id="ca-search-machine" placeholder="机器名搜索" style="padding:6px 12px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:180px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#409eff'" onblur="this.style.borderColor='#dcdfe6'">
        <input type="text" id="ca-search-name" placeholder="账号名搜索" style="padding:6px 12px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:180px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#409eff'" onblur="this.style.borderColor='#dcdfe6'">
        <input type="text" id="ca-search-token" placeholder="Token搜索" style="padding:6px 12px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:200px;outline:none;transition:border .2s;font-family:monospace;" onfocus="this.style.borderColor='#409eff'" onblur="this.style.borderColor='#dcdfe6'">
        <select id="ca-search-status" style="padding:6px 12px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;outline:none;cursor:pointer;">
            <option value="">全部状态</option>
            <option value="active">运行中</option>
            <option value="disabled">已停用</option>
            <option value="deleted">已删除</option>
        </select>
        <button class="ca-btn" onclick="caFilter()">搜索</button>
        <button class="ca-btn" onclick="caClearFilter()">重置</button>
        <span id="ca-filter-count" style="font-size:12px;color:#909399;"></span>
    </div>

    <div class="ca-status-bar">
        <div class="sl">
            <span class="dot" id="ca-dot"></span>
            <span id="ca-status-text">正在加载数据...</span>
            <span style="color:#909399;" id="ca-update-time"></span>
        </div>
        <div>
            <button class="ca-btn active" id="ca-btn-toggle" onclick="caToggle()">暂停刷新</button>
            <button class="ca-btn" onclick="caRefresh()">立即刷新</button>
        </div>
    </div>
</div>
HTML;
    }

    private function buildDashboardJs()
    {
        $ajaxUrl = url('ajaxData');
        $deleteUrl = url('deleteMachine');

        return <<<JS
<script type="text/javascript">
(function() {
    var chartStatus  = echarts.init(document.getElementById('ca-chart-status'));
    var chartMachine = echarts.init(document.getElementById('ca-chart-machine'));

    window.addEventListener('resize', function() {
        chartStatus.resize();
        chartMachine.resize();
    });

    var statusColorMap = {
        'active':   '#67c23a',
        'disabled': '#f56c6c',
        'deleted':  '#909399'
    };

    chartStatus.setOption({
        title: { text: '账号状态分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{
            name: '账号数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: true, formatter: '{b}: {c}', fontSize: 12 },
            emphasis: { label: { show: true, fontSize: 14, fontWeight: 'bold' } },
            data: []
        }]
    });

    chartMachine.setOption({
        title: { text: '机器账号分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 }, type: 'scroll' },
        series: [{
            name: '账号数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: true, formatter: '{b}: {c}', fontSize: 12 },
            emphasis: { label: { show: true, fontSize: 14, fontWeight: 'bold' } },
            data: []
        }]
    });

    function fmtNum(n) {
        return n >= 10000 ? (n / 10000).toFixed(1) + 'w' : n.toLocaleString();
    }

    function renderMachineTable(machines) {
        var tbody = $('#ca-machine-body');
        tbody.empty();
        if (!machines || machines.length === 0) {
            tbody.append('<tr><td colspan="11" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }
        for (var i = 0; i < machines.length; i++) {
            var m = machines[i];
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var escapedName = m.machine_name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            var ipList = (m.ip_addresses || []).join(', ') || '-';
            var row = '<tr style="background:' + bg + ';transition:background .2s;" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="padding:9px 12px;border-bottom:1px solid #ebeef5;font-weight:bold;color:#409eff;">' + m.machine_name + '</td>'
                + '<td style="padding:9px 8px;border-bottom:1px solid #ebeef5;font-family:monospace;font-size:12px;color:#606266;">' + ipList + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;font-weight:bold;">' + m.total + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;font-weight:bold;">' + m.active + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#e6a23c;font-weight:bold;">' + fmtNum(m.balance) + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.active_tasks > 0 ? '#9b59b6' : '#909399') + ';font-weight:bold;">' + m.active_tasks + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#67c23a;">' + fmtNum(m.completed) + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:' + (m.failed > 0 ? '#f56c6c' : '#909399') + ';">' + fmtNum(m.failed) + '</td>'
                + '<td style="padding:9px 8px;text-align:right;border-bottom:1px solid #ebeef5;color:#fc8452;font-weight:bold;">¥' + (m.earned / 100).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;color:#909399;">' + (m.last_reported || '-') + '</td>'
                + '<td style="padding:9px 8px;text-align:center;border-bottom:1px solid #ebeef5;">'
                + '<a href="javascript:void(0)" onclick="caDeleteMachine(\'' + escapedName + '\')" style="color:#f56c6c;font-size:12px;text-decoration:none;cursor:pointer;padding:4px 10px;border:1px solid #f56c6c;border-radius:4px;transition:all .2s;" onmouseover="this.style.background=\'#f56c6c\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'\';this.style.color=\'#f56c6c\'">删除</a>'
                + '</td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var statusLabels = { 'active': '运行中', 'disabled': '已停用', 'deleted': '已删除' };
    var statusColors2 = { 'active': '#67c23a', 'disabled': '#f56c6c', 'deleted': '#909399' };

    function renderAccountTable(accounts) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();

        var hasCheckbox = $('#builder-table-head thead th').first().find('input[type=checkbox]').length > 0;
        var colCount = hasCheckbox ? 13 : 12;

        if (!accounts || accounts.length === 0) {
            tbody.append('<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        for (var i = 0; i < accounts.length; i++) {
            var a = accounts[i];
            var sColor = statusColors2[a.status] || '#909399';
            var sLabel = statusLabels[a.status] || a.status;
            var balColor = a.balance > 100 ? '#67c23a' : (a.balance > 0 ? '#e6a23c' : '#f56c6c');
            var taskColor = a.active_tasks > 0 ? '#409eff' : '#909399';

            var checkTd = hasCheckbox ? '<td><div class="table-cell"><input type="checkbox" name="ids[]" value="' + a.id + '"></div></td>' : '';

            var row = '<tr>'
                + checkTd
                + '<td><div class="table-cell">' + a.id + '</div></td>'
                + '<td><div class="table-cell">' + a.machine_name + '</div></td>'
                + '<td><div class="table-cell">' + (a.name || '-') + '</div></td>'
                + '<td><div class="table-cell"><span style="font-family:monospace;font-size:12px;color:#606266;" title="' + (a.token || '') + '">' + (a.token ? a.token.substring(0, 8) + '...' : '-') + '</span>' + (a.token ? ' <a href="javascript:void(0)" onclick="caCopyToken(\'' + a.token + '\', this)" style="font-size:12px;color:#409eff;text-decoration:none;cursor:pointer;">复制</a>' : '') + '</div></td>'
                + '<td><div class="table-cell"><span style="color:' + sColor + ';font-weight:bold">' + sLabel + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + balColor + ';font-weight:bold">' + a.balance + '</span></div></td>'
                + '<td><div class="table-cell">' + a.max_concurrency + '</div></td>'
                + '<td><div class="table-cell"><span style="color:' + taskColor + ';font-weight:bold">' + a.active_tasks + '</span></div></td>'
                + '<td><div class="table-cell">' + a.completed_count + '</div></td>'
                + '<td><div class="table-cell">' + a.failed_count + '</div></td>'
                + '<td><div class="table-cell"><span style="color:#fc8452;font-weight:bold">¥' + (a.earned_money / 100).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</span></div></td>'
                + '<td><div class="table-cell">' + (a.reported_at || '-') + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    var autoRefresh = true;
    var countdown = 10;
    var _lastData = null;

    function getFilters() {
        return {
            machine: ($('#ca-search-machine').val() || '').trim().toLowerCase(),
            name:    ($('#ca-search-name').val() || '').trim().toLowerCase(),
            token:   ($('#ca-search-token').val() || '').trim().toLowerCase(),
            status:  ($('#ca-search-status').val() || '')
        };
    }

    function filterAccounts(accounts) {
        var f = getFilters();
        if (!f.machine && !f.name && !f.token && !f.status) return accounts;
        var result = [];
        for (var i = 0; i < accounts.length; i++) {
            var a = accounts[i];
            if (f.machine && (a.machine_name || '').toLowerCase().indexOf(f.machine) === -1) continue;
            if (f.name && (a.name || '').toLowerCase().indexOf(f.name) === -1) continue;
            if (f.token && (a.token || '').toLowerCase().indexOf(f.token) === -1) continue;
            if (f.status && a.status !== f.status) continue;
            result.push(a);
        }
        return result;
    }

    window.caCopyToken = function(token, btn) {
        navigator.clipboard.writeText(token).then(function() {
            var orig = btn.textContent;
            btn.textContent = '已复制';
            btn.style.color = '#67c23a';
            setTimeout(function() { btn.textContent = orig; btn.style.color = ''; }, 1200);
        });
    };

    function applyFilter() {
        if (!_lastData) return;
        var filtered = filterAccounts(_lastData.accounts);
        renderAccountTable(filtered);
        var total = _lastData.accounts.length;
        var shown = filtered.length;
        if (shown < total) {
            $('#ca-filter-count').text('显示 ' + shown + ' / ' + total + ' 条');
        } else {
            $('#ca-filter-count').text('');
        }
    }

    window.caFilter = function() { applyFilter(); };
    window.caClearFilter = function() {
        $('#ca-search-machine').val('');
        $('#ca-search-name').val('');
        $('#ca-search-token').val('');
        $('#ca-search-status').val('');
        $('#ca-filter-count').text('');
        applyFilter();
    };

    $('#ca-search-machine, #ca-search-name, #ca-search-token').on('keyup', function(e) {
        if (e.keyCode === 13) applyFilter();
    });
    $('#ca-search-status').on('change', function() { applyFilter(); });

    function fetchData() {
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) {
                    $('#ca-status-text').text('API 请求失败: ' + (res.msg || ''));
                    return;
                }
                var d = res.data;
                var o = d.overview;
                _lastData = d;

                $('#ca-total').text(o.totalAccounts);
                $('#ca-active').text(o.activeAccounts);
                $('#ca-disabled').text(o.disabledAccounts);
                $('#ca-balance').text(fmtNum(o.totalBalance));
                $('#ca-tasks').text(o.totalActiveTasks);
                $('#ca-completed').text(fmtNum(o.totalCompleted));
                $('#ca-failed').text(fmtNum(o.totalFailed));
                $('#ca-earned').text('¥' + (o.totalEarned / 100).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                $('#ca-rate').text(o.successRate + '%');

                var statusPie = [];
                for (var i = 0; i < d.statusDist.length; i++) {
                    var item = d.statusDist[i];
                    statusPie.push({
                        name: (statusLabels[item.name] || item.name) + ' (' + item.name + ')',
                        value: item.value,
                        itemStyle: { color: statusColorMap[item.name] || '#909399' }
                    });
                }
                chartStatus.setOption({ series: [{ data: statusPie }] });

                chartMachine.setOption({ series: [{ data: d.machineDist }] });

                renderMachineTable(d.machineDetails);
                applyFilter();

                $('#ca-update-time').text('最后更新: ' + res.time);
                $('#ca-status-text').text('数据已更新');
            },
            error: function() {
                $('#ca-status-text').text('数据请求失败，将自动重试');
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
        $('#ca-status-text').text('下次刷新: ' + countdown + ' 秒');
    }

    window.caToggle = function() {
        autoRefresh = !autoRefresh;
        var btn = document.getElementById('ca-btn-toggle');
        var dot = document.getElementById('ca-dot');
        if (autoRefresh) {
            btn.textContent = '暂停刷新';
            btn.className = 'ca-btn active';
            dot.className = 'dot';
            countdown = 10;
            $('#ca-status-text').text('已恢复自动刷新');
        } else {
            btn.textContent = '恢复刷新';
            btn.className = 'ca-btn';
            dot.className = 'dot paused';
            $('#ca-status-text').text('自动刷新已暂停');
        }
    };

    window.caRefresh = function() {
        fetchData();
        countdown = 10;
    };

    window.caDeleteMachine = function(machineName) {
        if (!confirm('确认删除机器 "' + machineName + '" 的所有记录？此操作不可恢复！')) return;
        $.ajax({
            url: '{$deleteUrl}',
            type: 'POST',
            data: { machine_name: machineName },
            dataType: 'json',
            success: function(res) {
                if (res.code === 0) {
                    alert(res.msg || '删除成功');
                    fetchData();
                    countdown = 10;
                } else {
                    alert(res.msg || '删除失败');
                }
            },
            error: function() {
                alert('请求失败，请重试');
            }
        });
    };

    fetchData();
    setInterval(tick, 1000);
})();
</script>
JS;
    }
}
