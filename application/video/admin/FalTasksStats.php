<?php
// Fal 任务消耗大户统计
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FalTasksModel;

class FalTasksStats extends Admin {
    
    /**
     * 24小时消耗大户统计
     */
    public function index() 
    {
        // 获取时间范围参数，默认24小时
        $hours = input('param.hours', 24, 'intval');
        if (!in_array($hours, [6, 12, 24, 48, 168])) {
            $hours = 24;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        // 1. 按用户聚合消耗统计（is_refund=0 成功消耗，is_refund=1 已退款=失败）
        $userStats = Db::connect('translate')->table('ts_fal_tasks')
            ->field([
                'user_id',
                'COUNT(*) as total_tasks',
                'SUM(CASE WHEN is_refund = 0 THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN is_refund = 1 THEN 1 ELSE 0 END) as failed_count',
                'SUM(money) as total_charge',
                'SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END) as total_refund',
                'SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END) as net_cost',
                'GROUP_CONCAT(DISTINCT app_name SEPARATOR ", ") as used_models',
            ])
            ->where('created_at', '>=', $startTime)
            ->group('user_id')
            ->order('net_cost desc')
            ->limit(100)
            ->select();

        if (empty($userStats)) {
            return ZBuilder::make('table')
                ->setPageTitle("Fal 消耗大户统计 (最近{$hours}小时)")
                ->setPageTips('当前时间范围内暂无任务数据')
                ->setExtraHtml($this->buildTimeRangeHtml($hours), 'toolbar_top')
                ->addColumns([
                    ['user_id', '用户ID'],
                    ['user_name', '用户名'],
                ])
                ->setRowList([])
                ->fetch();
        }

        // 2. 收集所有 user_id
        $userIds = array_column($userStats, 'user_id');

        // 3. 批量查询用户信息
        $users = Db::connect('translate')->table('ts_users')
            ->whereIn('id', $userIds)
            ->column('id, name, phone, points_balance, cash_balance, vip_level, pay_cnt', 'id');

        // 4. 批量查询用户充值汇总（status=2 为已支付）
        $rechargeStats = Db::connect('translate')->table('ts_pay_order_info')
            ->field([
                'user_id',
                'SUM(money) as total_recharge',
                'COUNT(*) as recharge_count',
                'MAX(time) as last_recharge_time',
            ])
            ->whereIn('user_id', $userIds)
            ->where('status', 2)
            ->group('user_id')
            ->select();
        
        $rechargeMap = [];
        foreach ($rechargeStats as $r) {
            $rechargeMap[$r['user_id']] = $r;
        }

        // 5. 全局汇总统计
        $globalStats = Db::connect('translate')->table('ts_fal_tasks')
            ->field([
                'COUNT(*) as total_tasks',
                'SUM(CASE WHEN is_refund = 0 THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN is_refund = 1 THEN 1 ELSE 0 END) as failed_count',
                'SUM(money) as total_charge',
                'SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END) as total_refund',
                'COUNT(DISTINCT user_id) as active_users',
            ])
            ->where('created_at', '>=', $startTime)
            ->find();

        $globalNetCost = ($globalStats['total_charge'] ?? 0) - ($globalStats['total_refund'] ?? 0);  // 净消耗 = 总扣费 - 总退费
        $globalFailRate = $globalStats['total_tasks'] > 0 
            ? round(($globalStats['failed_count'] ?? 0) / $globalStats['total_tasks'] * 100, 1) 
            : 0;

        // 6. 组装表格数据
        $vipNames = [0 => '非会员', 1 => '普通会员', 2 => '高级会员', 3 => '铜牌会员'];
        $dataList = [];
        $rank = 0;
        foreach ($userStats as $stat) {
            $rank++;
            $uid = $stat['user_id'];
            $user = $users[$uid] ?? [];
            $recharge = $rechargeMap[$uid] ?? [];

            $failRate = $stat['total_tasks'] > 0 
                ? round($stat['failed_count'] / $stat['total_tasks'] * 100, 1) 
                : 0;

            $dataList[] = [
                'id'              => $uid,
                'rank'            => $rank,
                'user_id'         => $uid,
                'user_name'       => $user['name'] ?? '-',
                'phone'           => $user['phone'] ?? '-',
                'vip_level'       => isset($user['vip_level']) ? ($vipNames[$user['vip_level']] ?? $user['vip_level']) : '-',
                'total_tasks'     => $stat['total_tasks'],
                'success_count'   => $stat['success_count'],
                'failed_count'    => $stat['failed_count'],
                'fail_rate'       => $failRate . '%',
                'total_charge'    => $stat['total_charge'],
                'total_refund'    => $stat['total_refund'],
                'net_cost'        => $stat['net_cost'],
                'used_models'     => $stat['used_models'],
                'points_balance'  => $user['points_balance'] ?? 0,
                'cash_balance'    => $user['cash_balance'] ?? 0,
                'total_recharge'  => isset($recharge['total_recharge']) ? round($recharge['total_recharge'] / 100, 2) : 0,
                'recharge_count'  => $recharge['recharge_count'] ?? 0,
                'last_recharge'   => $recharge['last_recharge_time'] ?? '-',
            ];
        }

        // 7. 查询时间趋势数据
        $isSameDay = ($hours <= 48);
        $groupBy = $isSameDay ? 'DATE_FORMAT(created_at, "%m-%d %H:00")' : 'DATE(created_at)';
        $trendData = Db::connect('translate')->table('ts_fal_tasks')
            ->field([
                "{$groupBy} as time_axis",
                'SUM(money) as total_charge',
                'SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END) as total_refund',
                'SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END) as net_cost',
            ])
            ->where('created_at', '>=', $startTime)
            ->group('time_axis')
            ->order('time_axis asc')
            ->select();

        // 8. 构建图表
        $chartHtml = '<div id="trend_chart" style="width: 100%;height:350px;margin-bottom:10px;"></div>'
            . '<div style="display: flex; width: 100%;">
                <div id="bar_chart" style="width: 60%;height:350px;"></div>
                <div id="pie_chart" style="width: 40%;height:350px;"></div>
            </div>';
        $chartJs = $this->getTrendChartJs($trendData, $hours)
                 . $this->getBarChartJs(array_slice($dataList, 0, 20)) 
                 . $this->getPieChartJs(array_slice($dataList, 0, 15))
                 . $this->getSortableTableJs();

        // 9. 页面提示信息
        $tips = "📊 最近 <b>{$hours} 小时</b> 统计 | "
            . "总任务：<b>{$globalStats['total_tasks']}</b> (成功 <span style='color:green'>{$globalStats['success_count']}</span> / 失败 <span style='color:#ee6666'>{$globalStats['failed_count']}</span> / 失败率 {$globalFailRate}%) | "
            . "总扣费：<b>{$globalStats['total_charge']}</b> 分 | "
            . "总退费：<b style='color:#ee6666'>{$globalStats['total_refund']}</b> 分 | "
            . "净消耗：<b style='color:#91cc75'>{$globalNetCost}</b> 分 | "
            . "活跃用户：<b>{$globalStats['active_users']}</b> 人";

        return ZBuilder::make('table')
            ->setPageTitle("Fal 消耗大户统计 (最近{$hours}小时)")
            ->setPageTips($tips, 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->addColumns([
                ['rank', '排名'],
                ['user_id', '用户ID'],
                ['user_name', '用户名'],
                ['phone', '手机号'],
                ['vip_level', 'VIP等级'],
                ['total_tasks', '总任务数'],
                ['success_count', '成功', 'callback', function($value){
                    return "<span style='color:green'>{$value}</span>";
                }],
                ['failed_count', '失败', 'callback', function($value){
                    $color = $value > 0 ? '#ee6666' : '#999';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['fail_rate', '失败率', 'callback', function($value){
                    $num = floatval($value);
                    $color = $num >= 30 ? 'red' : ($num >= 10 ? '#e6a23c' : 'green');
                    return "<span style='color:{$color};font-weight:" . ($num >= 30 ? 'bold' : 'normal') . "'>{$value}</span>";
                }],
                ['total_charge', '总扣费(分)'],
                ['total_refund', '总退费(分)', 'callback', function($value){
                    $color = $value > 0 ? '#ee6666' : '#999';
                    return "<span style='color:{$color}'>{$value}</span>";
                }],
                ['net_cost', '净消耗(分)', 'callback', function($value){
                    $style = 'font-weight:bold;';
                    if ($value >= 10000) {
                        $style .= 'color:#91cc75;font-size:14px;';
                    } else {
                        $style .= 'color:#91cc75;';
                    }
                    return "<span style='{$style}'>{$value}</span>";
                }],
                ['used_models', '使用模型', 'callback', function($value){
                    if (empty($value)) return '-';
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30) . '...' : $value;
                }],
                ['points_balance', '积分余额'],
                ['cash_balance', '现金余额'],
                ['total_recharge', '累计充值(元)'],
                ['recharge_count', '充值次数'],
                ['last_recharge', '最近充值时间'],
            ])
            ->setExtraHtml($this->buildTimeRangeHtml($hours) . $chartHtml, 'toolbar_top')
            ->setRowList($dataList)
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraJs($chartJs)
            ->fetch();
    }

    /**
     * 构建时间范围选择器 HTML
     */
    private function buildTimeRangeHtml($currentHours) 
    {
        $options = [
            6   => '最近6小时',
            12  => '最近12小时',
            24  => '最近24小时',
            48  => '最近48小时',
            168 => '最近7天',
        ];

        $html = '<div style="margin-bottom:10px;padding:10px;background:#f5f7fa;border-radius:4px;">';
        $html .= '<span style="margin-right:10px;font-weight:bold;">时间范围：</span>';
        foreach ($options as $h => $label) {
            $active = ($h == $currentHours);
            $style = $active 
                ? 'background:#409eff;color:#fff;border-color:#409eff;' 
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['hours' => $h]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 15px;margin-right:5px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;font-size:13px;{$style}'>{$label}</a>";
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 趋势折线图 - 消耗/退费/净消耗随时间变化
     */
    private function getTrendChartJs($trendData, $hours)
    {
        if (empty($trendData)) return '';

        $timeLabels = [];
        $chargeData = [];
        $refundData = [];
        $netData = [];

        foreach ($trendData as $row) {
            $timeLabels[] = $row['time_axis'];
            $chargeData[] = intval($row['total_charge']);
            $refundData[] = intval($row['total_refund']);
            $netData[] = intval($row['net_cost']);
        }

        $timeJson = json_encode($timeLabels, JSON_UNESCAPED_UNICODE);
        $chargeJson = json_encode($chargeData);
        $refundJson = json_encode($refundData);
        $netJson = json_encode($netData);

        $xAxisType = ($hours <= 48) ? '小时' : '日期';

        return "
        <script type='text/javascript'>
        var trendChart = echarts.init(document.getElementById('trend_chart'));
        trendChart.setOption({
            title: { text: '消耗趋势 (按{$xAxisType})', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis' },
            legend: { data: ['总扣费', '总退费', '净消耗'], top: 5, right: 20 },
            grid: { left: '3%', right: '4%', bottom: '3%', top: 45, containLabel: true },
            toolbox: { feature: { saveAsImage: {} } },
            xAxis: { type: 'category', boundaryGap: false, data: {$timeJson}, axisLabel: { fontSize: 11, rotate: " . ($hours > 48 ? 0 : 30) . " } },
            yAxis: { type: 'value', axisLabel: { fontSize: 11 } },
            series: [
                {
                    name: '总扣费', type: 'line', data: {$chargeJson}, smooth: true,
                    itemStyle: { color: '#5470c6' }, lineStyle: { color: '#5470c6', width: 2 },
                    areaStyle: { color: 'rgba(84,112,198,0.1)' },
                    label: { show: true, position: 'top', fontSize: 10 }
                },
                {
                    name: '总退费', type: 'line', data: {$refundJson}, smooth: true,
                    itemStyle: { color: '#ee6666' }, lineStyle: { color: '#ee6666', width: 2 },
                    areaStyle: { color: 'rgba(238,102,102,0.1)' },
                    label: { show: true, position: 'top', fontSize: 10 }
                },
                {
                    name: '净消耗', type: 'line', data: {$netJson}, smooth: true,
                    itemStyle: { color: '#91cc75' }, lineStyle: { color: '#91cc75', width: 3 },
                    areaStyle: { color: 'rgba(145,204,117,0.15)' },
                    label: { show: true, position: 'top', fontSize: 10, fontWeight: 'bold' }
                }
            ]
        });
        </script>";
    }

    /**
     * 柱状图 - Top N 用户消耗对比
     */
    private function getBarChartJs($topUsers) 
    {
        if (empty($topUsers)) return '';

        $names = [];
        $chargeData = [];
        $refundData = [];
        $netData = [];

        foreach (array_reverse($topUsers) as $u) {
            $displayName = $u['user_name'] != '-' ? $u['user_name'] : 'UID:' . $u['user_id'];
            $names[] = mb_strlen($displayName) > 8 ? mb_substr($displayName, 0, 8) . '..' : $displayName;
            $chargeData[] = $u['total_charge'];
            $refundData[] = $u['total_refund'];
            $netData[] = $u['net_cost'];
        }

        $namesJson = json_encode($names, JSON_UNESCAPED_UNICODE);
        $chargeJson = json_encode($chargeData);
        $refundJson = json_encode($refundData);
        $netJson = json_encode($netData);

        return "
        <script type='text/javascript'>
        var barChart = echarts.init(document.getElementById('bar_chart'));
        barChart.setOption({
            title: { text: 'Top 用户消耗对比', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { data: ['总扣费', '总退费', '净值消耗'], top: 25 },
            grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
            xAxis: { type: 'value' },
            yAxis: { type: 'category', data: {$namesJson}, axisLabel: { fontSize: 11 } },
            series: [
                { name: '总扣费', type: 'bar', stack: 'cost', data: {$chargeJson}, itemStyle: { color: '#5470c6' }, label: { show: false } },
                { name: '总退费', type: 'bar', stack: 'refund', data: {$refundJson}, itemStyle: { color: '#ee6666' }, label: { show: false } },
                { name: '净值消耗', type: 'bar', data: {$netJson}, itemStyle: { color: '#91cc75' }, label: { show: true, position: 'right', fontSize: 11 } }
            ]
        });
        </script>";
    }

    /**
     * 表头排序 JS
     */
    private function getSortableTableJs()
    {
        return <<<'JS'
<script type="text/javascript">
$(function() {
    // ZBuilder 表头和表体在不同的 table 中
    var $thead = $('#builder-table-head thead');
    var $tbody = $('#builder-table-main tbody');
    if (!$thead.length || !$tbody.length) return;

    $thead.find('th').each(function(index) {
        var $th = $(this);
        // 跳过复选框列
        if ($th.find('input[type=checkbox]').length > 0) return;
        // 跳过操作列
        if ($th.text().trim() === '操作') return;

        $th.css({ cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' })
           .attr('data-sort-dir', 'none')
           .attr('data-col-idx', index);

        $th.append('<span class="sort-icon" style="display:inline-block;margin-left:3px;font-size:10px;color:#c0c4cc;vertical-align:middle;">⇅</span>');

        $th.on('click', function() {
            var colIdx = parseInt($(this).attr('data-col-idx'));
            var dir = $(this).attr('data-sort-dir');
            var newDir = (dir === 'asc') ? 'desc' : 'asc';

            // 重置所有表头状态
            $thead.find('th').attr('data-sort-dir', 'none')
                  .find('.sort-icon').css('color', '#c0c4cc').html('⇅');

            // 设置当前列排序状态
            $(this).attr('data-sort-dir', newDir);
            $(this).find('.sort-icon').css('color', '#409eff')
                   .html(newDir === 'asc' ? '↑' : '↓');

            // 排序行（表体在 #builder-table-main 中）
            var rows = $tbody.find('tr').toArray();
            rows.sort(function(a, b) {
                var aCell = $(a).find('td').eq(colIdx);
                var bCell = $(b).find('td').eq(colIdx);
                // 从 .table-cell 中取文本，兼顾 span 包裹的内容
                var aDiv = aCell.find('.table-cell');
                var bDiv = bCell.find('.table-cell');
                var aText = (aDiv.length ? aDiv : aCell).text().trim();
                var bText = (bDiv.length ? bDiv : bCell).text().trim();

                // 尝试数值排序（去除 %, 元, 分 等后缀）
                var aNum = parseFloat(aText.replace(/[%,，元分\s]/g, ''));
                var bNum = parseFloat(bText.replace(/[%,，元分\s]/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newDir === 'asc' ? aNum - bNum : bNum - aNum;
                }
                // 文本排序
                return newDir === 'asc' ? aText.localeCompare(bText, 'zh') : bText.localeCompare(aText, 'zh');
            });

            $tbody.empty().append(rows);
        });

        // hover 效果
        $th.on('mouseenter', function() {
            if ($(this).attr('data-sort-dir') === 'none') {
                $(this).find('.sort-icon').css('color', '#909399');
            }
        }).on('mouseleave', function() {
            if ($(this).attr('data-sort-dir') === 'none') {
                $(this).find('.sort-icon').css('color', '#c0c4cc');
            }
        });
    });
});
</script>
JS;
    }

    /**
     * 饼图 - 用户消耗占比
     */
    private function getPieChartJs($topUsers) 
    {
        if (empty($topUsers)) return '';

        $pieData = [];
        foreach ($topUsers as $u) {
            $displayName = $u['user_name'] != '-' ? $u['user_name'] : 'UID:' . $u['user_id'];
            $pieData[] = [
                'name' => $displayName,
                'value' => max(0, $u['net_cost']),
            ];
        }

        $pieJson = json_encode($pieData, JSON_UNESCAPED_UNICODE);

        return "
        <script type='text/javascript'>
        var pieChart = echarts.init(document.getElementById('pie_chart'));
        pieChart.setOption({
            title: { text: '净值消耗占比', left: 'center', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'item', formatter: '{b}: {c} 分 ({d}%)' },
            legend: { orient: 'vertical', left: 'left', top: 30, textStyle: { fontSize: 10 } },
            series: [{
                name: '净值消耗',
                type: 'pie',
                radius: ['35%', '65%'],
                center: ['60%', '55%'],
                avoidLabelOverlap: false,
                label: { show: false, position: 'center' },
                emphasis: { label: { show: true, fontSize: 12, fontWeight: 'bold' } },
                labelLine: { show: false },
                data: {$pieJson}
            }]
        });
        </script>";
    }
}
