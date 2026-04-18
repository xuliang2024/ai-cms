<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class StripeCharges extends Admin {

    private function currencySymbol($currency) {
        return strtolower($currency) === 'cny' ? '¥' : '$';
    }

    private function formatAmount($amount, $currency) {
        $symbol = $this->currencySymbol($currency);
        $val = round($amount / 100, 2);
        return $symbol . $val;
    }

    public function index()
    {
        $map = $this->getMap();

        $data_list = Db::connect('translate')->table('ts_stripe_charges')
            ->where($map)
            ->order('stripe_created desc')
            ->paginate();

        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'stripe_created=') === 0) {
                    $daterangeValue = substr($param, strlen('stripe_created='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $statsByCurrency = Db::connect('translate')->table('ts_stripe_charges')
            ->whereTime('stripe_created', 'between', [$startDate, $endDate])
            ->field([
                'currency',
                'COUNT(*) as total_count',
                'SUM(CASE WHEN status="succeeded" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status="failed" THEN 1 ELSE 0 END) as failed_count',
                'SUM(CASE WHEN paid=1 THEN amount ELSE 0 END) as total_paid_amount',
                'SUM(amount_refunded) as total_refunded',
                'SUM(CASE WHEN refunded=1 THEN 1 ELSE 0 END) as refund_count',
                'COUNT(DISTINCT customer_email) as unique_customers',
            ])
            ->group('currency')
            ->select();

        $cnyStats = ['total_count' => 0, 'success_count' => 0, 'failed_count' => 0, 'total_paid_amount' => 0, 'total_refunded' => 0, 'refund_count' => 0, 'unique_customers' => 0];
        $usdStats = $cnyStats;
        foreach ($statsByCurrency as $s) {
            if (strtolower($s['currency']) === 'cny') $cnyStats = $s;
            else $usdStats = $s;
        }

        $cnyPaid = round(($cnyStats['total_paid_amount'] ?? 0) / 100, 2);
        $cnyRefunded = round(($cnyStats['total_refunded'] ?? 0) / 100, 2);
        $cnyNet = $cnyPaid - $cnyRefunded;
        $usdPaid = round(($usdStats['total_paid_amount'] ?? 0) / 100, 2);
        $usdRefunded = round(($usdStats['total_refunded'] ?? 0) / 100, 2);
        $usdNet = $usdPaid - $usdRefunded;
        $totalCount = ($cnyStats['total_count'] ?? 0) + ($usdStats['total_count'] ?? 0);
        $totalFailed = ($cnyStats['failed_count'] ?? 0) + ($usdStats['failed_count'] ?? 0);
        $totalCustomers = ($cnyStats['unique_customers'] ?? 0) + ($usdStats['unique_customers'] ?? 0);

        $displayDate = $daterangeValue ? $daterangeValue : ($startDate . ' - ' . date('Y-m-d'));

        $methodStats = Db::connect('translate')->table('ts_stripe_charges')
            ->whereTime('stripe_created', 'between', [$startDate, $endDate])
            ->where('paid', 1)
            ->field([
                'payment_method_type',
                'currency',
                'COUNT(*) as cnt',
                'SUM(amount) as total_amount',
            ])
            ->group('payment_method_type, currency')
            ->order('total_amount desc')
            ->select();

        $chartJs = $this->getTrendChartJs($startDate, $endDate);
        $methodChartJs = $this->getMethodPieChartJs($methodStats);

        $content_html = '<div style="padding:12px;background:#f5f7fa;margin-bottom:12px;border-radius:6px;">'
            . '<div style="text-align:center;margin-bottom:8px;">'
            . '<span style="font-size:14px;font-weight:bold;margin-right:20px;">时间范围: ' . $displayDate . '</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:20px;color:#909399;">总订单: ' . $totalCount . '笔</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:20px;color:#ee6666;">失败: ' . $totalFailed . '笔</span>'
            . '<span style="font-size:14px;font-weight:bold;color:#909399;">独立客户: ' . $totalCustomers . '人</span>'
            . '</div>'
            . '<div style="text-align:center;">'
            . '<span style="font-size:14px;font-weight:bold;margin-right:15px;color:#67C23A;">CNY成功: ¥' . $cnyPaid . ' (' . ($cnyStats['success_count'] ?? 0) . '笔)</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:15px;color:#E6A23C;">CNY退款: ¥' . $cnyRefunded . '</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:30px;color:#409EFF;">CNY净收: ¥' . $cnyNet . '</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:15px;color:#67C23A;">USD成功: $' . $usdPaid . ' (' . ($usdStats['success_count'] ?? 0) . '笔)</span>'
            . '<span style="font-size:14px;font-weight:bold;margin-right:15px;color:#E6A23C;">USD退款: $' . $usdRefunded . '</span>'
            . '<span style="font-size:14px;font-weight:bold;color:#409EFF;">USD净收: $' . $usdNet . '</span>'
            . '</div></div>';

        $content_html .= '<div id="trend_chart" style="width:100%;height:350px;margin-bottom:10px;"></div>';
        $content_html .= '<div style="display:flex;width:100%;">'
            . '<div id="method_pie_chart" style="width:50%;height:350px;"></div>'
            . '<div id="daily_bar_chart" style="width:50%;height:350px;"></div>'
            . '</div>';

        $content_html .= $this->buildMethodStatsTable($methodStats);

        $dailyBarJs = $this->getDailyBarChartJs($startDate, $endDate);

        return ZBuilder::make('table')
            ->setPageTitle('Stripe 账单列表')
            ->setTableName('video/StripeChargesModel', 2)
            ->addColumns([
                ['id', 'ID'],
                ['charge_id', 'Charge ID', 'callback', function($value) {
                    $short = substr($value, 0, 20) . '..';
                    return "<span title='{$value}' style='font-family:monospace;font-size:12px;'>{$short}</span>";
                }],
                ['user_id', '用户ID', 'callback', function($value) {
                    return $value > 0 ? "<span style='color:#409eff;font-weight:bold;'>{$value}</span>" : '<span style="color:#ccc;">-</span>';
                }],
                ['customer_email', '邮箱', 'callback', function($value) {
                    if (empty($value)) return '-';
                    return mb_strlen($value) > 25 ? "<span title='{$value}'>" . mb_substr($value, 0, 25) . '..</span>' : $value;
                }],
                ['customer_name', '客户名'],
                ['amount', '金额', 'callback', function($value, $data) {
                    $symbol = strtolower($data['currency']) === 'cny' ? '¥' : '$';
                    $val = round($value / 100, 2);
                    return "<span style='font-weight:bold;color:#67c23a;'>{$symbol}{$val}</span> <span style='color:#ccc;font-size:11px;'>({$value}分)</span>";
                }, '__data__'],
                ['amount_refunded', '退款', 'callback', function($value, $data) {
                    if ($value <= 0) return '<span style="color:#ccc;">0</span>';
                    $symbol = strtolower($data['currency']) === 'cny' ? '¥' : '$';
                    $val = round($value / 100, 2);
                    return "<span style='color:#ee6666;font-weight:bold;'>{$symbol}{$val}</span>";
                }, '__data__'],
                ['currency', '币种', 'callback', function($value) {
                    $upper = strtoupper($value);
                    $color = $upper === 'CNY' ? '#e6a23c' : '#409eff';
                    return "<span style='color:{$color};font-weight:bold;'>{$upper}</span>";
                }],
                ['status', '状态', 'callback', function($value) {
                    $map = [
                        'succeeded' => ['成功', '#67c23a'],
                        'failed' => ['失败', '#ee6666'],
                        'pending' => ['处理中', '#e6a23c'],
                    ];
                    $info = $map[$value] ?? [$value, '#909399'];
                    return "<span style='color:{$info[1]};font-weight:bold;'>{$info[0]}</span>";
                }],
                ['payment_method_type', '支付方式', 'callback', function($value) {
                    $map = ['card' => '信用卡', 'alipay' => '支付宝', 'wechat_pay' => '微信', 'link' => 'Link'];
                    return $map[$value] ?? $value;
                }],
                ['failure_message', '失败原因', 'callback', function($value) {
                    if (empty($value)) return '-';
                    return mb_strlen($value) > 20 ? "<span title='{$value}' style='color:#ee6666;'>" . mb_substr($value, 0, 20) . '..</span>' : "<span style='color:#ee6666;'>{$value}</span>";
                }],
                ['stripe_created', 'Stripe创建时间'],
                ['receipt_url', '收据', 'callback', function($value) {
                    if (empty($value)) return '-';
                    return "<a href='{$value}' target='_blank' style='color:#409eff;'>查看</a>";
                }],
            ])
            ->setSearchArea([
                ['text', 'user_id', '用户ID'],
                ['text', 'customer_email', '邮箱'],
                ['text', 'charge_id', 'Charge ID'],
                ['text', 'out_trade_no', '订单号'],
                ['select', 'status', '状态', '', '', ['succeeded' => '成功', 'failed' => '失败', 'pending' => '处理中']],
                ['select', 'payment_method_type', '支付方式', '', '', ['card' => '信用卡', 'alipay' => '支付宝', 'wechat_pay' => '微信支付', 'link' => 'Link']],
                ['select', 'currency', '币种', '', '', ['cny' => 'CNY人民币', 'usd' => 'USD美元']],
                ['daterange', 'stripe_created', '时间'],
            ])
            ->addRightButton('custom', [
                'title' => '详情',
                'icon'  => 'fa fa-eye',
                'class' => 'btn btn-xs btn-info',
                'href'  => url('detail', ['id' => '__id__']),
            ])
            ->setRowList($data_list)
            ->setHeight('auto')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($chartJs . $methodChartJs . $dailyBarJs . $this->getSortableTableJs())
            ->fetch();
    }

    public function detail()
    {
        $id = input('param.id', 0, 'intval');
        if (!$id) $this->error('参数错误');

        $charge = Db::connect('translate')->table('ts_stripe_charges')
            ->where('id', $id)
            ->find();
        if (!$charge) $this->error('记录不存在');

        $symbol = $this->currencySymbol($charge['currency']);
        $currencyLabel = strtoupper($charge['currency']);

        $userInfo = null;
        if ($charge['user_id'] > 0) {
            $userInfo = Db::connect('translate')->table('ts_users')
                ->where('id', $charge['user_id'])
                ->field('id, name, phone, points_balance, cash_balance, vip_level')
                ->find();
        }

        $statusMap = ['succeeded' => '成功', 'failed' => '失败', 'pending' => '处理中'];
        $methodMap = ['card' => '信用卡', 'alipay' => '支付宝', 'wechat_pay' => '微信支付', 'link' => 'Link'];

        $items = [
            ['Charge ID', $charge['charge_id']],
            ['Payment Intent', $charge['payment_intent_id'] ?: '-'],
            ['用户ID', $charge['user_id'] > 0 ? $charge['user_id'] : '-'],
            ['订单号', $charge['out_trade_no'] ?: '-'],
            ['币种', $currencyLabel],
            ['金额', $symbol . round($charge['amount'] / 100, 2) . ' (' . $currencyLabel . ')'],
            ['退款金额', $symbol . round($charge['amount_refunded'] / 100, 2)],
            ['状态', $statusMap[$charge['status']] ?? $charge['status']],
            ['已支付', $charge['paid'] ? '是' : '否'],
            ['已退款', $charge['refunded'] ? '是' : '否'],
            ['邮箱', $charge['customer_email'] ?: '-'],
            ['客户名', $charge['customer_name'] ?: '-'],
            ['描述', $charge['description'] ?: '-'],
            ['支付方式', $methodMap[$charge['payment_method_type']] ?? ($charge['payment_method_type'] ?: '-')],
            ['失败代码', $charge['failure_code'] ?: '-'],
            ['失败原因', $charge['failure_message'] ?: '-'],
            ['Stripe客户ID', $charge['stripe_customer_id'] ?: '-'],
            ['Stripe创建时间', $charge['stripe_created']],
            ['同步时间', $charge['synced_at']],
        ];

        if ($charge['receipt_url']) {
            $items[] = ['收据链接', "<a href='{$charge['receipt_url']}' target='_blank'>点击查看</a>"];
        }

        if ($userInfo) {
            $vipNames = [0 => '非会员', 1 => '普通会员', 2 => '高级会员', 3 => '铜牌会员'];
            $items[] = ['--- 用户信息 ---', ''];
            $items[] = ['用户名', $userInfo['name'] ?? '-'];
            $items[] = ['手机号', $userInfo['phone'] ?? '-'];
            $items[] = ['VIP等级', $vipNames[$userInfo['vip_level']] ?? $userInfo['vip_level']];
            $items[] = ['积分余额', $userInfo['points_balance'] ?? 0];
            $items[] = ['现金余额', $userInfo['cash_balance'] ?? 0];
        }

        $html = '<div style="padding:20px;max-width:800px;">';
        $html .= '<table class="table table-bordered">';
        foreach ($items as $item) {
            $labelStyle = $item[0] === '--- 用户信息 ---'
                ? 'background:#409eff;color:#fff;font-weight:bold;text-align:center;'
                : 'background:#f5f7fa;font-weight:bold;width:180px;';
            $valStyle = $item[0] === '--- 用户信息 ---' ? 'background:#409eff;color:#fff;' : '';
            $html .= "<tr><td style='{$labelStyle}'>{$item[0]}</td><td style='{$valStyle}'>{$item[1]}</td></tr>";
        }
        $html .= '</table></div>';

        return ZBuilder::make('form')
            ->setPageTitle('Stripe 账单详情 - ' . $charge['charge_id'])
            ->setExtraHtml($html, 'form_top')
            ->fetch();
    }

    private function getTrendChartJs($startDate, $endDate)
    {
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $isSameDay = ($daysDiff <= 2);
        $groupBy = $isSameDay
            ? 'DATE_FORMAT(stripe_created, "%m-%d %H:00")'
            : 'DATE(stripe_created)';

        $trendData = Db::connect('translate')->table('ts_stripe_charges')
            ->whereTime('stripe_created', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as time_axis",
                'SUM(CASE WHEN paid=1 AND currency="cny" THEN amount ELSE 0 END) as cny_paid',
                'SUM(CASE WHEN paid=1 AND currency="usd" THEN amount ELSE 0 END) as usd_paid',
                'SUM(CASE WHEN currency="cny" THEN amount_refunded ELSE 0 END) as cny_refunded',
                'SUM(CASE WHEN currency="usd" THEN amount_refunded ELSE 0 END) as usd_refunded',
                'SUM(CASE WHEN status="succeeded" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status="failed" THEN 1 ELSE 0 END) as failed_count',
            ])
            ->group('time_axis')
            ->order('time_axis asc')
            ->select();

        if (empty($trendData)) return '';

        $timeLabels = [];
        $cnyPaidData = [];
        $usdPaidData = [];
        $successData = [];
        $failData = [];

        foreach ($trendData as $row) {
            $timeLabels[] = $row['time_axis'];
            $cnyPaidData[] = round(intval($row['cny_paid']) / 100, 2);
            $usdPaidData[] = round(intval($row['usd_paid']) / 100, 2);
            $successData[] = intval($row['success_count']);
            $failData[] = intval($row['failed_count']);
        }

        $timeJson = json_encode($timeLabels, JSON_UNESCAPED_UNICODE);
        $cnyJson = json_encode($cnyPaidData);
        $usdJson = json_encode($usdPaidData);
        $successJson = json_encode($successData);
        $failJson = json_encode($failData);
        $xAxisType = $isSameDay ? '小时' : '日期';

        return "
        <script type='text/javascript'>
        var trendChart = echarts.init(document.getElementById('trend_chart'));
        trendChart.setOption({
            title: { text: 'Stripe 收入趋势 (按{$xAxisType})', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis' },
            legend: { data: ['CNY收入(¥)', 'USD收入($)', '成功笔数', '失败笔数'], top: 5, right: 20 },
            grid: { left: '3%', right: '4%', bottom: '3%', top: 50, containLabel: true },
            toolbox: { feature: { saveAsImage: {} } },
            xAxis: { type: 'category', boundaryGap: false, data: {$timeJson}, axisLabel: { fontSize: 11, rotate: " . ($isSameDay ? 30 : 0) . " } },
            yAxis: [
                { type: 'value', name: '金额', position: 'left', axisLabel: { fontSize: 11 } },
                { type: 'value', name: '笔数', position: 'right', axisLabel: { fontSize: 11 } }
            ],
            series: [
                { name: 'CNY收入(¥)', type: 'line', data: {$cnyJson}, smooth: true, itemStyle: { color: '#e6a23c' }, lineStyle: { width: 3 }, areaStyle: { color: 'rgba(230,162,60,0.15)' }, label: { show: true, position: 'top', fontSize: 10 } },
                { name: 'USD收入($)', type: 'line', data: {$usdJson}, smooth: true, itemStyle: { color: '#409eff' }, lineStyle: { width: 3 }, areaStyle: { color: 'rgba(64,158,255,0.15)' }, label: { show: true, position: 'top', fontSize: 10 } },
                { name: '成功笔数', type: 'bar', yAxisIndex: 1, data: {$successJson}, itemStyle: { color: 'rgba(145,204,117,0.6)' }, barWidth: '15%' },
                { name: '失败笔数', type: 'bar', yAxisIndex: 1, data: {$failJson}, itemStyle: { color: 'rgba(238,102,102,0.5)' }, barWidth: '15%' }
            ]
        });
        </script>";
    }

    private function getMethodPieChartJs($methodStats)
    {
        if (empty($methodStats)) return '';

        $methodNames = ['card' => '信用卡', 'alipay' => '支付宝', 'wechat_pay' => '微信支付', 'link' => 'Link'];
        $grouped = [];
        foreach ($methodStats as $m) {
            $key = $m['payment_method_type'];
            $name = $methodNames[$key] ?? $key;
            $cur = strtoupper($m['currency']);
            $label = $name . '(' . $cur . ')';
            $symbol = strtolower($m['currency']) === 'cny' ? '¥' : '$';
            $grouped[] = [
                'name' => $label,
                'value' => round($m['total_amount'] / 100, 2),
                'symbol' => $symbol,
            ];
        }

        $pieJson = json_encode($grouped, JSON_UNESCAPED_UNICODE);

        return "
        <script type='text/javascript'>
        var methodPie = echarts.init(document.getElementById('method_pie_chart'));
        var pieData = {$pieJson};
        methodPie.setOption({
            title: { text: '支付方式占比(按币种)', left: 'center', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'item', formatter: function(p) { var d = pieData[p.dataIndex]; return p.name + ': ' + d.symbol + p.value + ' (' + p.percent + '%)'; } },
            legend: { orient: 'vertical', left: 'left', top: 30, textStyle: { fontSize: 11 } },
            series: [{
                name: '支付金额',
                type: 'pie',
                radius: ['35%', '65%'],
                center: ['60%', '55%'],
                avoidLabelOverlap: false,
                label: { show: true, formatter: function(p) { var d = pieData[p.dataIndex]; return p.name + '\\n' + d.symbol + p.value; }, fontSize: 11 },
                data: pieData
            }]
        });
        </script>";
    }

    private function getDailyBarChartJs($startDate, $endDate)
    {
        $cnyDaily = Db::connect('translate')->table('ts_stripe_charges')
            ->whereTime('stripe_created', 'between', [$startDate, $endDate])
            ->where('paid', 1)
            ->where('currency', 'cny')
            ->field(['DATE(stripe_created) as day', 'SUM(amount) as total_amount'])
            ->group('day')
            ->order('day asc')
            ->select();

        $usdDaily = Db::connect('translate')->table('ts_stripe_charges')
            ->whereTime('stripe_created', 'between', [$startDate, $endDate])
            ->where('paid', 1)
            ->where('currency', 'usd')
            ->field(['DATE(stripe_created) as day', 'SUM(amount) as total_amount'])
            ->group('day')
            ->order('day asc')
            ->select();

        if (empty($cnyDaily) && empty($usdDaily)) return '';

        $allDays = [];
        $cnyMap = [];
        $usdMap = [];
        foreach ($cnyDaily as $d) { $allDays[$d['day']] = true; $cnyMap[$d['day']] = round($d['total_amount'] / 100, 2); }
        foreach ($usdDaily as $d) { $allDays[$d['day']] = true; $usdMap[$d['day']] = round($d['total_amount'] / 100, 2); }
        ksort($allDays);
        $days = array_keys($allDays);

        $cnyAmounts = [];
        $usdAmounts = [];
        foreach ($days as $day) {
            $cnyAmounts[] = $cnyMap[$day] ?? 0;
            $usdAmounts[] = $usdMap[$day] ?? 0;
        }

        $daysJson = json_encode($days, JSON_UNESCAPED_UNICODE);
        $cnyJson = json_encode($cnyAmounts);
        $usdJson = json_encode($usdAmounts);

        return "
        <script type='text/javascript'>
        var dailyBar = echarts.init(document.getElementById('daily_bar_chart'));
        dailyBar.setOption({
            title: { text: '每日收入对比', textStyle: { fontSize: 14 } },
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { data: ['CNY(¥)', 'USD($)'], top: 5, right: 20 },
            grid: { left: '3%', right: '4%', bottom: '3%', top: 50, containLabel: true },
            xAxis: { type: 'category', data: {$daysJson}, axisLabel: { fontSize: 11, rotate: 30 } },
            yAxis: { type: 'value', axisLabel: { fontSize: 11 } },
            series: [
                { name: 'CNY(¥)', type: 'bar', data: {$cnyJson}, itemStyle: { color: '#e6a23c' }, barWidth: '30%', label: { show: true, position: 'top', fontSize: 10, formatter: '¥{c}' } },
                { name: 'USD($)', type: 'bar', data: {$usdJson}, itemStyle: { color: '#409eff' }, barWidth: '30%', label: { show: true, position: 'top', fontSize: 10, formatter: '\${c}' } }
            ]
        });
        </script>";
    }

    private function buildMethodStatsTable($methodStats)
    {
        if (empty($methodStats)) return '';

        $methodNames = ['card' => '信用卡', 'alipay' => '支付宝', 'wechat_pay' => '微信支付', 'link' => 'Link'];

        $html = '<div style="margin:15px 0;">'
            . '<h4 style="margin-bottom:10px;color:#333;">支付方式统计（成功订单）</h4>'
            . '<table class="table table-striped table-bordered" style="margin-bottom:0;">'
            . '<thead style="background-color:#f5f5f5;"><tr>'
            . '<th style="text-align:center;width:50px;">序号</th>'
            . '<th style="text-align:center;">支付方式</th>'
            . '<th style="text-align:center;width:80px;">币种</th>'
            . '<th style="text-align:center;width:120px;">金额</th>'
            . '<th style="text-align:center;width:100px;">笔数</th>'
            . '</tr></thead><tbody>';

        $idx = 1;
        foreach ($methodStats as $m) {
            $name = $methodNames[$m['payment_method_type']] ?? $m['payment_method_type'];
            $cur = strtoupper($m['currency']);
            $symbol = strtolower($m['currency']) === 'cny' ? '¥' : '$';
            $amountVal = round($m['total_amount'] / 100, 2);
            $html .= "<tr>"
                . "<td style='text-align:center;'>{$idx}</td>"
                . "<td style='padding-left:10px;'>{$name}</td>"
                . "<td style='text-align:center;font-weight:bold;'>{$cur}</td>"
                . "<td style='text-align:right;padding-right:10px;color:#67c23a;font-weight:bold;'>{$symbol}{$amountVal}</td>"
                . "<td style='text-align:center;color:#409eff;font-weight:bold;'>{$m['cnt']}</td>"
                . "</tr>";
            $idx++;
        }

        $html .= '</tbody></table></div>';
        return $html;
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

                var aNum = parseFloat(aText.replace(/[%$¥,，\s]/g, ''));
                var bNum = parseFloat(bText.replace(/[%$¥,，\s]/g, ''));

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
}
