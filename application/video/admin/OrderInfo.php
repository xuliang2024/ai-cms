<?php
// 支付订单列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class OrderInfo extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_pay_order_info')
        ->alias('a')
        ->Join('ts_users u','a.user_id=u.id')
        ->where($map)
        ->field('a.*,u.from_user_id as from_user_id_u')
        ->order('time desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        // 获取查询参数
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'a.time=') === 0) {
                    $daterangeValue = substr($param, strlen('a.time='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        // 获取所有 USD 套餐模版ID (ts_pay_info.pay_type=11)
        $usd_pay_info_ids = DB::connect('translate')->table('ts_pay_info')
            ->where('pay_type', 11)
            ->column('id');

        // 获取 CNY 总金额和总订单数（排除 USD 订单）
        $cny_query = DB::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->whereTime('time', 'between', [$startDate, $endDate]);
        if (!empty($usd_pay_info_ids)) {
            $cny_query->whereNotIn('pay_info_id', $usd_pay_info_ids);
        }
        $cny_stats = $cny_query->field([
                'SUM(money) as grand_total_money',
                'COUNT(*) as grand_total_count'
            ])->find();

        // 获取 USD 总金额和总订单数
        $usd_stats = ['grand_total_money' => 0, 'grand_total_count' => 0];
        if (!empty($usd_pay_info_ids)) {
            $usd_stats = DB::connect('translate')->table('ts_pay_order_info')
                ->where('status', 2)
                ->whereTime('time', 'between', [$startDate, $endDate])
                ->whereIn('pay_info_id', $usd_pay_info_ids)
                ->field([
                    'SUM(money) as grand_total_money',
                    'COUNT(*) as grand_total_count'
                ])
                ->find();
        }

        $cny_total_money = isset($cny_stats['grand_total_money']) ? round($cny_stats['grand_total_money'] / 100, 2) : 0;
        $cny_total_count = isset($cny_stats['grand_total_count']) ? $cny_stats['grand_total_count'] : 0;
        $usd_total_money = isset($usd_stats['grand_total_money']) ? round($usd_stats['grand_total_money'] / 100, 2) : 0;
        $usd_total_count = isset($usd_stats['grand_total_count']) ? $usd_stats['grand_total_count'] : 0;
        $grand_total_count = $cny_total_count + $usd_total_count;

        // 获取按标题归类的统计数据（含pay_info_id用于区分货币）
        $title_stats_raw = DB::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                'title',
                'money',
                'pay_info_id'
            ])
            ->select();

        // PHP层面按货币类型分组统计
        $title_stats_cny = [];
        $title_stats_usd = [];
        foreach ($title_stats_raw as $row) {
            $clean_title = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $row['title']));
            $is_usd = !empty($usd_pay_info_ids) && in_array($row['pay_info_id'], $usd_pay_info_ids);

            if ($is_usd) {
                if (!isset($title_stats_usd[$clean_title])) {
                    $title_stats_usd[$clean_title] = ['title' => $clean_title, 'total_money' => 0, 'total_count' => 0];
                }
                $title_stats_usd[$clean_title]['total_money'] += $row['money'];
                $title_stats_usd[$clean_title]['total_count'] += 1;
            } else {
                if (!isset($title_stats_cny[$clean_title])) {
                    $title_stats_cny[$clean_title] = ['title' => $clean_title, 'total_money' => 0, 'total_count' => 0];
                }
                $title_stats_cny[$clean_title]['total_money'] += $row['money'];
                $title_stats_cny[$clean_title]['total_count'] += 1;
            }
        }

        // CNY 排序并转换金额
        $title_stats_cny = array_values($title_stats_cny);
        usort($title_stats_cny, function($a, $b) { return $b['total_money'] - $a['total_money']; });
        foreach ($title_stats_cny as &$stat) {
            $stat['total_money_yuan'] = round($stat['total_money'] / 100, 2);
        }
        unset($stat);

        // USD 排序并转换金额
        $title_stats_usd = array_values($title_stats_usd);
        usort($title_stats_usd, function($a, $b) { return $b['total_money'] - $a['total_money']; });
        foreach ($title_stats_usd as &$stat) {
            $stat['total_money_yuan'] = round($stat['total_money'] / 100, 2);
        }
        unset($stat);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        
        // 添加总计HTML到内容HTML
        $content_html .= '<div style="padding: 10px; background-color: #f9f9f9; margin-bottom: 10px; border-radius: 5px; text-align: center;">
            <span style="font-size: 16px; font-weight: bold; margin-right: 30px;">时间范围: ' . $display_date . '</span>
            <span style="font-size: 16px; font-weight: bold; margin-right: 30px; color: #67C23A;">CNY充值: ¥' . $cny_total_money . ' (' . $cny_total_count . '笔)</span>';
        if ($usd_total_count > 0) {
            $content_html .= '<span style="font-size: 16px; font-weight: bold; margin-right: 30px; color: #E6A23C;">USD充值: $' . $usd_total_money . ' (' . $usd_total_count . '笔)</span>';
        }
        $content_html .= '<span style="font-size: 16px; font-weight: bold; color: #409EFF;">总订单数: ' . $grand_total_count . ' 笔</span>
        </div>';

        // CNY 按标题归类统计表格
        $content_html .= '<div style="margin-bottom: 20px;">
            <h4 style="margin-bottom: 10px; color: #333;">按标题归类统计 - CNY人民币（支付成功）</h4>
            <table class="table table-striped table-bordered" style="margin-bottom: 0;">
                <thead style="background-color: #f5f5f5;">
                    <tr>
                        <th style="text-align: center; width: 50px;">序号</th>
                        <th style="text-align: center;">订单标题</th>
                        <th style="text-align: center; width: 120px;">总金额（元）</th>
                        <th style="text-align: center; width: 100px;">订单数量</th>
                        <th style="text-align: center; width: 100px;">占比</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (!empty($title_stats_cny)) {
            $index = 1;
            foreach ($title_stats_cny as $stat) {
                $percentage = $cny_total_money > 0 ? round(($stat['total_money_yuan'] / $cny_total_money) * 100, 2) : 0;
                $content_html .= '<tr>
                    <td style="text-align: center;">' . $index . '</td>
                    <td style="padding-left: 10px;">' . htmlspecialchars($stat['title']) . '</td>
                    <td style="text-align: right; padding-right: 10px; color: #67C23A; font-weight: bold;">' . $stat['total_money_yuan'] . '</td>
                    <td style="text-align: center; color: #409EFF; font-weight: bold;">' . $stat['total_count'] . '</td>
                    <td style="text-align: center;">' . $percentage . '%</td>
                </tr>';
                $index++;
            }
        } else {
            $content_html .= '<tr><td colspan="5" style="text-align: center; color: #999;">暂无数据</td></tr>';
        }
        
        $content_html .= '</tbody>
            </table>
        </div>';

        // USD 按标题归类统计表格（仅有 USD 订单时显示）
        if (!empty($title_stats_usd)) {
            $content_html .= '<div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; color: #E6A23C;">按标题归类统计 - USD美元（支付成功）</h4>
                <table class="table table-striped table-bordered" style="margin-bottom: 0;">
                    <thead style="background-color: #fdf6ec;">
                        <tr>
                            <th style="text-align: center; width: 50px;">序号</th>
                            <th style="text-align: center;">订单标题</th>
                            <th style="text-align: center; width: 120px;">总金额（美元）</th>
                            <th style="text-align: center; width: 100px;">订单数量</th>
                            <th style="text-align: center; width: 100px;">占比</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $index = 1;
            foreach ($title_stats_usd as $stat) {
                $percentage = $usd_total_money > 0 ? round(($stat['total_money_yuan'] / $usd_total_money) * 100, 2) : 0;
                $content_html .= '<tr>
                    <td style="text-align: center;">' . $index . '</td>
                    <td style="padding-left: 10px;">' . htmlspecialchars($stat['title']) . '</td>
                    <td style="text-align: right; padding-right: 10px; color: #E6A23C; font-weight: bold;">$' . $stat['total_money_yuan'] . '</td>
                    <td style="text-align: center; color: #409EFF; font-weight: bold;">' . $stat['total_count'] . '</td>
                    <td style="text-align: center;">' . $percentage . '%</td>
                </tr>';
                $index++;
            }
            
            $content_html .= '</tbody>
                </table>
            </div>';
        }

        cookie('ts_pay_order_info', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/PayInfoModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['time', '创建时间'],
                    ['user_id','用户id'],
                    ['from_user_id','来源'],
                    ['from_user_id_u','上级用户'],
                    ['pay_info_id','支付模版ID'],
                    ['out_trade_no', '订单号'],
                    ['money', '支付金额(分)'],
                    ['coin', '点券'],
                    ['title', '标题'],
                    ['status', '状态','status','',[0=>'创建订单',2=>'支付成功',404=>'支付失败']],
                    ['pay_type', '类型','status','',[0=>'未定义',1=>'微信',2=>'支付宝',3=>'stripe付款',4=>'月卡激活',5=>'年卡激活',6=>'积分激活']],
                    
                    // ['channel_name','渠道'],
                    // ['source_name','推广标识'],
                    
                    
            ])
           
            ->setSearchArea([  
                ['text', 'user_id', '用户id'],
                ['daterange', 'a.time', '时间'],   
                ['select', 'status', '支付状态', '', '', ['0' => '创建订单','2' => '支付成功','404' => '支付失败']],
                ['select', 'pay_type', '支付类型', '', '', ['0' => '未定义','1' => '微信','2' => '支付宝','3' => 'stripe付款','4' => '月卡激活','5' => '年卡激活','6' => '积分激活']],
            
                ['text', 'out_trade_no', '订单号'],
                ['text', 'pay_info_id', '支付模版ID'],
                 ['text', 'u.from_user_id', '上级用户'],
                
               
            ])
           
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'a.time=') === 0) {
                    $daterangeValue = substr($param, strlen('a.time='));
                    break;
                }
            }
        }

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
        $groupBy = $isSameDay ? 'HOUR(time)' : 'DATE(time)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        // 获取所有 USD 套餐模版ID，图表仅统计 CNY 订单
        $usd_pay_info_ids = DB::connect('translate')->table('ts_pay_info')
            ->where('pay_type', 11)
            ->column('id');

        $chart_query = DB::connect('translate')->table('ts_pay_order_info')
            ->where('status', 2)
            ->whereTime('time', 'between', [$startDate, $endDate]);
        if (!empty($usd_pay_info_ids)) {
            $chart_query->whereNotIn('pay_info_id', $usd_pay_info_ids);
        }
        $data_success = $chart_query->field([
                "{$groupBy} as axisValue",
                'SUM(money) as total_money',
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 处理数据
        $x_data = array();
        $y_data_money = array();
        $y_data_count = array();

        foreach ($data_success as $value) {
            array_push($x_data, $value['axisValue']);
            // 转换为元
            array_push($y_data_money, round($value['total_money'] / 100, 2));
            array_push($y_data_count, $value['count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_money_json = json_encode($y_data_money);
        $y_data_count_json = json_encode($y_data_count);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'CNY充值成功金额{$xAxisType}统计（不含USD）'
            },
            tooltip: {
                trigger: 'axis',
                formatter: function(params) {
                    var result = params[0].name + '<br/>';
                    params.forEach(function(param) {
                        var value = param.value;
                        var color = param.color;
                        var seriesName = param.seriesName;
                        var marker = '<span style=\"display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background-color:' + color + ';\"></span>';
                        if (seriesName === '充值金额(元)') {
                            result += marker + seriesName + ': ' + value + '元<br/>';
                        } else {
                            result += marker + seriesName + ': ' + value + '<br/>';
                        }
                    });
                    return result;
                }
            },
            legend: {
                data: ['充值金额(元)', '充值订单数']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            toolbox: {
                feature: {
                    saveAsImage: {}
                }
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: {$x_data_json}
            },
            yAxis: [
                {
                    type: 'value',
                    name: '金额(元)',
                    position: 'left'
                },
                {
                    type: 'value',
                    name: '订单数',
                    position: 'right'
                }
            ],
            series: [
                {
                    name: '充值金额(元)',
                    type: 'line',
                    data: {$y_data_money_json},
                    label: {
                        show: true,
                        position: 'top',
                        formatter: '{c}元'
                    },
                    itemStyle: {
                        color: '#67C23A'
                    }
                },
                {
                    name: '充值订单数',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#409EFF'
                    },
                    barWidth: '10%'
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }



}
