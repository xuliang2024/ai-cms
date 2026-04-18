<?php
// 用户转化统计仪表盘
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserConversionStats extends Admin {

    /**
     * 渲染统计页面
     */
    public function index()
    {
        // 获取时间范围参数
        $range = input('param.range', '30', 'trim');
        if (!in_array($range, ['today', '7', '30', '90', 'custom'])) {
            $range = '30';
        }
        $customStart = input('param.start', '', 'trim');
        $customEnd   = input('param.end', '', 'trim');

        // 计算时间区间
        list($startDate, $endDate, $rangeLabel) = $this->calcDateRange($range, $customStart, $customEnd);

        // 查询付费用户排行 Top 100（用于底部 ZBuilder 表格）
        $dataList = $this->getTopPayingUsers($startDate, $endDate);

        // 构建图表容器 HTML
        $contentHtml = $this->buildTimeRangeHtml($range, $customStart, $customEnd)
                     . $this->buildDashboardHtml();

        // 构建前端 JS
        $js = $this->buildDashboardJs($range, $customStart, $customEnd, $rangeLabel);

        $vipNames = [0 => '非会员', 1 => '普通会员', 2 => '高级会员', 3 => '铜牌会员'];

        return ZBuilder::make('table')
            ->setPageTitle('用户转化统计')
            ->setPageTips("时间范围：{$rangeLabel}，展示用户注册、付费转化与收入数据", 'info')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->addColumns([
                ['rank', '排名'],
                ['user_id', '用户ID'],
                ['user_name', '用户名'],
                ['phone', '手机号'],
                ['vip_level', 'VIP等级', 'callback', function($value) use ($vipNames) {
                    $colors = [0 => '#909399', 1 => '#409eff', 2 => '#e6a23c', 3 => '#f56c6c'];
                    $name = $vipNames[$value] ?? $value;
                    $color = $colors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold'>{$name}</span>";
                }],
                ['order_count', '订单数', 'callback', function($value){
                    return "<span style='font-weight:bold;color:#409eff'>{$value}</span>";
                }],
                ['total_pay_yuan', '总付费(元)', 'callback', function($value){
                    return "<span style='font-weight:bold;color:#67c23a'>¥{$value}</span>";
                }],
                ['last_pay_time', '最近付费'],
                ['points_balance', '积分余额'],
            ])
            ->setRowList($dataList)
            ->setExtraJs($js)
            ->fetch();
    }

    /**
     * AJAX 数据接口
     */
    public function ajaxData()
    {
        $range = input('param.range', '30', 'trim');
        if (!in_array($range, ['today', '7', '30', '90', 'custom'])) {
            $range = '30';
        }
        $customStart = input('param.start', '', 'trim');
        $customEnd   = input('param.end', '', 'trim');

        list($startDate, $endDate, $rangeLabel) = $this->calcDateRange($range, $customStart, $customEnd);

        // ========== 1. 概览指标 ==========

        // 总用户数（截止当前）
        $totalUsers = Db::connect('translate')->table('ts_users')->count();

        // 时段新增用户
        $newUsers = Db::connect('translate')->table('ts_users')
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->count();

        // 付费用户数（历史累计 pay_cnt > 0）
        $totalPayingUsers = Db::connect('translate')->table('ts_users')
            ->where('pay_cnt', '>', 0)
            ->count();

        // 时段新增付费用户（时段内首次支付成功的用户）
        $newPayingUsers = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field('COUNT(DISTINCT user_id) as cnt')
            ->find();
        $newPayingUsers = intval($newPayingUsers['cnt']);

        // 总收入（历史累计）
        $totalRevenue = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->sum('money');
        $totalRevenueYuan = round($totalRevenue / 100, 2);

        // 时段收入
        $periodStats = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field([
                'IFNULL(SUM(money), 0) as total_money',
                'COUNT(*) as order_count',
                'COUNT(DISTINCT user_id) as paying_users',
            ])
            ->find();
        $periodRevenue = intval($periodStats['total_money']);
        $periodRevenueYuan = round($periodRevenue / 100, 2);
        $periodOrderCount = intval($periodStats['order_count']);
        $periodPayingUsers = intval($periodStats['paying_users']);

        // 付费转化率
        $conversionRate = $totalUsers > 0 ? round($totalPayingUsers / $totalUsers * 100, 2) : 0;

        // 客单价 ARPU（时段收入 / 时段付费用户数）
        $arpu = $periodPayingUsers > 0 ? round($periodRevenueYuan / $periodPayingUsers, 2) : 0;

        // ARPPU（时段收入 / 时段新增用户数）
        $arppu = $newUsers > 0 ? round($periodRevenueYuan / $newUsers, 2) : 0;

        // ========== 2. 用户增长与付费趋势（按天） ==========

        // 新增用户趋势
        $userTrend = Db::connect('translate')->table('ts_users')
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field([
                'DATE(time) as day',
                'COUNT(*) as new_users',
            ])
            ->group('day')
            ->order('day asc')
            ->select();
        $userTrendMap = [];
        foreach ($userTrend as $row) {
            $userTrendMap[$row['day']] = intval($row['new_users']);
        }

        // 付费趋势（按天）
        $payTrend = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field([
                'DATE(time) as day',
                'COUNT(DISTINCT user_id) as paying_users',
                'COUNT(*) as order_count',
                'IFNULL(SUM(money), 0) as revenue',
            ])
            ->group('day')
            ->order('day asc')
            ->select();
        $payTrendMap = [];
        foreach ($payTrend as $row) {
            $payTrendMap[$row['day']] = [
                'paying_users' => intval($row['paying_users']),
                'order_count'  => intval($row['order_count']),
                'revenue'      => intval($row['revenue']),
            ];
        }

        // 合并日期轴
        $allDays = array_unique(array_merge(array_keys($userTrendMap), array_keys($payTrendMap)));
        sort($allDays);

        $trendDays = [];
        $trendNewUsers = [];
        $trendPayingUsers = [];
        $trendOrders = [];
        $trendRevenue = [];

        foreach ($allDays as $day) {
            $trendDays[]        = $day;
            $trendNewUsers[]    = $userTrendMap[$day] ?? 0;
            $trendPayingUsers[] = isset($payTrendMap[$day]) ? $payTrendMap[$day]['paying_users'] : 0;
            $trendOrders[]      = isset($payTrendMap[$day]) ? $payTrendMap[$day]['order_count'] : 0;
            $trendRevenue[]     = isset($payTrendMap[$day]) ? round($payTrendMap[$day]['revenue'] / 100, 2) : 0;
        }

        // ========== 3. 转化漏斗 ==========

        // 注册用户
        $funnelRegistered = Db::connect('translate')->table('ts_users')
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->count();

        // 下单用户（包含所有状态）
        $funnelOrdered = Db::connect('translate')->table('ts_pay_order_info')
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field('COUNT(DISTINCT user_id) as cnt')
            ->find();
        $funnelOrdered = intval($funnelOrdered['cnt']);

        // 付费成功用户
        $funnelPaid = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field('COUNT(DISTINCT user_id) as cnt')
            ->find();
        $funnelPaid = intval($funnelPaid['cnt']);

        // 复购用户（时段内 >=2 笔成功订单）
        $funnelRepeat = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field('user_id, COUNT(*) as cnt')
            ->group('user_id')
            ->having('cnt >= 2')
            ->select();
        $funnelRepeat = count($funnelRepeat);

        // ========== 4. VIP 等级分布 ==========
        $vipDist = Db::connect('translate')->table('ts_users')
            ->field([
                'vip_level',
                'COUNT(*) as count',
            ])
            ->group('vip_level')
            ->select();

        $vipNames = [0 => '非会员', 1 => '普通会员', 2 => '高级会员', 3 => '铜牌会员'];
        $vipData = [];
        foreach ($vipDist as $item) {
            $vipData[] = [
                'name'  => $vipNames[$item['vip_level']] ?? ('等级' . $item['vip_level']),
                'value' => intval($item['count']),
            ];
        }

        // ========== 5. 支付方式分布 ==========
        $payTypeDist = Db::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->field([
                'pay_type',
                'COUNT(*) as count',
                'IFNULL(SUM(money), 0) as total_money',
            ])
            ->group('pay_type')
            ->select();

        $payTypeNames = [0 => '未定义', 1 => '微信支付', 2 => '支付宝', 3 => '算力激活', 4 => '月卡', 5 => '年卡', 6 => '积分'];
        $payTypeData = [];
        foreach ($payTypeDist as $item) {
            $payTypeData[] = [
                'name'  => $payTypeNames[$item['pay_type']] ?? ('类型' . $item['pay_type']),
                'value' => intval($item['count']),
                'money' => round(intval($item['total_money']) / 100, 2),
            ];
        }

        // ========== 6. 每日转化明细 ==========

        // fal_tasks 每日成功任务消耗
        $falMoneyTrend = Db::connect('translate')->table('ts_fal_tasks')
            ->where('status', 'completed')
            ->where('is_refund', 0)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->field([
                'DATE(created_at) as day',
                'IFNULL(SUM(money), 0) as total_money',
            ])
            ->group('day')
            ->order('day asc')
            ->select();
        $falMoneyMap = [];
        foreach ($falMoneyTrend as $row) {
            $falMoneyMap[$row['day']] = intval($row['total_money']);
        }

        $dailyTable = [];
        foreach ($allDays as $day) {
            $nu  = $userTrendMap[$day] ?? 0;
            $pu  = isset($payTrendMap[$day]) ? $payTrendMap[$day]['paying_users'] : 0;
            $oc  = isset($payTrendMap[$day]) ? $payTrendMap[$day]['order_count'] : 0;
            $rev = isset($payTrendMap[$day]) ? round($payTrendMap[$day]['revenue'] / 100, 2) : 0;
            $rate = $nu > 0 ? round($pu / $nu * 100, 2) : 0;
            $avgOrder = $pu > 0 ? round($rev / $pu, 2) : 0;
            $falM = isset($falMoneyMap[$day]) ? round($falMoneyMap[$day] / 100, 2) : 0;

            $dailyTable[] = [
                'day'          => $day,
                'newUsers'     => $nu,
                'payingUsers'  => $pu,
                'rate'         => $rate,
                'orderCount'   => $oc,
                'revenue'      => $rev,
                'avgOrder'     => $avgOrder,
                'falMoney'     => $falM,
            ];
        }
        // 按日期倒序
        $dailyTable = array_reverse($dailyTable);

        // ========== 7. 付费用户排行 Top 100 ==========
        $topUsers = $this->getTopPayingUsersRaw($startDate, $endDate);

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'totalUsers'       => $totalUsers,
                    'newUsers'         => $newUsers,
                    'totalPayingUsers' => $totalPayingUsers,
                    'newPayingUsers'   => $newPayingUsers,
                    'totalRevenueYuan' => $totalRevenueYuan,
                    'periodRevenueYuan'=> $periodRevenueYuan,
                    'periodOrderCount' => $periodOrderCount,
                    'conversionRate'   => $conversionRate,
                    'arpu'             => $arpu,
                    'arppu'            => $arppu,
                ],
                'trend' => [
                    'days'         => $trendDays,
                    'newUsers'     => $trendNewUsers,
                    'payingUsers'  => $trendPayingUsers,
                    'orders'       => $trendOrders,
                    'revenue'      => $trendRevenue,
                ],
                'funnel' => [
                    ['name' => '注册用户',   'value' => $funnelRegistered],
                    ['name' => '下单用户',   'value' => $funnelOrdered],
                    ['name' => '付费成功',   'value' => $funnelPaid],
                    ['name' => '复购用户',   'value' => $funnelRepeat],
                ],
                'vipDist'     => $vipData,
                'payTypeDist' => $payTypeData,
                'dailyTable'  => $dailyTable,
                'topUsers'    => $topUsers,
            ],
            'range' => $range,
            'time'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 计算时间区间
     */
    private function calcDateRange($range, $customStart, $customEnd)
    {
        switch ($range) {
            case 'today':
                $startDate  = date('Y-m-d');
                $endDate    = date('Y-m-d', strtotime('+1 day'));
                $rangeLabel = '今日';
                break;
            case '7':
                $startDate  = date('Y-m-d', strtotime('-6 days'));
                $endDate    = date('Y-m-d', strtotime('+1 day'));
                $rangeLabel = '近7天';
                break;
            case '90':
                $startDate  = date('Y-m-d', strtotime('-89 days'));
                $endDate    = date('Y-m-d', strtotime('+1 day'));
                $rangeLabel = '近90天';
                break;
            case 'custom':
                $startDate  = $customStart ?: date('Y-m-d', strtotime('-29 days'));
                $endDate    = $customEnd ? date('Y-m-d', strtotime($customEnd . ' +1 day')) : date('Y-m-d', strtotime('+1 day'));
                $rangeLabel = "{$startDate} ~ " . date('Y-m-d', strtotime($endDate . ' -1 day'));
                break;
            default: // 30
                $startDate  = date('Y-m-d', strtotime('-29 days'));
                $endDate    = date('Y-m-d', strtotime('+1 day'));
                $rangeLabel = '近30天';
                break;
        }
        return [$startDate, $endDate, $rangeLabel];
    }

    /**
     * 查询付费用户排行 Top 100（用于 ZBuilder 表格）
     */
    private function getTopPayingUsers($startDate, $endDate)
    {
        $userStats = Db::connect('translate')->table('ts_pay_order_info')
            ->field([
                'user_id',
                'COUNT(*) as order_count',
                'SUM(money) as total_money',
                'MAX(time) as last_pay_time',
            ])
            ->where('status', 2)
            ->where('time', '>=', $startDate)
            ->where('time', '<', $endDate)
            ->group('user_id')
            ->order('total_money desc')
            ->limit(100)
            ->select();

        if (empty($userStats)) {
            return [];
        }

        $userIds = array_column($userStats, 'user_id');
        $users = Db::connect('translate')->table('ts_users')
            ->whereIn('id', $userIds)
            ->column('id, name, phone, points_balance, vip_level', 'id');

        $dataList = [];
        $rank = 0;
        foreach ($userStats as $stat) {
            $rank++;
            $uid = $stat['user_id'];
            $user = $users[$uid] ?? [];

            $dataList[] = [
                'id'              => $uid,
                'rank'            => $rank,
                'user_id'         => $uid,
                'user_name'       => $user['name'] ?? '-',
                'phone'           => $user['phone'] ?? '-',
                'vip_level'       => $user['vip_level'] ?? 0,
                'order_count'     => intval($stat['order_count']),
                'total_pay_yuan'  => round(intval($stat['total_money']) / 100, 2),
                'last_pay_time'   => $stat['last_pay_time'] ?? '-',
                'points_balance'  => $user['points_balance'] ?? 0,
            ];
        }
        return $dataList;
    }

    /**
     * 查询付费用户排行原始数据（用于 AJAX 刷新）
     */
    private function getTopPayingUsersRaw($startDate, $endDate)
    {
        $dataList = $this->getTopPayingUsers($startDate, $endDate);
        $rows = [];
        foreach ($dataList as $item) {
            $rows[] = [
                'rank'           => $item['rank'],
                'user_id'        => $item['user_id'],
                'user_name'      => $item['user_name'],
                'phone'          => $item['phone'],
                'vip_level'      => $item['vip_level'],
                'order_count'    => $item['order_count'],
                'total_pay_yuan' => $item['total_pay_yuan'],
                'last_pay_time'  => $item['last_pay_time'],
                'points_balance' => $item['points_balance'],
            ];
        }
        return $rows;
    }

    /**
     * 构建时间范围选择器 HTML
     */
    private function buildTimeRangeHtml($currentRange, $customStart, $customEnd)
    {
        $options = [
            'today' => '今日',
            '7'     => '近7天',
            '30'    => '近30天',
            '90'    => '近90天',
        ];

        $html = '<div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">';
        $html .= '<span style="margin-right:6px;font-weight:bold;font-size:13px;">时间范围：</span>';

        foreach ($options as $key => $label) {
            $active = ($key == $currentRange);
            $style = $active
                ? 'background:#409eff;color:#fff;border-color:#409eff;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['range' => $key]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 15px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;transition:all .2s;{$style}'>{$label}</a>";
        }

        // 自定义日期范围
        $customActive = ($currentRange === 'custom');
        $customBtnStyle = $customActive
            ? 'background:#409eff;color:#fff;border-color:#409eff;'
            : 'background:#fff;color:#606266;border-color:#dcdfe6;';

        $startVal = $customStart ?: date('Y-m-d', strtotime('-29 days'));
        $endVal   = $customEnd ?: date('Y-m-d');

        $html .= '<span style="margin-left:12px;color:#909399;">|</span>';
        $html .= '<input type="date" id="custom-start" value="' . $startVal . '" style="padding:4px 8px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;"/>';
        $html .= '<span style="color:#909399;font-size:13px;">~</span>';
        $html .= '<input type="date" id="custom-end" value="' . $endVal . '" style="padding:4px 8px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;"/>';

        $customUrl = url('index');
        $html .= "<a href='javascript:void(0)' onclick=\"window.location.href='{$customUrl}?range=custom&start='+document.getElementById('custom-start').value+'&end='+document.getElementById('custom-end').value\" style='display:inline-block;padding:5px 15px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;transition:all .2s;{$customBtnStyle}'>查询</a>";

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
.uc-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.uc-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.uc-card {
    flex: 1; min-width: 130px; padding: 16px 18px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.uc-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.uc-card .cv { font-size: 26px; font-weight: 700; line-height: 1.2; }
.uc-card .cl { font-size: 12px; color: #909399; margin-top: 4px; }
.uc-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.uc-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.uc-trend-row { margin-bottom: 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.uc-table-wrap {
    margin-bottom: 16px; background: #fff; border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 16px;
}
.uc-table-wrap table { width: 100%; border-collapse: collapse; font-size: 13px; }
.uc-table-wrap th {
    padding: 10px 8px; text-align: center; border-bottom: 2px solid #ebeef5;
    color: #606266; font-weight: 600; background: #f5f7fa;
}
.uc-table-wrap td {
    padding: 9px 8px; text-align: center; border-bottom: 1px solid #ebeef5;
}
.uc-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.uc-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px;
    transition: all .2s;
}
.uc-btn:hover { border-color: #409eff; color: #409eff; }
</style>

<div class="uc-wrap">
    <!-- 用户指标卡片 -->
    <div class="uc-cards">
        <div class="uc-card" style="border-top-color:#409eff;">
            <div class="cv" style="color:#409eff;" id="card-total-users">-</div>
            <div class="cl">总用户数</div>
        </div>
        <div class="uc-card" style="border-top-color:#409eff;">
            <div class="cv" style="color:#409eff;" id="card-new-users">-</div>
            <div class="cl">时段新增用户</div>
        </div>
        <div class="uc-card" style="border-top-color:#67c23a;">
            <div class="cv" style="color:#67c23a;" id="card-total-paying">-</div>
            <div class="cl">付费用户数(累计)</div>
        </div>
        <div class="uc-card" style="border-top-color:#67c23a;">
            <div class="cv" style="color:#67c23a;" id="card-new-paying">-</div>
            <div class="cl">时段付费用户</div>
        </div>
        <div class="uc-card" style="border-top-color:#e6a23c;">
            <div class="cv" style="color:#e6a23c;" id="card-conversion-rate">-</div>
            <div class="cl">付费转化率</div>
        </div>
    </div>

    <!-- 金额指标卡片 -->
    <div class="uc-cards">
        <div class="uc-card" style="border-top-color:#fc8452;">
            <div class="cv" style="color:#fc8452;" id="card-total-revenue">-</div>
            <div class="cl">总收入(元)</div>
        </div>
        <div class="uc-card" style="border-top-color:#fc8452;">
            <div class="cv" style="color:#fc8452;" id="card-period-revenue">-</div>
            <div class="cl">时段收入(元)</div>
        </div>
        <div class="uc-card" style="border-top-color:#9b59b6;">
            <div class="cv" style="color:#9b59b6;" id="card-period-orders">-</div>
            <div class="cl">时段订单数</div>
        </div>
        <div class="uc-card" style="border-top-color:#3498db;">
            <div class="cv" style="color:#3498db;" id="card-arpu">-</div>
            <div class="cl">客单价 ARPU</div>
        </div>
        <div class="uc-card" style="border-top-color:#1abc9c;">
            <div class="cv" style="color:#1abc9c;" id="card-arppu">-</div>
            <div class="cl">ARPPU</div>
        </div>
    </div>

    <!-- 趋势折线图 -->
    <div class="uc-trend-row">
        <div id="chart-trend" style="width:100%;height:380px;"></div>
    </div>

    <!-- 漏斗 + VIP分布 + 支付方式分布 -->
    <div class="uc-charts-row">
        <div class="chart-box">
            <div id="chart-funnel" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="chart-vip" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="chart-paytype" style="width:100%;height:340px;"></div>
        </div>
    </div>

    <!-- 每日转化明细表格 -->
    <div class="uc-table-wrap">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">每日转化明细</div>
        <table>
            <thead>
                <tr>
                    <th>日期</th>
                    <th>新增用户</th>
                    <th style="color:#67c23a;">新增付费用户</th>
                    <th style="color:#e6a23c;">转化率</th>
                    <th style="color:#9b59b6;">订单数</th>
                    <th style="color:#fc8452;">收入(元)</th>
                    <th style="color:#3498db;">客单价(元)</th>
                    <th style="color:#ff6b6b;">任务消耗(元)</th>
                </tr>
            </thead>
            <tbody id="daily-table-body">
                <tr><td colspan="8" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 状态栏 -->
    <div class="uc-status-bar">
        <div>
            <span id="uc-status-text">正在加载数据...</span>
            <span style="color:#909399;margin-left:12px;" id="uc-last-update"></span>
        </div>
        <div>
            <button class="uc-btn" onclick="ucRefresh()">刷新数据</button>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * 构建仪表盘前端 JS
     */
    private function buildDashboardJs($range, $customStart, $customEnd, $rangeLabel)
    {
        $params = ['range' => $range];
        if ($range === 'custom') {
            $params['start'] = $customStart;
            $params['end']   = $customEnd;
        }
        $ajaxUrl = url('ajaxData', $params);

        return <<<JS
<script type="text/javascript">
(function() {
    // ===== ECharts 实例 =====
    var chartTrend   = echarts.init(document.getElementById('chart-trend'));
    var chartFunnel  = echarts.init(document.getElementById('chart-funnel'));
    var chartVip     = echarts.init(document.getElementById('chart-vip'));
    var chartPaytype = echarts.init(document.getElementById('chart-paytype'));

    window.addEventListener('resize', function() {
        chartTrend.resize();
        chartFunnel.resize();
        chartVip.resize();
        chartPaytype.resize();
    });

    // ===== VIP 颜色映射 =====
    var vipColorMap = {
        '非会员':   '#909399',
        '普通会员': '#409eff',
        '高级会员': '#e6a23c',
        '铜牌会员': '#f56c6c'
    };
    var vipNames = {0: '非会员', 1: '普通会员', 2: '高级会员', 3: '铜牌会员'};

    // ===== 初始化趋势图 =====
    chartTrend.setOption({
        title: { text: '用户增长与付费趋势 ({$rangeLabel})', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis' },
        legend: { data: ['新增用户', '付费用户', '订单数', '收入(元)'], top: 5, right: 20 },
        grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
        toolbox: { feature: { saveAsImage: {} } },
        xAxis: { type: 'category', boundaryGap: false, data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: [
            { type: 'value', minInterval: 1, name: '人数/笔数', nameTextStyle: { fontSize: 11 } },
            { type: 'value', name: '元', nameTextStyle: { fontSize: 11 }, splitLine: { show: false }, axisLabel: { formatter: '{value}' } }
        ],
        series: [
            { name: '新增用户', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#409eff' }, lineStyle: { color: '#409eff', width: 2 }, areaStyle: { color: 'rgba(64,158,255,0.08)' } },
            { name: '付费用户', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#67c23a' }, lineStyle: { color: '#67c23a', width: 2 } },
            { name: '订单数', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#9b59b6' }, lineStyle: { color: '#9b59b6', width: 2, type: 'dashed' } },
            { name: '收入(元)', type: 'bar', yAxisIndex: 1, data: [], itemStyle: { color: 'rgba(252,132,82,0.6)' }, barMaxWidth: 30 }
        ]
    });

    // ===== 初始化漏斗图 =====
    chartFunnel.setOption({
        title: { text: '转化漏斗', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: function(p) { return p.name + ': ' + p.value + ' 人'; } },
        series: [{
            name: '转化', type: 'funnel', left: '10%', top: 40, bottom: 10, width: '80%',
            min: 0, max: 100, minSize: '0%', maxSize: '100%',
            sort: 'descending', gap: 2,
            label: { show: true, position: 'inside', formatter: function(p) { return p.name + '\\n' + p.value; }, fontSize: 12 },
            itemStyle: { borderColor: '#fff', borderWidth: 1 },
            emphasis: { label: { fontSize: 14 } },
            data: []
        }]
    });

    // ===== 初始化 VIP 分布饼图 =====
    chartVip.setOption({
        title: { text: 'VIP 等级分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{
            name: '用户数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false, position: 'center' },
            emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            labelLine: { show: false },
            data: []
        }]
    });

    // ===== 初始化支付方式饼图 =====
    chartPaytype.setOption({
        title: { text: '支付方式分布', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: function(p) { return p.name + ': ' + p.value + ' 笔 (' + p.percent + '%)'; } },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 } },
        series: [{
            name: '订单数', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false, position: 'center' },
            emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            labelLine: { show: false },
            data: []
        }]
    });

    // ===== 每日明细表格渲染 =====
    function renderDailyTable(rows) {
        var tbody = document.getElementById('daily-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>';
            return;
        }

        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var rateColor = r.rate >= 10 ? '#67c23a' : (r.rate >= 5 ? '#e6a23c' : '#f56c6c');
            if (r.rate === 0) rateColor = '#909399';

            var tr = '<tr style="background:' + bg + ';" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="font-weight:bold;">' + r.day + '</td>'
                + '<td>' + r.newUsers + '</td>'
                + '<td style="color:#67c23a;font-weight:bold;">' + r.payingUsers + '</td>'
                + '<td style="color:' + rateColor + ';font-weight:bold;">' + r.rate + '%</td>'
                + '<td style="color:#9b59b6;">' + r.orderCount + '</td>'
                + '<td style="color:#fc8452;font-weight:bold;">' + r.revenue + '</td>'
                + '<td style="color:#3498db;">' + r.avgOrder + '</td>'
                + '<td style="color:#ff6b6b;font-weight:bold;">' + (r.falMoney || 0) + '</td>'
                + '</tr>';
            tbody.innerHTML += tr;
        }
    }

    // ===== 底部表格渲染 =====
    function renderTopUsers(users) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();

        var hasCheckbox = $('#builder-table-head thead th').first().find('input[type=checkbox]').length > 0;
        var colCount = hasCheckbox ? 10 : 9;

        if (!users || users.length === 0) {
            tbody.append('<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }

        for (var i = 0; i < users.length; i++) {
            var u = users[i];
            var vipLabel = vipNames[u.vip_level] || u.vip_level;
            var vipColors = {0: '#909399', 1: '#409eff', 2: '#e6a23c', 3: '#f56c6c'};
            var vipColor = vipColors[u.vip_level] || '#909399';
            var checkboxTd = hasCheckbox ? '<td><div class="table-cell"><input type="checkbox" name="ids[]" value="' + u.user_id + '"></div></td>' : '';

            var row = '<tr>'
                + checkboxTd
                + '<td><div class="table-cell">' + u.rank + '</div></td>'
                + '<td><div class="table-cell">' + u.user_id + '</div></td>'
                + '<td><div class="table-cell">' + (u.user_name || '-') + '</div></td>'
                + '<td><div class="table-cell">' + (u.phone || '-') + '</div></td>'
                + '<td><div class="table-cell"><span style="color:' + vipColor + ';font-weight:bold">' + vipLabel + '</span></div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#409eff">' + u.order_count + '</span></div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#67c23a">&yen;' + u.total_pay_yuan + '</span></div></td>'
                + '<td><div class="table-cell">' + (u.last_pay_time || '-') + '</div></td>'
                + '<td><div class="table-cell">' + u.points_balance + '</div></td>'
                + '</tr>';
            tbody.append(row);
        }
    }

    // ===== 数据加载 =====
    function fetchData() {
        $('#uc-status-text').text('正在加载数据...');
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;
                var o = d.overview;

                // 更新概览卡片
                $('#card-total-users').text(o.totalUsers.toLocaleString());
                $('#card-new-users').text(o.newUsers.toLocaleString());
                $('#card-total-paying').text(o.totalPayingUsers.toLocaleString());
                $('#card-new-paying').text(o.newPayingUsers.toLocaleString());
                $('#card-conversion-rate').text(o.conversionRate + '%');
                $('#card-total-revenue').text(o.totalRevenueYuan.toLocaleString());
                $('#card-period-revenue').text(o.periodRevenueYuan.toLocaleString());
                $('#card-period-orders').text(o.periodOrderCount.toLocaleString());
                $('#card-arpu').text(o.arpu);
                $('#card-arppu').text(o.arppu);

                // 更新趋势图
                chartTrend.setOption({
                    xAxis: { data: d.trend.days },
                    series: [
                        { data: d.trend.newUsers },
                        { data: d.trend.payingUsers },
                        { data: d.trend.orders },
                        { data: d.trend.revenue }
                    ]
                });

                // 更新漏斗图
                var funnelMax = d.funnel.length > 0 ? d.funnel[0].value : 100;
                if (funnelMax === 0) funnelMax = 100;
                var funnelColors = ['#409eff', '#67c23a', '#e6a23c', '#f56c6c'];
                var funnelData = [];
                for (var i = 0; i < d.funnel.length; i++) {
                    funnelData.push({
                        name: d.funnel[i].name,
                        value: d.funnel[i].value,
                        itemStyle: { color: funnelColors[i] || '#909399' }
                    });
                }
                chartFunnel.setOption({
                    series: [{
                        max: funnelMax,
                        data: funnelData
                    }]
                });

                // 更新 VIP 饼图
                var vipPieData = [];
                for (var i = 0; i < d.vipDist.length; i++) {
                    var v = d.vipDist[i];
                    vipPieData.push({
                        name: v.name,
                        value: v.value,
                        itemStyle: { color: vipColorMap[v.name] || '#909399' }
                    });
                }
                chartVip.setOption({ series: [{ data: vipPieData }] });

                // 更新支付方式饼图
                var payTypeColors = {
                    '微信支付': '#07c160', '支付宝': '#1677ff', '算力激活': '#fc8452',
                    '月卡': '#e6a23c', '年卡': '#f56c6c', '积分': '#9b59b6', '未定义': '#909399'
                };
                var ptData = [];
                for (var i = 0; i < d.payTypeDist.length; i++) {
                    var pt = d.payTypeDist[i];
                    ptData.push({
                        name: pt.name,
                        value: pt.value,
                        itemStyle: { color: payTypeColors[pt.name] || '#909399' }
                    });
                }
                chartPaytype.setOption({ series: [{ data: ptData }] });

                // 更新每日明细
                renderDailyTable(d.dailyTable);

                // 更新底部排行表
                renderTopUsers(d.topUsers);

                // 更新状态栏
                $('#uc-last-update').text('最后更新: ' + res.time);
                $('#uc-status-text').text('数据已加载');
            },
            error: function() {
                $('#uc-status-text').text('数据请求失败');
            }
        });
    }

    // 暴露刷新函数
    window.ucRefresh = function() {
        fetchData();
    };

    // 首次加载
    fetchData();
})();
</script>
JS;
    }
}
