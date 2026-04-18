<?php
// 充值消耗表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use think\facade\Log;
use app\video\model\FinancialTransactionsModel;

class FinancialTransactions extends Admin {

    
    public function index() 
    {
     

        $map = $this->getMap();
        
        // 检查是否需要筛选正金额
        if (input('positive_money') == 1) {
            $map[] = ['money', '>', 0];
        }
        
        // 使用动态指定的数据库连接进行查询
        $data_list = FinancialTransactionsModel::where($map)
        ->alias('a')
        ->Join('ts_users u','a.user_id=u.id')
         ->field('a.*,u.from_user_id as from_user_id_u')

        ->order('time desc')
        ->paginate();

        cookie('ts_financial_transactions', $map);
        
        // 获取图表JavaScript代码
        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }
        
        
        return ZBuilder::make('table')
            ->setTableName('video/FinancialTransactionsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['from_user_id_u','上级用户'],
                ['money', '金额(分)','text.edit'],
                ['order_id', '订单id'],
                ['title', 'title'],

                ['deployment_uuid', '容器uuid'],
                ['transaction_type', '交易类型'],

                ['is_cash', '现金'],

                
                ['time', '创建时间'],
                
            ])
            ->addFilter('money') // 添加金额字段的表头筛选
            // ->addFilterMap('money', ['money' => ['>', 0]]) // 设置筛选条件为金额大于0
            ->setSearchArea([  
                // ['daterange', 'a.time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'a.time', '创建时间', '', '', ['format' => 'YYYY-MM-DD HH:mm']],
                ['text', 'a.id', 'id'],
                ['text', 'user_id', '用户ID'],
                ['text', 'u.from_user_id', '上级用户'],
                ['text', 'order_id', 'order_id'],
                ['text', 'title', '标题'],
                ['text', 'is_cash', '现金'],
                ['text', 'deployment_uuid', '容器uuid'],
                ['text', 'transaction_type', '交易类型'],
                ['text', 'money', '金额'],
              
            ])
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add',['title'=>'新增']) // 批量添加顶部按钮
            ->addTopButton('custom', [
                'title' => '显示正金额',
                'icon' => 'fa fa-rmb',
                'href' => url('index', ['positive_money' => 1]),
                'class' => 'btn btn-success'
            ]) // 添加筛选正金额的按钮
            ->addTopButton('download', [
                'title' => '导出Excel',
                'class' => 'btn btn-primary js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/financial_transactions/export_excel.html?' . $this->request->query()
            ]) // 添加导出Excel按钮
            
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min") // 加载echarts库
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top') // 添加图表HTML容器
            ->setExtraJs($js) // 添加图表JavaScript代码
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

        // 构建查询条件
        $map = [];
        if (input('positive_money') == 1) {
            $map[] = ['money', '>', 0];
        }

        $data_list_time = FinancialTransactionsModel::where($map)
        ->whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(money) as total_money',
            'SUM(CASE WHEN money > 0 THEN money ELSE 0 END) as positive_money',
            'SUM(CASE WHEN money < 0 THEN money ELSE 0 END) as negative_money',
            'SUM(CASE WHEN money > 0 THEN 1 ELSE 0 END) as positive_count',
            'SUM(CASE WHEN money < 0 THEN 1 ELSE 0 END) as negative_count'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_count = array();
        $y_data_total_money = array();
        $y_data_positive_money = array();
        $y_data_negative_money = array();
        $y_data_positive_count = array();
        $y_data_negative_count = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_count, $value['count']);
            array_push($y_data_total_money, round($value['total_money']/100, 2)); // 转换为元
            array_push($y_data_positive_money, round($value['positive_money']/100, 2));
            array_push($y_data_negative_money, round($value['negative_money']/100, 2));
            array_push($y_data_positive_count, $value['positive_count']);
            array_push($y_data_negative_count, $value['negative_count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_count_json = json_encode($y_data_count);
        $y_data_total_money_json = json_encode($y_data_total_money);
        $y_data_positive_money_json = json_encode($y_data_positive_money);
        $y_data_negative_money_json = json_encode($y_data_negative_money);
        $y_data_positive_count_json = json_encode($y_data_positive_count);
        $y_data_negative_count_json = json_encode($y_data_negative_count);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '财务交易{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['交易笔数', '总金额(元)', '正金额(元)', '负金额(元)', '正金额笔数', '负金额笔数']
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
                    name: '笔数',
                    position: 'left'
                },
                {
                    type: 'value',
                    name: '金额(元)',
                    position: 'right'
                }
            ],
            series: [
                {
                    name: '交易笔数',
                    type: 'line',
                    yAxisIndex: 0,
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '总金额(元)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_total_money_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '正金额(元)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_positive_money_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '负金额(元)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_negative_money_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '正金额笔数',
                    type: 'line',
                    yAxisIndex: 0,
                    data: {$y_data_positive_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '负金额笔数',
                    type: 'line',
                    yAxisIndex: 0,
                    data: {$y_data_negative_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $r = FinancialTransactionsModel::insert($data);
            if ($r) {
                $this->updateUserCashBalance($data['user_id'] ?? null, floatval($data['money'] ?? 0));
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', 'user_id'],
                ['text', 'money', '金额(分)'],
                ['text','order_id', '订单id'],
                ['text','title', 'title','','佣金补偿'],
                ['text','transaction_type', '交易类型','','0'],
            ])
            ->fetch();
    }

    public function quickEdit($record = [])
    {
        $id = input('post.pk', '');
        $field = input('post.name', '');
        $newValue = input('post.value', '');

        $oldMoney = 0;
        if ($field === 'money' && $id) {
            $oldMoney = floatval(FinancialTransactionsModel::where('id', $id)->value('money'));
        }

        parent::quickEdit($record);

        if ($field === 'money' && $id) {
            $diff = floatval($newValue) - $oldMoney;
            if ($diff != 0) {
                $userId = FinancialTransactionsModel::where('id', $id)->value('user_id');
                $this->updateUserCashBalance($userId, $diff);
            }
        }
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $records = FinancialTransactionsModel::whereIn('id', $ids)->select();
        $userMoneyMap = [];
        foreach ($records as $record) {
            $uid = $record['user_id'];
            if (!empty($uid)) {
                $userMoneyMap[$uid] = ($userMoneyMap[$uid] ?? 0) + floatval($record['money']);
            }
        }

        $result = FinancialTransactionsModel::whereIn('id', $ids)->delete();
        if ($result) {
            foreach ($userMoneyMap as $userId => $totalMoney) {
                $this->updateUserCashBalance($userId, -$totalMoney);
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 直接更新用户的 cash_balance 和 balance
     */
    protected function updateUserCashBalance($userId, $amount)
    {
        if (empty($userId) || $amount == 0) {
            return;
        }

        try {
            $connection = Db::connect('translate');
            $connection->table('ts_users')->where('id', $userId)->update([
                'cash_balance' => Db::raw('cash_balance + (' . floatval($amount) . ')'),
                'balance' => Db::raw('balance + (' . floatval($amount) . ')')
            ]);
        } catch (\Exception $e) {
            Log::record('[UpdateUserCashBalance] user=' . $userId . ' amount=' . $amount . ' error=' . $e->getMessage(), 'error');
        }
    }

    /**
     * 导出Excel（支持勾选导出与按筛选条件导出）
     */
    public function export_excel()
    {
        // 支持勾选导出：?ids=1,2,3
        $ids = input('get.ids', '', 'trim');
        $map = [];
        if ($ids !== '') {
            $ids_array = array_filter(explode(',', $ids));
            if (!empty($ids_array)) {
                $map[] = ['a.id', 'in', $ids_array];
            }
        } else {
            // 按页面筛选条件导出
            $map = cookie('ts_financial_transactions') ?: [];
        }

        // 查询数据，带上上级用户字段
        $data_list = FinancialTransactionsModel::alias('a')
            ->join('ts_users u','a.user_id=u.id','LEFT')
            ->field('a.*,u.from_user_id as from_user_id_u')
            ->where($map)
            ->order('time desc')
            ->select()
            ->toArray();

        if (empty($data_list)) {
            $this->error('没有数据可以导出');
        }

        // 设置表头（字段名,列宽,标题）
        $cellName = [
            ['id', 10, 'ID'],
            ['user_id', 12, '用户ID'],
            ['from_user_id_u', 14, '上级用户'],
            ['money', 14, '金额(分)'],
            ['order_id', 20, '订单ID'],
            ['title', 24, '标题'],
            ['deployment_uuid', 28, '容器uuid'],
            ['transaction_type', 14, '交易类型'],
            ['is_cash', 10, '现金'],
            ['time', 20, '创建时间'],
        ];

        // 导出
        plugin_action('Excel/Excel/export', ['财务交易_' . date('Y-m-d_H-i-s'), $cellName, $data_list]);
    }

}
