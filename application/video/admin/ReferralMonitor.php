<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class ReferralMonitor extends Admin {

    public function index()
    {
        $range = input('param.range', 'today', 'trim');
        if (!in_array($range, ['today', '7', '30', '90', 'custom'])) {
            $range = 'today';
        }
        $customStart = input('param.start', '', 'trim');
        $customEnd   = input('param.end', '', 'trim');
        $promoterId  = input('param.promoter_id', 0, 'intval');

        list($startDate, $endDate, $rangeLabel) = $this->calcDateRange($range, $customStart, $customEnd);
        $dataList = $this->getRewardOrders($startDate, $endDate, $promoterId);

        $contentHtml = $this->buildTimeRangeHtml($range, $customStart, $customEnd, $promoterId)
                     . $this->buildDashboardHtml();
        $js = $this->buildDashboardJs($range, $customStart, $customEnd, $rangeLabel, $promoterId);

        return ZBuilder::make('table')
            ->setPageTitle('推广数据监控')
            ->setPageTips("时间范围：{$rangeLabel}，展示推广员流量、转化与佣金数据", 'info')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($contentHtml, 'toolbar_top')
            ->setHeight('auto')
            ->hideCheckbox()
            ->addColumns([
                ['id', 'ID'],
                ['payer_user_id', '付费用户ID'],
                ['payer_name', '付费用户名'],
                ['promoter_user_id', '推广员ID'],
                ['ref_code', '推广码'],
                ['order_amount_yuan', '订单金额(元)', 'callback', function($value) {
                    return "<span style='font-weight:bold;color:#67c23a'>¥{$value}</span>";
                }],
                ['commission_rate', '佣金比例', 'callback', function($value) {
                    return "<span style='color:#409eff'>{$value}%</span>";
                }],
                ['reward_points', '佣金积分', 'callback', function($value) {
                    return "<span style='font-weight:bold;color:#fc8452'>{$value}</span>";
                }],
                ['status', '状态', 'callback', function($value) {
                    $map = [1 => ['已发放', '#67c23a'], 2 => ['已冲正', '#f56c6c']];
                    $info = $map[$value] ?? ['未知', '#909399'];
                    return "<span style='color:{$info[1]};font-weight:bold'>{$info[0]}</span>";
                }],
                ['rewarded_at', '发放时间'],
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
        $range = input('param.range', 'today', 'trim');
        if (!in_array($range, ['today', '7', '30', '90', 'custom'])) {
            $range = 'today';
        }
        $customStart = input('param.start', '', 'trim');
        $customEnd   = input('param.end', '', 'trim');
        $promoterId  = input('param.promoter_id', 0, 'intval');

        list($startDate, $endDate, $rangeLabel) = $this->calcDateRange($range, $customStart, $customEnd);

        $db = Db::connect('translate');

        $refCodes = [];
        if ($promoterId > 0) {
            $rcRows = $db->query("SELECT `code` FROM `ts_referral_code` WHERE `promoter_user_id` = {$promoterId}");
            $refCodes = array_column($rcRows, 'code');
        }

        // ========== 1. 概览指标 ==========

        $pcRows = $db->query("SELECT COUNT(*) as cnt FROM `ts_referral_promoter` WHERE `status` = 1");
        $promoterCount = intval($pcRows[0]['cnt'] ?? 0);

        $ccSQL = "SELECT COUNT(*) as cnt FROM `ts_referral_code` WHERE `status` = 1";
        if ($promoterId > 0) $ccSQL .= " AND `promoter_user_id` = {$promoterId}";
        $ccRows = $db->query($ccSQL);
        $codeCount = intval($ccRows[0]['cnt'] ?? 0);

        $visitCount = 0;
        if (!($promoterId > 0 && empty($refCodes))) {
            $vcSQL = "SELECT COUNT(*) as cnt FROM `ts_referral_visit_event` WHERE `stat_date` >= '{$startDate}' AND `stat_date` < '{$endDate}'";
            if ($promoterId > 0) {
                $codeListStr = implode("','", array_map('addslashes', $refCodes));
                $vcSQL .= " AND `ref_code` IN ('{$codeListStr}')";
            }
            $vcRows = $db->query($vcSQL);
            $visitCount = intval($vcRows[0]['cnt'] ?? 0);
        }

        $rcSQL = "SELECT COUNT(*) as cnt FROM `ts_referral_relation` WHERE `status` = 1 AND `bound_at` >= '{$startDate}' AND `bound_at` < '{$endDate}'";
        if ($promoterId > 0) $rcSQL .= " AND `promoter_user_id` = {$promoterId}";
        $rcRows2 = $db->query($rcSQL);
        $registerCount = intval($rcRows2[0]['cnt'] ?? 0);

        $paySQL = "SELECT COUNT(DISTINCT o.user_id) as pay_users,
                          IFNULL(SUM(o.money), 0) as total_money,
                          COUNT(*) as order_count
                   FROM ts_pay_order_info o
                   INNER JOIN ts_referral_relation r ON o.user_id = r.user_id AND r.status = 1
                   WHERE o.status = 2
                     AND o.time >= '{$startDate}' AND o.time < '{$endDate}'";
        if ($promoterId > 0) $paySQL .= " AND r.promoter_user_id = {$promoterId}";
        $payStats = $db->query($paySQL);
        $payUserCount  = intval($payStats[0]['pay_users'] ?? 0);
        $totalPayMoney = intval($payStats[0]['total_money'] ?? 0);
        $orderCount    = intval($payStats[0]['order_count'] ?? 0);
        $totalPayYuan  = round($totalPayMoney / 100, 2);

        $commSQL = "SELECT IFNULL(SUM(`reward_points`), 0) as total FROM `ts_referral_reward` WHERE `status` = 1 AND `rewarded_at` >= '{$startDate}' AND `rewarded_at` < '{$endDate}'";
        if ($promoterId > 0) $commSQL .= " AND `promoter_user_id` = {$promoterId}";
        $commRes = $db->query($commSQL);
        $totalCommission = intval($commRes[0]['total'] ?? 0);

        $nrSQL = "SELECT IFNULL(SUM(`reward_points`), 0) as total FROM `ts_referral_new_user_reward` WHERE `status` = 1 AND `rewarded_at` >= '{$startDate}' AND `rewarded_at` < '{$endDate}'";
        if ($promoterId > 0) $nrSQL .= " AND `promoter_user_id` = {$promoterId}";
        $nrRes = $db->query($nrSQL);
        $totalNewUserReward = intval($nrRes[0]['total'] ?? 0);

        $visitToRegRate = $visitCount > 0 ? round($registerCount / $visitCount * 100, 1) : 0;
        $regToPayRate   = $registerCount > 0 ? round($payUserCount / $registerCount * 100, 1) : 0;
        $arpu           = $payUserCount > 0 ? round($totalPayYuan / $payUserCount, 2) : 0;

        // ========== 2. 趋势数据（按天） ==========

        $visitTrendMap = [];
        if (!($promoterId > 0 && empty($refCodes))) {
            $vtSQL = "SELECT `stat_date` as day, COUNT(*) as cnt FROM `ts_referral_visit_event` WHERE `stat_date` >= '{$startDate}' AND `stat_date` < '{$endDate}'";
            if ($promoterId > 0) {
                $codeListStr = implode("','", array_map('addslashes', $refCodes));
                $vtSQL .= " AND `ref_code` IN ('{$codeListStr}')";
            }
            $vtSQL .= " GROUP BY `stat_date` ORDER BY `stat_date` ASC";
            foreach ($db->query($vtSQL) as $row) {
                $visitTrendMap[$row['day']] = intval($row['cnt']);
            }
        }

        $rtSQL = "SELECT DATE(`bound_at`) as day, COUNT(*) as cnt FROM `ts_referral_relation` WHERE `status` = 1 AND `bound_at` >= '{$startDate}' AND `bound_at` < '{$endDate}'";
        if ($promoterId > 0) $rtSQL .= " AND `promoter_user_id` = {$promoterId}";
        $rtSQL .= " GROUP BY day ORDER BY day ASC";
        $regTrendMap = [];
        foreach ($db->query($rtSQL) as $row) {
            $regTrendMap[$row['day']] = intval($row['cnt']);
        }

        $ptSQL = "SELECT DATE(o.time) as day,
                         COUNT(DISTINCT o.user_id) as pay_users,
                         IFNULL(SUM(o.money), 0) as revenue
                  FROM ts_pay_order_info o
                  INNER JOIN ts_referral_relation r ON o.user_id = r.user_id AND r.status = 1
                  WHERE o.status = 2
                    AND o.time >= '{$startDate}' AND o.time < '{$endDate}'";
        if ($promoterId > 0) $ptSQL .= " AND r.promoter_user_id = {$promoterId}";
        $ptSQL .= " GROUP BY day ORDER BY day ASC";
        $payTrendMap = [];
        foreach ($db->query($ptSQL) as $row) {
            $payTrendMap[$row['day']] = [
                'pay_users' => intval($row['pay_users']),
                'revenue'   => intval($row['revenue']),
            ];
        }

        $allDays = array_unique(array_merge(
            array_keys($visitTrendMap), array_keys($regTrendMap), array_keys($payTrendMap)
        ));
        sort($allDays);

        $trendDays = $trendVisits = $trendRegisters = $trendPayUsers = $trendRevenue = [];
        foreach ($allDays as $day) {
            $trendDays[]      = $day;
            $trendVisits[]    = $visitTrendMap[$day] ?? 0;
            $trendRegisters[] = $regTrendMap[$day] ?? 0;
            $trendPayUsers[]  = isset($payTrendMap[$day]) ? $payTrendMap[$day]['pay_users'] : 0;
            $trendRevenue[]   = isset($payTrendMap[$day]) ? round($payTrendMap[$day]['revenue'] / 100, 2) : 0;
        }

        // ========== 3. 转化漏斗 ==========
        $funnel = [
            ['name' => '链接访问', 'value' => $visitCount],
            ['name' => '注册用户', 'value' => $registerCount],
            ['name' => '充值用户', 'value' => $payUserCount],
        ];

        // ========== 4. 推广员贡献分布 Top 10 ==========
        $pdSQL = "SELECT r.promoter_user_id,
                         IFNULL(SUM(o.money), 0) as total_money
                  FROM ts_pay_order_info o
                  INNER JOIN ts_referral_relation r ON o.user_id = r.user_id AND r.status = 1
                  WHERE o.status = 2
                    AND o.time >= '{$startDate}' AND o.time < '{$endDate}'
                  GROUP BY r.promoter_user_id
                  ORDER BY total_money DESC LIMIT 10";
        $pdRows = $db->query($pdSQL);
        $pdUserIds = array_column($pdRows, 'promoter_user_id');
        $pdNames = [];
        if (!empty($pdUserIds)) {
            $pdIdStr = implode(',', array_map('intval', $pdUserIds));
            $pdNameRows = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` IN ({$pdIdStr})");
            $pdNames = array_column($pdNameRows, 'name', 'id');
        }
        $promoterDistData = [];
        foreach ($pdRows as $item) {
            $uid = $item['promoter_user_id'];
            $label = ($pdNames[$uid] ?? '') ?: ('#' . $uid);
            $promoterDistData[] = [
                'name'  => $label,
                'value' => round(intval($item['total_money']) / 100, 2),
            ];
        }

        // ========== 5. 推广员排行表（批量查询） ==========
        $promoters = $db->query("SELECT `user_id`, `commission_rate`, `enabled_at` FROM `ts_referral_promoter` WHERE `status` = 1");
        $promoterIds = array_column($promoters, 'user_id');

        $promoterTable = [];
        if (!empty($promoterIds)) {
            $pIdStr = implode(',', array_map('intval', $promoterIds));
            $pNameRows = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` IN ({$pIdStr})");
            $pNames = array_column($pNameRows, 'name', 'id');

            $allCodesRows = $db->query("SELECT `promoter_user_id`, `code` FROM `ts_referral_code` WHERE `status` = 1 AND `promoter_user_id` IN ({$pIdStr})");
            $codeMap = [];
            $codeToPromoter = [];
            $allCodesList = [];
            foreach ($allCodesRows as $c) {
                $codeMap[$c['promoter_user_id']][] = $c['code'];
                $codeToPromoter[$c['code']] = $c['promoter_user_id'];
                $allCodesList[] = $c['code'];
            }

            $visitsByP = [];
            if (!empty($allCodesList)) {
                $allCodesStr = implode("','", array_map('addslashes', $allCodesList));
                $vRows = $db->query("SELECT `ref_code`, COUNT(*) as cnt FROM `ts_referral_visit_event` WHERE `ref_code` IN ('{$allCodesStr}') AND `stat_date` >= '{$startDate}' AND `stat_date` < '{$endDate}' GROUP BY `ref_code`");
                foreach ($vRows as $row) {
                    $puid = $codeToPromoter[$row['ref_code']] ?? null;
                    if ($puid) $visitsByP[$puid] = ($visitsByP[$puid] ?? 0) + intval($row['cnt']);
                }
            }

            $regsByP = [];
            $regRows = $db->query("SELECT `promoter_user_id`, COUNT(*) as cnt FROM `ts_referral_relation` WHERE `promoter_user_id` IN ({$pIdStr}) AND `status` = 1 AND `bound_at` >= '{$startDate}' AND `bound_at` < '{$endDate}' GROUP BY `promoter_user_id`");
            foreach ($regRows as $row) {
                $regsByP[$row['promoter_user_id']] = intval($row['cnt']);
            }

            $idList = implode(',', $promoterIds);
            $ppSQL = "SELECT r.promoter_user_id,
                             COUNT(DISTINCT o.user_id) as pay_users,
                             IFNULL(SUM(o.money), 0) as total_money
                      FROM ts_pay_order_info o
                      INNER JOIN ts_referral_relation r ON o.user_id = r.user_id AND r.status = 1
                      WHERE o.status = 2
                        AND o.time >= '{$startDate}' AND o.time < '{$endDate}'
                        AND r.promoter_user_id IN ({$idList})
                      GROUP BY r.promoter_user_id";
            $payByP = [];
            foreach ($db->query($ppSQL) as $row) {
                $payByP[$row['promoter_user_id']] = [
                    'pay_users'   => intval($row['pay_users']),
                    'total_money' => intval($row['total_money']),
                ];
            }

            $commByP = [];
            $commRows = $db->query("SELECT `promoter_user_id`, SUM(`reward_points`) as total_points FROM `ts_referral_reward` WHERE `promoter_user_id` IN ({$pIdStr}) AND `status` = 1 AND `rewarded_at` >= '{$startDate}' AND `rewarded_at` < '{$endDate}' GROUP BY `promoter_user_id`");
            foreach ($commRows as $row) {
                $commByP[$row['promoter_user_id']] = intval($row['total_points']);
            }

            foreach ($promoters as $p) {
                $uid    = $p['user_id'];
                $vis    = $visitsByP[$uid] ?? 0;
                $regs   = $regsByP[$uid] ?? 0;
                $pUsers = isset($payByP[$uid]) ? $payByP[$uid]['pay_users'] : 0;
                $pAmt   = isset($payByP[$uid]) ? round($payByP[$uid]['total_money'] / 100, 2) : 0;
                $comm   = $commByP[$uid] ?? 0;

                $promoterTable[] = [
                    'user_id'        => $uid,
                    'name'           => $pNames[$uid] ?? '-',
                    'codes'          => implode(', ', $codeMap[$uid] ?? []),
                    'commission_rate'=> $p['commission_rate'],
                    'enabled_at'     => $p['enabled_at'],
                    'visits'         => $vis,
                    'registers'      => $regs,
                    'payUsers'       => $pUsers,
                    'payAmount'      => $pAmt,
                    'commission'     => $comm,
                    'visitToRegRate' => $vis > 0 ? round($regs / $vis * 100, 1) : 0,
                    'regToPayRate'   => $regs > 0 ? round($pUsers / $regs * 100, 1) : 0,
                ];
            }
            usort($promoterTable, function($a, $b) { return $b['payAmount'] <=> $a['payAmount']; });
        }

        // ========== 6. 每日数据明细 ==========
        $dailyTable = [];
        foreach ($allDays as $day) {
            $dv  = $visitTrendMap[$day] ?? 0;
            $dr  = $regTrendMap[$day] ?? 0;
            $dp  = isset($payTrendMap[$day]) ? $payTrendMap[$day]['pay_users'] : 0;
            $drev = isset($payTrendMap[$day]) ? round($payTrendMap[$day]['revenue'] / 100, 2) : 0;

            $dailyTable[] = [
                'day'            => $day,
                'visits'         => $dv,
                'registers'      => $dr,
                'visitToRegRate' => $dv > 0 ? round($dr / $dv * 100, 1) : 0,
                'payUsers'       => $dp,
                'revenue'        => $drev,
                'regToPayRate'   => $dr > 0 ? round($dp / $dr * 100, 1) : 0,
            ];
        }
        $dailyTable = array_reverse($dailyTable);

        // ========== 7. 佣金订单列表 ==========
        $rewardOrders = $this->getRewardOrdersRaw($startDate, $endDate, $promoterId);

        return json([
            'code' => 0,
            'data' => [
                'overview' => [
                    'promoterCount'    => $promoterCount,
                    'codeCount'        => $codeCount,
                    'visitCount'       => $visitCount,
                    'registerCount'    => $registerCount,
                    'payUserCount'     => $payUserCount,
                    'totalPayYuan'     => $totalPayYuan,
                    'orderCount'       => $orderCount,
                    'totalCommission'  => $totalCommission,
                    'totalNewUserReward' => $totalNewUserReward,
                    'visitToRegRate'   => $visitToRegRate,
                    'regToPayRate'     => $regToPayRate,
                    'arpu'             => $arpu,
                ],
                'trend' => [
                    'days'      => $trendDays,
                    'visits'    => $trendVisits,
                    'registers' => $trendRegisters,
                    'payUsers'  => $trendPayUsers,
                    'revenue'   => $trendRevenue,
                ],
                'funnel'       => $funnel,
                'promoterDist' => $promoterDistData,
                'promoterTable'=> $promoterTable,
                'dailyTable'   => $dailyTable,
                'rewardOrders' => $rewardOrders,
            ],
            'range' => $range,
            'time'  => date('Y-m-d H:i:s'),
        ]);
    }

    // ==================== 辅助方法 ====================

    private function calcDateRange($range, $customStart, $customEnd)
    {
        switch ($range) {
            case 'today':
                return [date('Y-m-d'), date('Y-m-d', strtotime('+1 day')), '今日'];
            case '7':
                return [date('Y-m-d', strtotime('-6 days')), date('Y-m-d', strtotime('+1 day')), '近7天'];
            case '90':
                return [date('Y-m-d', strtotime('-89 days')), date('Y-m-d', strtotime('+1 day')), '近90天'];
            case 'custom':
                $s = $customStart ?: date('Y-m-d', strtotime('-29 days'));
                $e = $customEnd ? date('Y-m-d', strtotime($customEnd . ' +1 day')) : date('Y-m-d', strtotime('+1 day'));
                return [$s, $e, "{$s} ~ " . date('Y-m-d', strtotime($e . ' -1 day'))];
            default:
                return [date('Y-m-d', strtotime('-29 days')), date('Y-m-d', strtotime('+1 day')), '近30天'];
        }
    }

    private function getPromoterList()
    {
        $db = Db::connect('translate');
        $promoters = $db->query("SELECT `user_id`, `commission_rate` FROM `ts_referral_promoter` WHERE `status` = 1 ORDER BY `enabled_at` DESC");
        if (empty($promoters)) return [];

        $ids = array_column($promoters, 'user_id');
        $idStr = implode(',', array_map('intval', $ids));
        $nameRows = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` IN ({$idStr})");
        $names = array_column($nameRows, 'name', 'id');

        $codes = $db->query("SELECT `promoter_user_id`, `code` FROM `ts_referral_code` WHERE `status` = 1 AND `promoter_user_id` IN ({$idStr})");
        $codeMap = [];
        foreach ($codes as $c) {
            $codeMap[$c['promoter_user_id']][] = $c['code'];
        }

        $list = [];
        foreach ($promoters as $p) {
            $uid = $p['user_id'];
            $codeStr = implode(', ', $codeMap[$uid] ?? []);
            $name = $names[$uid] ?? '';
            $label = "#{$uid}";
            if ($name) $label .= " {$name}";
            if ($codeStr) $label .= " [{$codeStr}]";
            $list[] = ['user_id' => $uid, 'label' => $label];
        }
        return $list;
    }

    private function getRewardOrders($startDate, $endDate, $promoterId)
    {
        $db = Db::connect('translate');
        $rwSQL = "SELECT * FROM `ts_referral_reward` WHERE `rewarded_at` >= '{$startDate}' AND `rewarded_at` < '{$endDate}'";
        if ($promoterId > 0) $rwSQL .= " AND `promoter_user_id` = {$promoterId}";
        $rwSQL .= " ORDER BY `rewarded_at` DESC LIMIT 200";
        $rows = $db->query($rwSQL);
        if (empty($rows)) return [];

        $payerIds = array_unique(array_filter(array_column($rows, 'payer_user_id')));
        $users = [];
        if (!empty($payerIds)) {
            $idStr = implode(',', array_map('intval', $payerIds));
            $userRows = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` IN ({$idStr})");
            $users = array_column($userRows, 'name', 'id');
        }

        $dataList = [];
        foreach ($rows as $row) {
            $dataList[] = [
                'id'                => $row['id'],
                'payer_user_id'     => $row['payer_user_id'],
                'payer_name'        => $users[$row['payer_user_id']] ?? '-',
                'promoter_user_id'  => $row['promoter_user_id'],
                'ref_code'          => $row['ref_code'],
                'order_amount_yuan' => round(intval($row['order_amount']) / 100, 2),
                'commission_rate'   => $row['commission_rate'],
                'reward_points'     => $row['reward_points'],
                'status'            => $row['status'],
                'rewarded_at'       => $row['rewarded_at'],
            ];
        }
        return $dataList;
    }

    private function getRewardOrdersRaw($startDate, $endDate, $promoterId)
    {
        return array_map(function($item) {
            return [
                'id'                => $item['id'],
                'payer_user_id'     => $item['payer_user_id'],
                'payer_name'        => $item['payer_name'],
                'promoter_user_id'  => $item['promoter_user_id'],
                'ref_code'          => $item['ref_code'],
                'order_amount_yuan' => $item['order_amount_yuan'],
                'commission_rate'   => $item['commission_rate'],
                'reward_points'     => $item['reward_points'],
                'status'            => $item['status'],
                'rewarded_at'       => $item['rewarded_at'],
            ];
        }, $this->getRewardOrders($startDate, $endDate, $promoterId));
    }

    // ==================== 时间范围 + 推广员筛选 HTML ====================

    private function buildTimeRangeHtml($currentRange, $customStart, $customEnd, $promoterId)
    {
        $options = ['today' => '今日', '7' => '近7天', '30' => '近30天', '90' => '近90天'];
        $promoters = $this->getPromoterList();

        $html = '<div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">';
        $html .= '<span style="font-weight:bold;font-size:13px;">时间范围：</span>';

        foreach ($options as $key => $label) {
            $active = ($key == $currentRange);
            $style = $active
                ? 'background:#409eff;color:#fff;border-color:#409eff;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['range' => $key, 'promoter_id' => $promoterId]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 15px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$style}'>{$label}</a>";
        }

        $startVal = $customStart ?: date('Y-m-d', strtotime('-29 days'));
        $endVal   = $customEnd ?: date('Y-m-d');
        $customActive = ($currentRange === 'custom');
        $customStyle = $customActive
            ? 'background:#409eff;color:#fff;border-color:#409eff;'
            : 'background:#fff;color:#606266;border-color:#dcdfe6;';

        $html .= '<span style="color:#909399;">|</span>';
        $html .= '<input type="date" id="ref-custom-start" value="' . $startVal . '" style="padding:4px 8px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;"/>';
        $html .= '<span style="color:#909399;font-size:13px;">~</span>';
        $html .= '<input type="date" id="ref-custom-end" value="' . $endVal . '" style="padding:4px 8px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;"/>';

        $baseUrl = url('index');
        $html .= "<a href='javascript:void(0)' onclick=\"window.location.href='{$baseUrl}?range=custom&promoter_id={$promoterId}&start='+document.getElementById('ref-custom-start').value+'&end='+document.getElementById('ref-custom-end').value\" style='display:inline-block;padding:5px 15px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$customStyle}'>查询</a>";

        // 推广员下拉筛选
        $html .= '<span style="color:#909399;margin-left:8px;">|</span>';
        $html .= '<span style="font-weight:bold;font-size:13px;margin-left:8px;">推广员：</span>';
        $html .= '<select id="ref-promoter-select" onchange="window.location.href=\'' . $baseUrl . '?range=' . $currentRange;
        if ($currentRange === 'custom') {
            $html .= '&start=' . $startVal . '&end=' . $endVal;
        }
        $html .= '&promoter_id=\'+this.value" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;min-width:160px;">';
        $html .= '<option value="0"' . ($promoterId == 0 ? ' selected' : '') . '>全部推广员</option>';
        foreach ($promoters as $p) {
            $sel = ($promoterId == $p['user_id']) ? ' selected' : '';
            $html .= '<option value="' . $p['user_id'] . '"' . $sel . '>' . htmlspecialchars($p['label']) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        return $html;
    }

    // ==================== 仪表盘 HTML ====================

    private function buildDashboardHtml()
    {
        return <<<'HTML'
<style>
.ref-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
.ref-cards { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.ref-card {
    flex: 1; min-width: 130px; padding: 16px 18px; border-radius: 8px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.08); text-align: center;
    border-top: 3px solid #409eff; transition: transform .2s;
}
.ref-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ref-card .cv { font-size: 26px; font-weight: 700; line-height: 1.2; }
.ref-card .cl { font-size: 12px; color: #909399; margin-top: 4px; }
.ref-charts-row { display: flex; gap: 16px; margin-bottom: 16px; }
.ref-charts-row .chart-box { flex: 1; min-width: 0; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ref-trend-row { margin-bottom: 16px; background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 8px; }
.ref-tbl-wrap {
    margin-bottom: 16px; background: #fff; border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 16px;
}
.ref-tbl-wrap table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ref-tbl-wrap th {
    padding: 10px 8px; text-align: center; border-bottom: 2px solid #ebeef5;
    color: #606266; font-weight: 600; background: #f5f7fa;
}
.ref-tbl-wrap td { padding: 9px 8px; text-align: center; border-bottom: 1px solid #ebeef5; }
.ref-status-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; background: #f5f7fa; border-radius: 6px; margin-bottom: 16px;
    font-size: 13px; color: #606266;
}
.ref-btn {
    padding: 5px 14px; border: 1px solid #dcdfe6; border-radius: 4px;
    background: #fff; color: #606266; cursor: pointer; font-size: 13px; transition: all .2s;
}
.ref-btn:hover { border-color: #409eff; color: #409eff; }
</style>

<div class="ref-wrap">
    <!-- 流量指标卡片 -->
    <div class="ref-cards">
        <div class="ref-card" style="border-top-color:#9b59b6;">
            <div class="cv" style="color:#9b59b6;" id="ref-card-promoters">-</div>
            <div class="cl">推广员总数</div>
        </div>
        <div class="ref-card" style="border-top-color:#3498db;">
            <div class="cv" style="color:#3498db;" id="ref-card-codes">-</div>
            <div class="cl">推广码总数</div>
        </div>
        <div class="ref-card" style="border-top-color:#409eff;">
            <div class="cv" style="color:#409eff;" id="ref-card-visits">-</div>
            <div class="cl">链接访问次数</div>
        </div>
        <div class="ref-card" style="border-top-color:#1abc9c;">
            <div class="cv" style="color:#1abc9c;" id="ref-card-registers">-</div>
            <div class="cl">注册用户数</div>
        </div>
        <div class="ref-card" style="border-top-color:#67c23a;">
            <div class="cv" style="color:#67c23a;" id="ref-card-payusers">-</div>
            <div class="cl">充值用户数</div>
        </div>
    </div>

    <!-- 收入指标卡片 -->
    <div class="ref-cards">
        <div class="ref-card" style="border-top-color:#fc8452;">
            <div class="cv" style="color:#fc8452;" id="ref-card-payamount">-</div>
            <div class="cl">充值总金额(元)</div>
        </div>
        <div class="ref-card" style="border-top-color:#9b59b6;">
            <div class="cv" style="color:#9b59b6;" id="ref-card-orders">-</div>
            <div class="cl">充值订单数</div>
        </div>
        <div class="ref-card" style="border-top-color:#e6a23c;">
            <div class="cv" style="color:#e6a23c;" id="ref-card-commission">-</div>
            <div class="cl">佣金积分总计</div>
        </div>
        <div class="ref-card" style="border-top-color:#67c23a;">
            <div class="cv" style="color:#67c23a;" id="ref-card-newreward">-</div>
            <div class="cl">新客奖励积分</div>
        </div>
        <div class="ref-card" style="border-top-color:#f56c6c;">
            <div class="cv" style="color:#f56c6c;" id="ref-card-v2r">-</div>
            <div class="cl">访问→注册率</div>
        </div>
        <div class="ref-card" style="border-top-color:#e6a23c;">
            <div class="cv" style="color:#e6a23c;" id="ref-card-r2p">-</div>
            <div class="cl">注册→付费率</div>
        </div>
        <div class="ref-card" style="border-top-color:#1abc9c;">
            <div class="cv" style="color:#1abc9c;" id="ref-card-arpu">-</div>
            <div class="cl">付费用户 ARPU</div>
        </div>
    </div>

    <!-- 趋势折线图 -->
    <div class="ref-trend-row">
        <div id="ref-chart-trend" style="width:100%;height:380px;"></div>
    </div>

    <!-- 漏斗图 + 推广员贡献饼图 -->
    <div class="ref-charts-row">
        <div class="chart-box">
            <div id="ref-chart-funnel" style="width:100%;height:340px;"></div>
        </div>
        <div class="chart-box">
            <div id="ref-chart-promoter" style="width:100%;height:340px;"></div>
        </div>
    </div>

    <!-- 推广员排行表 -->
    <div class="ref-tbl-wrap">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">推广员排行</div>
        <table>
            <thead>
                <tr>
                    <th>推广员ID</th>
                    <th>名称</th>
                    <th>推广码</th>
                    <th>佣金比例</th>
                    <th>开通时间</th>
                    <th style="color:#409eff;">访问量</th>
                    <th style="color:#1abc9c;">注册数</th>
                    <th style="color:#67c23a;">充值人数</th>
                    <th style="color:#fc8452;">充值金额(元)</th>
                    <th style="color:#e6a23c;">佣金积分</th>
                    <th style="color:#f56c6c;">访问→注册率</th>
                    <th style="color:#9b59b6;">注册→付费率</th>
                </tr>
            </thead>
            <tbody id="ref-promoter-body">
                <tr><td colspan="12" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 每日数据明细 -->
    <div class="ref-tbl-wrap">
        <div style="font-size:14px;font-weight:bold;color:#303133;margin-bottom:12px;">每日数据明细</div>
        <table>
            <thead>
                <tr>
                    <th>日期</th>
                    <th style="color:#409eff;">链接访问</th>
                    <th style="color:#1abc9c;">新注册</th>
                    <th style="color:#e6a23c;">注册转化率</th>
                    <th style="color:#67c23a;">充值人数</th>
                    <th style="color:#fc8452;">充值金额(元)</th>
                    <th style="color:#f56c6c;">充值转化率</th>
                </tr>
            </thead>
            <tbody id="ref-daily-body">
                <tr><td colspan="7" style="text-align:center;padding:20px;color:#909399;">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 状态栏 -->
    <div class="ref-status-bar">
        <div>
            <span id="ref-status-text">正在加载数据...</span>
            <span style="color:#909399;margin-left:12px;" id="ref-last-update"></span>
        </div>
        <div>
            <button class="ref-btn" onclick="refRefresh()">刷新数据</button>
        </div>
    </div>
</div>
HTML;
    }

    // ==================== 仪表盘 JS ====================

    private function buildDashboardJs($range, $customStart, $customEnd, $rangeLabel, $promoterId)
    {
        $params = ['range' => $range, 'promoter_id' => $promoterId];
        if ($range === 'custom') {
            $params['start'] = $customStart;
            $params['end']   = $customEnd;
        }
        $ajaxUrl = url('ajaxData', $params);

        return <<<JS
<script type="text/javascript">
(function() {
    var chartTrend    = echarts.init(document.getElementById('ref-chart-trend'));
    var chartFunnel   = echarts.init(document.getElementById('ref-chart-funnel'));
    var chartPromoter = echarts.init(document.getElementById('ref-chart-promoter'));

    window.addEventListener('resize', function() {
        chartTrend.resize(); chartFunnel.resize(); chartPromoter.resize();
    });

    chartTrend.setOption({
        title: { text: '推广数据趋势 ({$rangeLabel})', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'axis' },
        legend: { data: ['访问量', '注册数', '充值人数', '充值金额(元)'], top: 5, right: 20 },
        grid: { left: '3%', right: '5%', bottom: '3%', top: 50, containLabel: true },
        toolbox: { feature: { saveAsImage: {} } },
        xAxis: { type: 'category', boundaryGap: false, data: [], axisLabel: { fontSize: 11, rotate: 45 } },
        yAxis: [
            { type: 'value', minInterval: 1, name: '人次', nameTextStyle: { fontSize: 11 } },
            { type: 'value', name: '元', nameTextStyle: { fontSize: 11 }, splitLine: { show: false }, axisLabel: { formatter: '{value}' } }
        ],
        series: [
            { name: '访问量', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#409eff' }, lineStyle: { color: '#409eff', width: 2 }, areaStyle: { color: 'rgba(64,158,255,0.08)' } },
            { name: '注册数', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#1abc9c' }, lineStyle: { color: '#1abc9c', width: 2 } },
            { name: '充值人数', type: 'line', smooth: true, yAxisIndex: 0, data: [], itemStyle: { color: '#67c23a' }, lineStyle: { color: '#67c23a', width: 2 } },
            { name: '充值金额(元)', type: 'bar', yAxisIndex: 1, data: [], itemStyle: { color: 'rgba(252,132,82,0.6)' }, barMaxWidth: 30 }
        ]
    });

    chartFunnel.setOption({
        title: { text: '转化漏斗', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: function(p) { return p.name + ': ' + p.value + ' 人次'; } },
        series: [{
            name: '转化', type: 'funnel', left: '10%', top: 40, bottom: 10, width: '80%',
            min: 0, max: 100, minSize: '0%', maxSize: '100%',
            sort: 'descending', gap: 2,
            label: { show: true, position: 'inside', formatter: function(p) { return p.name + '\\n' + p.value; }, fontSize: 12 },
            itemStyle: { borderColor: '#fff', borderWidth: 1 },
            data: []
        }]
    });

    chartPromoter.setOption({
        title: { text: '推广员贡献 Top 10 (充值金额)', left: 'center', textStyle: { fontSize: 14, color: '#303133' } },
        tooltip: { trigger: 'item', formatter: '{b}: ¥{c} ({d}%)' },
        legend: { orient: 'vertical', left: 10, top: 30, textStyle: { fontSize: 11 }, type: 'scroll' },
        series: [{
            name: '充值金额', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
            avoidLabelOverlap: false,
            itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
            label: { show: false, position: 'center' },
            emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
            labelLine: { show: false },
            data: []
        }]
    });

    function renderPromoterTable(rows) {
        var tbody = document.getElementById('ref-promoter-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>';
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var v2rColor = r.visitToRegRate >= 10 ? '#67c23a' : (r.visitToRegRate >= 5 ? '#e6a23c' : '#f56c6c');
            var r2pColor = r.regToPayRate >= 5 ? '#67c23a' : (r.regToPayRate >= 2 ? '#e6a23c' : '#f56c6c');
            if (r.visitToRegRate === 0) v2rColor = '#909399';
            if (r.regToPayRate === 0) r2pColor = '#909399';

            tbody.innerHTML += '<tr style="background:' + bg + ';" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="font-weight:bold;">' + r.user_id + '</td>'
                + '<td>' + (r.name || '-') + '</td>'
                + '<td style="color:#409eff;font-size:12px;max-width:150px;word-break:break-all;">' + (r.codes || '-') + '</td>'
                + '<td>' + r.commission_rate + '%</td>'
                + '<td style="font-size:12px;">' + (r.enabled_at || '-') + '</td>'
                + '<td style="color:#409eff;font-weight:bold;">' + r.visits + '</td>'
                + '<td style="color:#1abc9c;font-weight:bold;">' + r.registers + '</td>'
                + '<td style="color:#67c23a;font-weight:bold;">' + r.payUsers + '</td>'
                + '<td style="color:#fc8452;font-weight:bold;">¥' + r.payAmount + '</td>'
                + '<td style="color:#e6a23c;font-weight:bold;">' + r.commission + '</td>'
                + '<td style="color:' + v2rColor + ';font-weight:bold;">' + r.visitToRegRate + '%</td>'
                + '<td style="color:' + r2pColor + ';font-weight:bold;">' + r.regToPayRate + '%</td>'
                + '</tr>';
        }
    }

    function renderDailyTable(rows) {
        var tbody = document.getElementById('ref-daily-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>';
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var bg = i % 2 === 0 ? '#fff' : '#fafafa';
            var v2rColor = r.visitToRegRate >= 10 ? '#67c23a' : (r.visitToRegRate >= 5 ? '#e6a23c' : '#f56c6c');
            var r2pColor = r.regToPayRate >= 5 ? '#67c23a' : (r.regToPayRate >= 2 ? '#e6a23c' : '#f56c6c');
            if (r.visitToRegRate === 0) v2rColor = '#909399';
            if (r.regToPayRate === 0) r2pColor = '#909399';

            tbody.innerHTML += '<tr style="background:' + bg + ';" onmouseover="this.style.background=\'#ecf5ff\'" onmouseout="this.style.background=\'' + bg + '\'">'
                + '<td style="font-weight:bold;">' + r.day + '</td>'
                + '<td style="color:#409eff;">' + r.visits + '</td>'
                + '<td style="color:#1abc9c;font-weight:bold;">' + r.registers + '</td>'
                + '<td style="color:' + v2rColor + ';font-weight:bold;">' + r.visitToRegRate + '%</td>'
                + '<td style="color:#67c23a;font-weight:bold;">' + r.payUsers + '</td>'
                + '<td style="color:#fc8452;font-weight:bold;">¥' + r.revenue + '</td>'
                + '<td style="color:' + r2pColor + ';font-weight:bold;">' + r.regToPayRate + '%</td>'
                + '</tr>';
        }
    }

    var statusMap = {1: ['已发放', '#67c23a'], 2: ['已冲正', '#f56c6c']};

    function renderRewardTable(orders) {
        var tbody = $('#builder-table-main tbody');
        if (!tbody.length) return;
        tbody.empty();
        if (!orders || orders.length === 0) {
            tbody.append('<tr><td colspan="10" style="text-align:center;padding:20px;color:#909399;">暂无数据</td></tr>');
            return;
        }
        for (var i = 0; i < orders.length; i++) {
            var o = orders[i];
            var st = statusMap[o.status] || ['未知', '#909399'];
            tbody.append('<tr>'
                + '<td><div class="table-cell">' + o.id + '</div></td>'
                + '<td><div class="table-cell">' + o.payer_user_id + '</div></td>'
                + '<td><div class="table-cell">' + (o.payer_name || '-') + '</div></td>'
                + '<td><div class="table-cell">' + o.promoter_user_id + '</div></td>'
                + '<td><div class="table-cell">' + (o.ref_code || '-') + '</div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#67c23a">¥' + o.order_amount_yuan + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:#409eff">' + o.commission_rate + '%</span></div></td>'
                + '<td><div class="table-cell"><span style="font-weight:bold;color:#fc8452">' + o.reward_points + '</span></div></td>'
                + '<td><div class="table-cell"><span style="color:' + st[1] + ';font-weight:bold">' + st[0] + '</span></div></td>'
                + '<td><div class="table-cell">' + (o.rewarded_at || '-') + '</div></td>'
                + '</tr>');
        }
    }

    function fetchData() {
        $('#ref-status-text').text('正在加载数据...');
        $.ajax({
            url: '{$ajaxUrl}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.code !== 0) return;
                var d = res.data;
                var o = d.overview;

                $('#ref-card-promoters').text(o.promoterCount);
                $('#ref-card-codes').text(o.codeCount);
                $('#ref-card-visits').text(o.visitCount.toLocaleString());
                $('#ref-card-registers').text(o.registerCount.toLocaleString());
                $('#ref-card-payusers').text(o.payUserCount);
                $('#ref-card-payamount').text('¥' + o.totalPayYuan.toLocaleString());
                $('#ref-card-orders').text(o.orderCount);
                $('#ref-card-commission').text(o.totalCommission.toLocaleString());
                $('#ref-card-newreward').text(o.totalNewUserReward.toLocaleString());
                $('#ref-card-v2r').text(o.visitToRegRate + '%');
                $('#ref-card-r2p').text(o.regToPayRate + '%');
                $('#ref-card-arpu').text('¥' + o.arpu);

                chartTrend.setOption({
                    xAxis: { data: d.trend.days },
                    series: [
                        { data: d.trend.visits },
                        { data: d.trend.registers },
                        { data: d.trend.payUsers },
                        { data: d.trend.revenue }
                    ]
                });

                var funnelMax = d.funnel.length > 0 ? d.funnel[0].value : 100;
                if (funnelMax === 0) funnelMax = 100;
                var funnelColors = ['#409eff', '#1abc9c', '#67c23a'];
                var funnelData = [];
                for (var i = 0; i < d.funnel.length; i++) {
                    funnelData.push({
                        name: d.funnel[i].name,
                        value: d.funnel[i].value,
                        itemStyle: { color: funnelColors[i] || '#909399' }
                    });
                }
                chartFunnel.setOption({ series: [{ max: funnelMax, data: funnelData }] });

                chartPromoter.setOption({ series: [{ data: d.promoterDist }] });

                renderPromoterTable(d.promoterTable);
                renderDailyTable(d.dailyTable);
                renderRewardTable(d.rewardOrders);

                $('#ref-last-update').text('最后更新: ' + res.time);
                $('#ref-status-text').text('数据已加载');
            },
            error: function() {
                $('#ref-status-text').text('数据请求失败');
            }
        });
    }

    window.refRefresh = function() { fetchData(); };
    fetchData();
})();
</script>
JS;
    }
}
