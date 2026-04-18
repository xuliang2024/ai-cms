<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class HighValueUsers extends Admin {

    private $usdPayInfoIds = [];

    private function getUsdPayInfoIds()
    {
        if (empty($this->usdPayInfoIds)) {
            $this->usdPayInfoIds = Db::connect('translate')->table('ts_pay_info')
                ->where('pay_type', 11)
                ->column('id');
        }
        return $this->usdPayInfoIds;
    }

    public function index()
    {
        $cnyThreshold = input('param.cny_min', 500, 'intval') * 100;
        $usdThreshold = input('param.usd_min', 50, 'intval') * 100;

        $usdIds = $this->getUsdPayInfoIds();
        $usdIdStr = implode(',', $usdIds);

        $cnyCase = !empty($usdIds)
            ? "SUM(CASE WHEN o.pay_info_id NOT IN ({$usdIdStr}) THEN o.money ELSE 0 END)"
            : "SUM(o.money)";
        $usdCase = !empty($usdIds)
            ? "SUM(CASE WHEN o.pay_info_id IN ({$usdIdStr}) THEN o.money ELSE 0 END)"
            : "0";

        $subQuery = Db::connect('translate')->table('ts_pay_order_info')->alias('o')
            ->field([
                'o.user_id',
                "{$cnyCase} as cny_total",
                "{$usdCase} as usd_total",
                'COUNT(*) as order_count',
                'MAX(o.time) as last_pay_time',
                'MIN(o.time) as first_pay_time',
            ])
            ->where('o.status', 2)
            ->group('o.user_id')
            ->having("cny_total >= {$cnyThreshold} OR usd_total >= {$usdThreshold}")
            ->orderRaw('(cny_total + usd_total) desc')
            ->buildSql();

        $totalResult = Db::connect('translate')->query("SELECT COUNT(*) as total FROM ({$subQuery}) sub_count");
        $totalUsers = $totalResult[0]['total'] ?? 0;

        $page = input('param.page', 1, 'intval');
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $highUsers = Db::connect('translate')->query("SELECT * FROM ({$subQuery}) sub LIMIT {$offset}, {$pageSize}");

        if (empty($highUsers)) {
            return ZBuilder::make('table')
                ->setPageTitle('高端用户列表')
                ->setPageTips('暂无符合条件的高端用户')
                ->setExtraHtml($this->buildFilterHtml($cnyThreshold / 100, $usdThreshold / 100), 'toolbar_top')
                ->addColumns([['user_id', '用户ID']])
                ->setRowList([])
                ->fetch();
        }

        $userIds = array_column($highUsers, 'user_id');

        $users = Db::connect('translate')->table('ts_users')
            ->whereIn('id', $userIds)
            ->column('id, name, phone, points_balance, cash_balance, vip_level, vip_time, time as reg_time, pay_cnt, from_user_id, google_email', 'id');

        $lastFalTasks = Db::connect('translate')->table('ts_fal_tasks')
            ->field(['user_id', 'MAX(created_at) as last_fal_time'])
            ->whereIn('user_id', $userIds)
            ->group('user_id')
            ->select();
        $falTimeMap = [];
        foreach ($lastFalTasks as $ft) {
            $falTimeMap[$ft['user_id']] = $ft['last_fal_time'];
        }

        $globalStats = [
            'total_users' => $totalUsers,
            'cny_total' => 0, 'usd_total' => 0,
            'points_total' => 0, 'cash_total' => 0,
        ];

        $vipNames = [0 => '非会员', 1 => '普通会员', 2 => '高级会员', 3 => '铜牌会员'];
        $dataList = [];
        $rank = $offset;
        foreach ($highUsers as $hu) {
            $rank++;
            $uid = $hu['user_id'];
            $user = $users[$uid] ?? [];

            $cnyYuan = round($hu['cny_total'] / 100, 2);
            $usdDollar = round($hu['usd_total'] / 100, 2);
            $totalYuan = $cnyYuan + $usdDollar;

            $globalStats['cny_total'] += $hu['cny_total'];
            $globalStats['usd_total'] += $hu['usd_total'];
            $globalStats['points_total'] += ($user['points_balance'] ?? 0);
            $globalStats['cash_total'] += ($user['cash_balance'] ?? 0);

            $contact = $user['phone'] ?? '';
            if (empty($contact) || strpos($contact, 'google_') === 0 || strpos($contact, 'op_') === 0) {
                $contact = $user['google_email'] ?? $contact;
            }

            $dataList[] = [
                'id'              => $uid,
                'rank'            => $rank,
                'user_id'         => $uid,
                'user_name'       => $user['name'] ?? '-',
                'contact'         => $contact ?: '-',
                'vip_level'       => isset($user['vip_level']) ? ($vipNames[$user['vip_level']] ?? $user['vip_level']) : '-',
                'vip_time'        => $user['vip_time'] ?? '-',
                'cny_total'       => $cnyYuan,
                'usd_total'       => $usdDollar,
                'total_recharge'  => $totalYuan,
                'order_count'     => $hu['order_count'],
                'points_balance'  => $user['points_balance'] ?? 0,
                'cash_balance'    => $user['cash_balance'] ?? 0,
                'total_balance'   => ($user['points_balance'] ?? 0) + ($user['cash_balance'] ?? 0),
                'from_user_id'    => $user['from_user_id'] ?? 0,
                'first_pay_time'  => $hu['first_pay_time'],
                'last_pay_time'   => $hu['last_pay_time'],
                'last_fal_time'   => $falTimeMap[$uid] ?? '-',
                'reg_time'        => $user['reg_time'] ?? '-',
            ];
        }

        $totalPages = ceil($totalUsers / $pageSize);
        $paginationHtml = $this->buildPaginationHtml($page, $totalPages, $cnyThreshold / 100, $usdThreshold / 100);

        $tips = "共 <b>{$totalUsers}</b> 位高端用户 (CNY充值≥¥" . ($cnyThreshold / 100) . " 或 USD充值≥$" . ($usdThreshold / 100) . ") | "
            . "CNY充值总额: <b style='color:#e6a23c;'>¥" . round($globalStats['cny_total'] / 100, 2) . "</b> | "
            . "USD充值总额: <b style='color:#409eff;'>\$" . round($globalStats['usd_total'] / 100, 2) . "</b> | "
            . "积分余额合计: <b>" . $globalStats['points_total'] . "</b> | "
            . "现金余额合计: <b>" . $globalStats['cash_total'] . "</b>";

        $chartHtml = '<div style="display:flex;width:100%;margin-bottom:10px;">'
            . '<div id="recharge_pie" style="width:50%;height:350px;"></div>'
            . '<div id="vip_pie" style="width:50%;height:350px;"></div>'
            . '</div>';

        $chartJs = $this->getRechargePieJs($dataList) . $this->getVipPieJs($dataList);

        return ZBuilder::make('table')
            ->setPageTitle('高端用户列表')
            ->setPageTips($tips, 'info')
            ->setTableName('video/HighValueUsersModel', 2)
            ->addColumns([
                ['rank', '排名'],
                ['user_id', '用户ID'],
                ['user_name', '用户名'],
                ['contact', '联系方式', 'callback', function($value) {
                    if (empty($value) || $value === '-') return '-';
                    return mb_strlen($value) > 22 ? "<span title='{$value}'>" . mb_substr($value, 0, 22) . '..</span>' : $value;
                }],
                ['vip_level', 'VIP等级', 'callback', function($value) {
                    $color = ($value === '非会员') ? '#999' : '#e6a23c';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['cny_total', 'CNY充值(元)', 'callback', function($value) {
                    $style = $value >= 1000 ? 'color:#e6a23c;font-weight:bold;font-size:14px;' : 'color:#e6a23c;font-weight:bold;';
                    return "<span style='{$style}'>¥{$value}</span>";
                }],
                ['usd_total', 'USD充值($)', 'callback', function($value) {
                    if ($value <= 0) return '<span style="color:#ccc;">-</span>';
                    $style = $value >= 100 ? 'color:#409eff;font-weight:bold;font-size:14px;' : 'color:#409eff;font-weight:bold;';
                    return "<span style='{$style}'>\${$value}</span>";
                }],
                ['total_recharge', '充值总计', 'callback', function($value) {
                    $style = 'font-weight:bold;color:#67c23a;';
                    if ($value >= 5000) $style .= 'font-size:15px;';
                    return "<span style='{$style}'>{$value}</span>";
                }],
                ['order_count', '订单数'],
                ['points_balance', '积分余额', 'callback', function($value) {
                    $color = $value < 10000 ? '#ee6666' : '#67c23a';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['cash_balance', '现金余额', 'callback', function($value) {
                    $color = $value > 0 ? '#67c23a' : ($value < 0 ? '#ee6666' : '#999');
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['total_balance', '总积分', 'callback', function($value) {
                    $color = $value < 10000 ? '#ee6666' : '#67c23a';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['from_user_id', '上级用户', 'callback', function($value) {
                    return $value > 0 ? $value : '<span style="color:#ccc;">-</span>';
                }],
                ['first_pay_time', '首次充值'],
                ['last_pay_time', '最近充值'],
                ['last_fal_time', '最近任务', 'callback', function($value) {
                    if (empty($value) || $value === '-') return '<span style="color:#ccc;">无记录</span>';
                    $diff = time() - strtotime($value);
                    if ($diff < 3600) $ago = floor($diff / 60) . '分钟前';
                    elseif ($diff < 86400) $ago = floor($diff / 3600) . '小时前';
                    elseif ($diff < 604800) $ago = floor($diff / 86400) . '天前';
                    else $ago = '';
                    $color = $diff < 86400 ? '#67c23a' : ($diff < 604800 ? '#e6a23c' : '#999');
                    $agoHtml = $ago ? " <span style='font-size:11px;'>({$ago})</span>" : '';
                    return "<span style='color:{$color};'>{$value}{$agoHtml}</span>";
                }],
                ['reg_time', '注册时间'],
            ])
            ->setExtraHtml($this->buildFilterHtml($cnyThreshold / 100, $usdThreshold / 100) . $chartHtml, 'toolbar_top')
            ->setExtraHtml($paginationHtml, 'toolbar_bottom')
            ->setRowList($dataList)
            ->setHeight('auto')
            ->js("libs/echart/echarts.min")
            ->setExtraJs($chartJs . $this->getSortableTableJs())
            ->fetch();
    }

    private function buildFilterHtml($cnyMin, $usdMin)
    {
        $cnyOptions = [100, 200, 500, 1000, 2000, 5000];
        $usdOptions = [10, 30, 50, 100, 200, 500];

        $html = '<div style="margin-bottom:10px;padding:10px;background:#f5f7fa;border-radius:4px;">';
        $html .= '<span style="margin-right:10px;font-weight:bold;color:#e6a23c;">CNY充值≥：</span>';
        foreach ($cnyOptions as $v) {
            $active = ($v == $cnyMin);
            $style = $active
                ? 'background:#e6a23c;color:#fff;border-color:#e6a23c;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['cny_min' => $v, 'usd_min' => $usdMin]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:4px 12px;margin-right:4px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;font-size:13px;{$style}'>¥{$v}</a>";
        }
        $html .= '<span style="margin-left:20px;margin-right:10px;font-weight:bold;color:#409eff;">USD充值≥：</span>';
        foreach ($usdOptions as $v) {
            $active = ($v == $usdMin);
            $style = $active
                ? 'background:#409eff;color:#fff;border-color:#409eff;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['cny_min' => $cnyMin, 'usd_min' => $v]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:4px 12px;margin-right:4px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;font-size:13px;{$style}'>\${$v}</a>";
        }
        $html .= '</div>';
        return $html;
    }

    private function buildPaginationHtml($currentPage, $totalPages, $cnyMin, $usdMin)
    {
        if ($totalPages <= 1) return '';

        $html = '<div style="text-align:center;padding:10px;">';
        $start = max(1, $currentPage - 4);
        $end = min($totalPages, $currentPage + 4);

        if ($currentPage > 1) {
            $url = url('index', ['page' => $currentPage - 1, 'cny_min' => $cnyMin, 'usd_min' => $usdMin]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 12px;margin:0 2px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;color:#606266;'>上一页</a>";
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $currentPage);
            $style = $active
                ? 'background:#409eff;color:#fff;border-color:#409eff;'
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $url = url('index', ['page' => $i, 'cny_min' => $cnyMin, 'usd_min' => $usdMin]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 12px;margin:0 2px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;font-size:13px;{$style}'>{$i}</a>";
        }

        if ($currentPage < $totalPages) {
            $url = url('index', ['page' => $currentPage + 1, 'cny_min' => $cnyMin, 'usd_min' => $usdMin]);
            $html .= "<a href='{$url}' style='display:inline-block;padding:5px 12px;margin:0 2px;border:1px solid #dcdfe6;border-radius:3px;text-decoration:none;color:#606266;'>下一页</a>";
        }

        $html .= "<span style='margin-left:10px;color:#909399;'>共 {$totalPages} 页</span>";
        $html .= '</div>';
        return $html;
    }

    private function getRechargePieJs($dataList)
    {
        if (empty($dataList)) return '';

        $topUsers = array_slice($dataList, 0, 15);
        $pieData = [];
        foreach ($topUsers as $u) {
            $label = $u['user_name'] !== '-' ? $u['user_name'] : 'UID:' . $u['user_id'];
            $pieData[] = ['name' => $label, 'value' => $u['total_recharge']];
        }
        $pieJson = json_encode($pieData, JSON_UNESCAPED_UNICODE);

        return "
        <script type='text/javascript'>
        var rechargePie = echarts.init(document.getElementById('recharge_pie'));
        rechargePie.setOption({
            title: { text: 'Top15 充值占比', left: 'center', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend: { orient: 'vertical', left: 'left', top: 30, textStyle: { fontSize: 10 }, type: 'scroll' },
            series: [{
                name: '充值金额', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
                label: { show: false }, emphasis: { label: { show: true, fontSize: 12, fontWeight: 'bold' } },
                data: {$pieJson}
            }]
        });
        </script>";
    }

    private function getVipPieJs($dataList)
    {
        if (empty($dataList)) return '';

        $vipCount = [];
        foreach ($dataList as $u) {
            $level = $u['vip_level'];
            $vipCount[$level] = ($vipCount[$level] ?? 0) + 1;
        }
        $pieData = [];
        foreach ($vipCount as $level => $cnt) {
            $pieData[] = ['name' => $level, 'value' => $cnt];
        }
        $pieJson = json_encode($pieData, JSON_UNESCAPED_UNICODE);

        return "
        <script type='text/javascript'>
        var vipPie = echarts.init(document.getElementById('vip_pie'));
        vipPie.setOption({
            title: { text: 'VIP等级分布', left: 'center', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'item', formatter: '{b}: {c}人 ({d}%)' },
            legend: { orient: 'vertical', left: 'left', top: 30, textStyle: { fontSize: 11 } },
            series: [{
                name: 'VIP等级', type: 'pie', radius: ['35%', '65%'], center: ['60%', '55%'],
                label: { show: true, formatter: '{b}\\n{c}人', fontSize: 11 },
                data: {$pieJson}
            }]
        });
        </script>";
    }

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
           .attr('data-sort-dir', 'none').attr('data-col-idx', index);
        $th.append('<span class="sort-icon" style="display:inline-block;margin-left:3px;font-size:10px;color:#c0c4cc;vertical-align:middle;">⇅</span>');
        $th.on('click', function() {
            var colIdx = parseInt($(this).attr('data-col-idx'));
            var dir = $(this).attr('data-sort-dir');
            var newDir = (dir === 'asc') ? 'desc' : 'asc';
            $thead.find('th').attr('data-sort-dir', 'none').find('.sort-icon').css('color', '#c0c4cc').html('⇅');
            $(this).attr('data-sort-dir', newDir).find('.sort-icon').css('color', '#409eff').html(newDir === 'asc' ? '↑' : '↓');
            var rows = $tbody.find('tr').toArray();
            rows.sort(function(a, b) {
                var aCell = $(a).find('td').eq(colIdx), bCell = $(b).find('td').eq(colIdx);
                var aDiv = aCell.find('.table-cell'), bDiv = bCell.find('.table-cell');
                var aText = (aDiv.length ? aDiv : aCell).text().trim();
                var bText = (bDiv.length ? bDiv : bCell).text().trim();
                var aNum = parseFloat(aText.replace(/[%$¥,，\s]/g, ''));
                var bNum = parseFloat(bText.replace(/[%$¥,，\s]/g, ''));
                if (!isNaN(aNum) && !isNaN(bNum)) return newDir === 'asc' ? aNum - bNum : bNum - aNum;
                return newDir === 'asc' ? aText.localeCompare(bText, 'zh') : bText.localeCompare(aText, 'zh');
            });
            $tbody.empty().append(rows);
        });
        $th.on('mouseenter', function() { if ($(this).attr('data-sort-dir') === 'none') $(this).find('.sort-icon').css('color', '#909399'); })
           .on('mouseleave', function() { if ($(this).attr('data-sort-dir') === 'none') $(this).find('.sort-icon').css('color', '#c0c4cc'); });
    });
});
</script>
JS;
    }
}
