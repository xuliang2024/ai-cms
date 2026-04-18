<?php
// 模型 Money 消耗排行统计
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ModelMoneyStats extends Admin {
    
    /**
     * 模型消费排行统计
     */
    public function index() 
    {
        // 获取时间范围参数，默认24小时
        $hours = input('param.hours', 24, 'intval');
        if (!in_array($hours, [6, 12, 24, 48, 168])) {
            $hours = 24;
        }

        $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        // 1. 按模型(app_name)聚合消耗统计
        $modelStats = Db::connect('translate')->table('ts_fal_tasks')
            ->field([
                'app_name',
                'COUNT(*) as total_tasks',
                'SUM(CASE WHEN is_refund = 0 THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN is_refund = 1 THEN 1 ELSE 0 END) as failed_count',
                'SUM(money) as total_charge',
                'SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END) as total_refund',
                'SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END) as net_cost',
                'COUNT(DISTINCT user_id) as user_count',
            ])
            ->where('created_at', '>=', $startTime)
            ->group('app_name')
            ->order('net_cost desc')
            ->select();

        if (empty($modelStats)) {
            return ZBuilder::make('table')
                ->setPageTitle("模型消费排行 (最近{$hours}小时)")
                ->setPageTips('当前时间范围内暂无任务数据')
                ->setExtraHtml($this->buildTimeRangeHtml($hours), 'toolbar_top')
                ->addColumns([
                    ['app_name', '模型名称'],
                    ['net_cost', '净消耗(分)'],
                ])
                ->setRowList([])
                ->fetch();
        }

        // 2. 全局汇总统计
        $globalStats = Db::connect('translate')->table('ts_fal_tasks')
            ->field([
                'COUNT(*) as total_tasks',
                'SUM(CASE WHEN is_refund = 0 THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN is_refund = 1 THEN 1 ELSE 0 END) as failed_count',
                'SUM(money) as total_charge',
                'SUM(CASE WHEN is_refund = 1 THEN money ELSE 0 END) as total_refund',
                'COUNT(DISTINCT app_name) as model_count',
                'COUNT(DISTINCT user_id) as active_users',
            ])
            ->where('created_at', '>=', $startTime)
            ->find();

        $globalNetCost = ($globalStats['total_charge'] ?? 0) - ($globalStats['total_refund'] ?? 0);
        $globalFailRate = $globalStats['total_tasks'] > 0 
            ? round(($globalStats['failed_count'] ?? 0) / $globalStats['total_tasks'] * 100, 1) 
            : 0;

        // 3. 组装表格数据
        $dataList = [];
        $rank = 0;
        foreach ($modelStats as $stat) {
            $rank++;
            $failRate = $stat['total_tasks'] > 0 
                ? round($stat['failed_count'] / $stat['total_tasks'] * 100, 1) 
                : 0;
            $avgCost = $stat['success_count'] > 0 
                ? round($stat['net_cost'] / $stat['success_count'], 1) 
                : 0;

            $dataList[] = [
                'id'              => $rank,
                'rank'            => $rank,
                'app_name'        => $stat['app_name'] ?: '(未知)',
                'total_tasks'     => $stat['total_tasks'],
                'success_count'   => $stat['success_count'],
                'failed_count'    => $stat['failed_count'],
                'fail_rate'       => $failRate . '%',
                'total_charge'    => $stat['total_charge'],
                'total_refund'    => $stat['total_refund'],
                'net_cost'        => $stat['net_cost'],
                'net_cost_yuan'   => round($stat['net_cost'] / 100, 2),
                'avg_cost'        => $avgCost,
                'user_count'      => $stat['user_count'],
            ];
        }

        // 4. 查询 Top 模型的时间趋势数据（取净消耗前8的模型）
        $topModelNames = array_slice(array_column($dataList, 'app_name'), 0, 8);
        $isSameDay = ($hours <= 48);
        $groupBy = $isSameDay ? 'DATE_FORMAT(created_at, "%m-%d %H:00")' : 'DATE(created_at)';

        $trendData = [];
        if (!empty($topModelNames)) {
            $trendData = Db::connect('translate')->table('ts_fal_tasks')
                ->field([
                    "{$groupBy} as time_axis",
                    'app_name',
                    'SUM(CASE WHEN is_refund = 0 THEN money ELSE 0 END) as net_cost',
                ])
                ->where('created_at', '>=', $startTime)
                ->whereIn('app_name', $topModelNames)
                ->group("time_axis, app_name")
                ->order('time_axis asc')
                ->select();
        }

        // 5. 构建图表
        $chartHtml = '<div id="trend_chart" style="width: 100%;height:400px;margin-bottom:10px;"></div>'
            . '<div style="display: flex; width: 100%;">
                <div id="bar_chart" style="width: 60%;height:400px;"></div>
                <div id="pie_chart" style="width: 40%;height:400px;"></div>
            </div>';
        $chartJs = $this->getTrendChartJs($trendData, $topModelNames, $hours)
                 . $this->getBarChartJs(array_slice($dataList, 0, 20)) 
                 . $this->getPieChartJs(array_slice($dataList, 0, 15))
                 . $this->getSortableTableJs();

        // 6. 页面提示信息
        $tips = "📊 最近 <b>{$hours} 小时</b> | "
            . "模型数：<b>{$globalStats['model_count']}</b> | "
            . "总任务：<b>{$globalStats['total_tasks']}</b> "
            . "(成功 <span style='color:green'>{$globalStats['success_count']}</span> / "
            . "失败 <span style='color:#ee6666'>{$globalStats['failed_count']}</span> / "
            . "失败率 {$globalFailRate}%) | "
            . "总扣费：<b>{$globalStats['total_charge']}</b> 分 | "
            . "总退费：<b style='color:#ee6666'>{$globalStats['total_refund']}</b> 分 | "
            . "净消耗：<b style='color:#91cc75'>{$globalNetCost}</b> 分 "
            . "(<b style='color:#91cc75'>" . round($globalNetCost / 100, 2) . "</b> 元) | "
            . "活跃用户：<b>{$globalStats['active_users']}</b> 人";

        return ZBuilder::make('table')
            ->setPageTitle("模型消费排行 (最近{$hours}小时)")
            ->setPageTips($tips, 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->addColumns([
                ['rank', '排名'],
                ['app_name', '模型名称', 'callback', function($value){
                    return "<span style='font-weight:bold;color:#409eff;'>{$value}</span>";
                }],
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
                ['net_cost_yuan', '净消耗(元)', 'callback', function($value){
                    $style = 'font-weight:bold;color:#67c23a;';
                    if ($value >= 100) {
                        $style .= 'font-size:14px;';
                    }
                    return "<span style='{$style}'>¥{$value}</span>";
                }],
                ['avg_cost', '平均单价(分)', 'callback', function($value){
                    return "<span style='color:#e6a23c;'>{$value}</span>";
                }],
                ['user_count', '使用人数'],
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
     * 趋势折线图 - Top N 模型的净消耗随时间变化（多线图）
     */
    private function getTrendChartJs($trendData, $topModelNames, $hours)
    {
        if (empty($trendData) || empty($topModelNames)) return '';

        // 收集所有时间点
        $timeSet = [];
        foreach ($trendData as $row) {
            $timeSet[$row['time_axis']] = true;
        }
        $timeLabels = array_keys($timeSet);
        sort($timeLabels);

        // 按模型组织数据
        $modelData = [];
        foreach ($trendData as $row) {
            $modelData[$row['app_name']][$row['time_axis']] = intval($row['net_cost']);
        }

        // 构建 series
        $colors = ['#5470c6','#91cc75','#fac858','#ee6666','#73c0de','#3ba272','#fc8452','#9a60b4'];
        $seriesArr = [];
        $legendNames = [];
        $idx = 0;
        foreach ($topModelNames as $modelName) {
            if (!isset($modelData[$modelName])) continue;
            $lineData = [];
            foreach ($timeLabels as $t) {
                $lineData[] = $modelData[$modelName][$t] ?? 0;
            }
            $color = $colors[$idx % count($colors)];
            // 简化模型名称用于图例显示
            $shortName = $this->shortenModelName($modelName);
            $legendNames[] = $shortName;
            $seriesArr[] = [
                'name' => $shortName,
                'type' => 'line',
                'data' => $lineData,
                'smooth' => true,
                'itemStyle' => ['color' => $color],
                'lineStyle' => ['color' => $color, 'width' => 2],
                'emphasis' => ['focus' => 'series'],
            ];
            $idx++;
        }

        $timeJson = json_encode($timeLabels, JSON_UNESCAPED_UNICODE);
        $legendJson = json_encode($legendNames, JSON_UNESCAPED_UNICODE);
        $seriesJson = json_encode($seriesArr, JSON_UNESCAPED_UNICODE);
        $xAxisType = ($hours <= 48) ? '小时' : '日期';

        return "
        <script type='text/javascript'>
        var trendChart = echarts.init(document.getElementById('trend_chart'));
        trendChart.setOption({
            title: { text: 'Top 模型消耗趋势 (按{$xAxisType})', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis', confine: true },
            legend: { data: {$legendJson}, top: 5, right: 20, textStyle: { fontSize: 11 }, type: 'scroll' },
            grid: { left: '3%', right: '4%', bottom: '3%', top: 50, containLabel: true },
            toolbox: { feature: { saveAsImage: {} } },
            xAxis: { type: 'category', boundaryGap: false, data: {$timeJson}, axisLabel: { fontSize: 11, rotate: " . ($hours > 48 ? 0 : 30) . " } },
            yAxis: { type: 'value', axisLabel: { fontSize: 11 } },
            series: {$seriesJson}
        });
        </script>";
    }

    /**
     * 柱状图 - Top N 模型消耗对比
     */
    private function getBarChartJs($topModels) 
    {
        if (empty($topModels)) return '';

        $names = [];
        $chargeData = [];
        $refundData = [];
        $netData = [];

        foreach (array_reverse($topModels) as $m) {
            $shortName = $this->shortenModelName($m['app_name']);
            $names[] = mb_strlen($shortName) > 15 ? mb_substr($shortName, 0, 15) . '..' : $shortName;
            $chargeData[] = $m['total_charge'];
            $refundData[] = $m['total_refund'];
            $netData[] = $m['net_cost'];
        }

        $namesJson = json_encode($names, JSON_UNESCAPED_UNICODE);
        $chargeJson = json_encode($chargeData);
        $refundJson = json_encode($refundData);
        $netJson = json_encode($netData);

        return "
        <script type='text/javascript'>
        var barChart = echarts.init(document.getElementById('bar_chart'));
        barChart.setOption({
            title: { text: 'Top 模型消耗对比', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { data: ['总扣费', '总退费', '净消耗'], top: 25 },
            grid: { left: '3%', right: '8%', bottom: '3%', containLabel: true },
            xAxis: { type: 'value' },
            yAxis: { type: 'category', data: {$namesJson}, axisLabel: { fontSize: 11 } },
            series: [
                { name: '总扣费', type: 'bar', stack: 'cost', data: {$chargeJson}, itemStyle: { color: '#5470c6' }, label: { show: false } },
                { name: '总退费', type: 'bar', stack: 'refund', data: {$refundJson}, itemStyle: { color: '#ee6666' }, label: { show: false } },
                { name: '净消耗', type: 'bar', data: {$netJson}, itemStyle: { color: '#91cc75' }, label: { show: true, position: 'right', fontSize: 11 } }
            ]
        });
        </script>";
    }

    /**
     * 饼图 - 模型消耗占比
     */
    private function getPieChartJs($topModels) 
    {
        if (empty($topModels)) return '';

        $pieData = [];
        foreach ($topModels as $m) {
            $shortName = $this->shortenModelName($m['app_name']);
            $pieData[] = [
                'name' => $shortName,
                'value' => max(0, $m['net_cost']),
            ];
        }

        $pieJson = json_encode($pieData, JSON_UNESCAPED_UNICODE);

        return "
        <script type='text/javascript'>
        var pieChart = echarts.init(document.getElementById('pie_chart'));
        pieChart.setOption({
            title: { text: '模型消耗占比', left: 'center', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'item', formatter: '{b}: {c} 分 ({d}%)' },
            legend: { orient: 'vertical', left: 'left', top: 30, textStyle: { fontSize: 10 }, type: 'scroll', pageTextStyle: { fontSize: 10 } },
            series: [{
                name: '净消耗',
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

    /**
     * 表头排序 JS
     */
    private function getSortableTableJs()
    {
        return <<<'JS'
<script type="text/javascript">
$(function() {
    var $thead = $('#builder-table-head thead');
    var $tbody = $('#builder-table-main tbody');
    if (!$thead.length || !$tbody.length) return;

    $thead.find('th').each(function(index) {
        var $th = $(this);
        if ($th.find('input[type=checkbox]').length > 0) return;
        if ($th.text().trim() === '操作') return;

        $th.css({ cursor: 'pointer', userSelect: 'none', whiteSpace: 'nowrap' })
           .attr('data-sort-dir', 'none')
           .attr('data-col-idx', index);

        $th.append('<span class="sort-icon" style="display:inline-block;margin-left:3px;font-size:10px;color:#c0c4cc;vertical-align:middle;">⇅</span>');

        $th.on('click', function() {
            var colIdx = parseInt($(this).attr('data-col-idx'));
            var dir = $(this).attr('data-sort-dir');
            var newDir = (dir === 'asc') ? 'desc' : 'asc';

            $thead.find('th').attr('data-sort-dir', 'none')
                  .find('.sort-icon').css('color', '#c0c4cc').html('⇅');

            $(this).attr('data-sort-dir', newDir);
            $(this).find('.sort-icon').css('color', '#409eff')
                   .html(newDir === 'asc' ? '↑' : '↓');

            var rows = $tbody.find('tr').toArray();
            rows.sort(function(a, b) {
                var aCell = $(a).find('td').eq(colIdx);
                var bCell = $(b).find('td').eq(colIdx);
                var aDiv = aCell.find('.table-cell');
                var bDiv = bCell.find('.table-cell');
                var aText = (aDiv.length ? aDiv : aCell).text().trim();
                var bText = (bDiv.length ? bDiv : bCell).text().trim();

                var aNum = parseFloat(aText.replace(/[%,，元分¥\s]/g, ''));
                var bNum = parseFloat(bText.replace(/[%,，元分¥\s]/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newDir === 'asc' ? aNum - bNum : bNum - aNum;
                }
                return newDir === 'asc' ? aText.localeCompare(bText, 'zh') : bText.localeCompare(aText, 'zh');
            });

            $tbody.empty().append(rows);
        });

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
     * 简化模型名称（去除常见前缀）
     */
    private function shortenModelName($name) 
    {
        if (empty($name)) return '(未知)';
        // 去除 fal-ai/ 等常见前缀
        $name = preg_replace('#^fal-ai/#', '', $name);
        return $name;
    }
}
