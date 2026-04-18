<?php
// 用户智能体解锁管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\UserBotUnlockModel;

class UserBotUnlock extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        
        // 使用动态指定的数据库连接进行查询
        $data_list = UserBotUnlockModel::where($map)
        ->order('unlock_time desc')
        ->paginate();

        cookie('ts_user_bot_unlock', $map);
        
        // 获取ECharts图表JS
        $js = $this->getChartjs();
        $content_html = "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        // 获取查询参数
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'unlock_time=') === 0) {
                    $daterangeValue = substr($param, strlen('unlock_time='));
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

        // 获取总金额和总订单数
        $total_stats = UserBotUnlockModel::where('status', 1)
            ->whereTime('unlock_time', 'between', [$startDate, $endDate])
            ->field([
                'SUM(unlock_price) as grand_total_money',
                'COUNT(*) as grand_total_count'
            ])
            ->find();
        
        $grand_total_money = isset($total_stats['grand_total_money']) ? round($total_stats['grand_total_money'] / 100, 2) : 0;
        $grand_total_count = isset($total_stats['grand_total_count']) ? $total_stats['grand_total_count'] : 0;

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        
        // 添加时间筛选按钮
        $content_html .= '<div style="margin-bottom: 15px; text-align: center;">
            <div class="btn-group" role="group" style="margin-bottom: 10px;">
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'today\')">今天</button>
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'yesterday\')">昨天</button>
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'this_week\')">本周</button>
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'last_week\')">上周</button>
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'this_month\')">本月</button>
                <button type="button" class="btn btn-sm btn-default" onclick="filterByTime(\'last_month\')">上月</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="filterByTime(\'all\')">全部</button>
            </div>
        </div>';

        // 添加总计HTML到内容HTML
        $content_html .= '<div style="padding: 10px; background-color: #f9f9f9; margin-bottom: 10px; border-radius: 5px; text-align: center;">
            <span style="font-size: 16px; font-weight: bold; margin-right: 30px;">时间范围: ' . $display_date . '</span>
            <span style="font-size: 16px; font-weight: bold; margin-right: 30px; color: #67C23A;">总解锁金额: ' . $grand_total_money . ' 元</span>
            <span style="font-size: 16px; font-weight: bold; color: #409EFF;">总解锁数: ' . $grand_total_count . ' 次</span>
        </div>';
        
        return ZBuilder::make('table')
            ->setTableName('ai/UserBotUnlockModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['create_user_id', '创建人ID'],
                ['platform_fee','平台手续费'],
                ['settlement_amount','结算金额'],
                ['bot_id', '智能体ID'],
                ['unlock_price', '解锁价格(分)'],
                ['unlock_time', '解锁时间'],
                ['settlement_status','状态'    ,'status' , '' , [0 => '冻结', 1 => '已结算', 2 => '退款'] ],
                ['status', '状态', 'status', '', [0 => '无效', 1 => '有效']],
                ['is_refunded','退款状态' ,'status', '' , [0 => '未退款', 1 => '已退款'] ],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'bot_id', '智能体ID'],
                ['text', 'create_user_id', '创建人ID'],
                ['select', 'status', '状态', '', '', [
                    '' => '全部状态',
                    0 => '无效', 
                    1 => '有效'
                ]],
                ['daterange', 'unlock_time', '解锁时间', '', '', ['format' => 'YYYY-MM-DD']],

            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add', ['title'=>'新增']) // 添加新增按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }
    
    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            // 验证数据
            if (empty($data['user_id'])) {
                $this->error('用户ID不能为空');
            }
            
            if (empty($data['bot_id'])) {
                $this->error('智能体ID不能为空');
            }
            
            // 设置默认解锁时间
            if (empty($data['unlock_time'])) {
                $data['unlock_time'] = date('Y-m-d H:i:s');
            }
            
            // 设置默认价格
            if (!isset($data['unlock_price'])) {
                $data['unlock_price'] = 0;
            }
            
            // 设置默认状态
            if (!isset($data['status'])) {
                $data['status'] = 1;
            }
            
            $r = UserBotUnlockModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增智能体解锁记录')
            ->addFormItems([
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 0],
                ['text', 'bot_id', '智能体ID', '请输入智能体ID'],
                ['number', 'unlock_price', '解锁价格(分)', '请输入解锁价格，单位：分', '', 0],
                ['datetime', 'unlock_time', '解锁时间', '选择解锁时间', '', date('Y-m-d H:i:s')],
                ['radio', 'status', '状态', '', [1 => '有效', 0 => '无效'], 1],
            ])
            ->fetch();
    }
    
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证数据 - 编辑时这些字段应该已存在，如果为空则从原记录获取
            $originalInfo = UserBotUnlockModel::where('id', $id)->find();
            if (!$originalInfo) {
                $this->error('记录不存在');
            }
            
            // 确保关键字段不为空
            if (empty($data['user_id'])) {
                $data['user_id'] = $originalInfo['user_id'];
            }
            
            if (empty($data['bot_id'])) {
                $data['bot_id'] = $originalInfo['bot_id'];
            }
            
            // 验证解锁时间格式
            if (!empty($data['unlock_time'])) {
                $timestamp = strtotime($data['unlock_time']);
                if ($timestamp === false) {
                    $this->error('解锁时间格式不正确');
                }
            }
            
            // 更新数据
            $r = UserBotUnlockModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserBotUnlockModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑智能体解锁记录') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['hidden', 'user_id'], // 添加隐藏的user_id字段
                ['hidden', 'bot_id'], // 添加隐藏的bot_id字段
                ['static', 'user_id_display', '用户ID', '', $info['user_id']], // 显示用户ID
                ['static', 'bot_id_display', '智能体ID', '', $info['bot_id']], // 显示智能体ID
                ['static', 'unlock_price_display', '解锁价格(分)', '', $info['unlock_price']], // 只读显示解锁价格
                ['static', 'status_display', '状态', '', $info['status'] == 1 ? '有效' : '无效'], // 只读显示状态
                ['datetime', 'unlock_time', '解锁时间', '选择解锁时间'],
                ['select', 'settlement_status', '退款状态', '', [
                    2 => '退款'
                    
                ]],
                
                ['textarea', 'error_msg', '错误信息', '如有错误信息请填写'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'unlock_time=') === 0) {
                    $daterangeValue = substr($param, strlen('unlock_time='));
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
        $groupBy = $isSameDay ? 'HOUR(unlock_time)' : 'DATE(unlock_time)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        // 获取解锁成功的总金额数据 (status = 1 表示有效)
        $data_success = UserBotUnlockModel::where('status', 1)
            ->whereTime('unlock_time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'SUM(unlock_price) as total_money',
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
                text: '解锁成功金额{$xAxisType}统计'
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
                        if (seriesName === '解锁金额(元)') {
                            result += marker + seriesName + ': ' + value + '元<br/>';
                        } else {
                            result += marker + seriesName + ': ' + value + '<br/>';
                        }
                    });
                    return result;
                }
            },
            legend: {
                data: ['解锁金额(元)', '解锁订单数']
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
                    name: '解锁金额(元)',
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
                    name: '解锁订单数',
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
        
        // 时间筛选功能
        function filterByTime(timeType) {
            var today = new Date();
            var startDate, endDate;
            
            switch(timeType) {
                case 'today':
                    startDate = endDate = formatDate(today);
                    break;
                case 'yesterday':
                    var yesterday = new Date(today);
                    yesterday.setDate(today.getDate() - 1);
                    startDate = endDate = formatDate(yesterday);
                    break;
                case 'this_week':
                    var startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay() + 1); // 周一
                    var endOfWeek = new Date(startOfWeek);
                    endOfWeek.setDate(startOfWeek.getDate() + 6); // 周日
                    startDate = formatDate(startOfWeek);
                    endDate = formatDate(endOfWeek);
                    break;
                case 'last_week':
                    var lastWeekStart = new Date(today);
                    lastWeekStart.setDate(today.getDate() - today.getDay() - 6); // 上周一
                    var lastWeekEnd = new Date(lastWeekStart);
                    lastWeekEnd.setDate(lastWeekStart.getDate() + 6); // 上周日
                    startDate = formatDate(lastWeekStart);
                    endDate = formatDate(lastWeekEnd);
                    break;
                case 'this_month':
                    startDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    endDate = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
                    break;
                case 'last_month':
                    startDate = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                    endDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
                    break;
                case 'all':
                    // 清除时间筛选
                    var currentUrl = window.location.href;
                    var baseUrl = currentUrl.split('?')[0];
                    window.location.href = baseUrl;
                    return;
            }
            
            // 构建URL参数
            var dateRange = startDate + ' - ' + endDate;
            var currentUrl = window.location.href;
            var baseUrl = currentUrl.split('?')[0];
            
            // 构建新的查询参数
            var newUrl = baseUrl + '?_s=unlock_time=' + encodeURIComponent(dateRange) + '&_o=unlock_time=between%20time';
            window.location.href = newUrl;
        }
        
        function formatDate(date) {
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        }
        </script>";

        return $js;
    }

    /**
     * 删除记录（支持单条与批量）
     */
    public function delete($ids = null)
    {
        // 优先从POST中获取批量ids
        if ($this->request->isPost()) {
            $postIds = $this->request->post('ids/a');
            if (!empty($postIds)) {
                $ids = $postIds;
            }
        }
        // 兼容从参数获取单个或逗号分隔的ids
        if (empty($ids)) {
            $ids = $this->request->param('ids');
        }
        if (empty($ids)) {
            $this->error('参数错误：缺少要删除的ID');
        }
        if (!is_array($ids)) {
            $ids = is_string($ids) ? explode(',', $ids) : [$ids];
        }
        try {
            $result = UserBotUnlockModel::destroy($ids);
        } catch (\Exception $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
        if ($result === false) {
            $this->error('删除失败');
        }
        $this->success('删除成功', 'index');
    }
}