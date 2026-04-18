<?php
// 机器人列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\BotListModel;

class BotList extends Admin {
    
    public function index() 
    {
        // 获取搜索参数并处理特殊字符
        $search_params = $this->request->get();
        
        // 对包含特殊字符的搜索值进行URL编码处理
        if (!empty($search_params['bot_name']) && strpos($search_params['bot_name'], '|') !== false) {
            // 临时替换特殊字符
            $original_bot_name = $search_params['bot_name'];
            $encoded_bot_name = str_replace('|', '___PIPE___', $original_bot_name);
            
            // 修改请求参数
            $_GET['bot_name'] = $encoded_bot_name;
            $this->request = request()->withGet($_GET);
        }
        
        $map = $this->getMap();
        
        // 如果有编码的机器人名称，需要还原并重新处理
        if (!empty($search_params['bot_name']) && strpos($search_params['bot_name'], '|') !== false) {
            // 移除编码后的条件
            foreach ($map as $key => $condition) {
                if (is_array($condition) && isset($condition[0]) && $condition[0] == 'bot_name') {
                    unset($map[$key]);
                }
            }
            // 添加原始搜索条件
            $map[] = ['bot_name', 'like', '%' . $search_params['bot_name'] . '%'];
        }
        
        // 使用动态指定的数据库连接进行查询，连表查询获取作者名称
        $data_list = Db::connect('translate')->table('ts_bot_list')
        ->alias('b')
        ->leftJoin('ts_users u', 'b.user_id = u.id')
        ->field('b.*, u.name as author_name')
        ->where($map)
        ->order('b.time desc')
        ->paginate();

        cookie('ts_bot_list', $map);
        
        // 获取图表数据
        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        // 统计数据（随当前筛选条件变化）
        $stats_total = BotListModel::where($map)->count();
        $stats_total_money_cents = (float) BotListModel::where($map)->sum('money');
        // 换算为元并四舍五入，保留两位小数
        $stats_total_money = number_format(round($stats_total_money_cents / 100, 2), 2, '.', '');

        // 构建筛选统计表格，放在图表下方
        $stats_html = '<div class="panel panel-default" style="margin-top:10px;">'
            . '<div class="panel-heading">筛选统计</div>'
            . '<div class="panel-body" style="padding:0;">'
            . '<table class="table table-bordered table-striped" style="margin:0;">'
            . '<thead><tr><th>智能体数量</th><th>收费总金额(元)</th></tr></thead>'
            . '<tbody><tr><td>' . $stats_total . '</td><td>' . $stats_total_money . '</td></tr></tbody>'
            . '</table>'
            . '</div>'
            . '</div>';

        $content_html .= $stats_html;
        
        return ZBuilder::make('table')
            ->setTableName('video/BotListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['author_name', '作者名称'],
                ['bot_id', '机器人ID'],
                ['bot_name', '机器人名称'],
                ['bot_description', '机器人描述', 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['icon_url', '图标URL', 'img_url'],
                ['level','等级' ,'switch'],
                ['is_offical','是否官方','switch'],
                ['category','分类','text.edit'],
                ['money' ,'收费金额' ,'text.edit'],
                ['error_msg', '审核失败原因', 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['sort', '排序','text.edit'],
                ['user_id', '用户','text.edit'],
                ['status','状态' ,'callback', function($status) {
                    $status_map = [
                        0 => '<span class="label label-info">创建中</span>',
                        1 => '<span class="label label-success">已上架</span>',
                        2 => '<span class="label label-warning">审核失败</span>',
                        3 => '<span class="label label-primary">审核中</span>',
                        404 => '<span class="label label-danger">已删除</span>'
                    ];
                    return isset($status_map[$status]) ? $status_map[$status] : '<span class="label label-default">未知</span>';
                }],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'bot_id', '机器人id'],
                ['text', 'bot_name', '机器人名称'],
                ['text', 'category', '分类'],
                ['select', 'level', '收费类型', '', '', [
                    '' => '全部类型',
                    0 => '免费',
                    1 => '收费'
                ]],
                ['select', 'status', '状态', '', '', [
                    '' => '全部状态',
                    0 => '创建中',
                    1 => '已上架', 
                    2 => '审核失败',
                    3 => '审核中',
                    404 => '已删除'
                ]],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->addRightButton('custom', [
                'title' => '查看工作流',
                'icon' => 'fa fa-cogs',
                'class' => 'btn btn-xs btn-default',
                'href' => 'https://ai-cms.fyshark.com/admin.php/video/workflow_list/index.html?_s=workflow_id=|workflow_name=|bot_id=__bot_id__|bot_name=|status=|create_time=&_o=workflow_id=eq|workflow_name=eq|bot_id=eq|bot_name=eq|status=eq|create_time=between%20time',
                'target' => '_blank'
            ]) // 添加查看工作流按钮
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs('
                <script>
                function searchCases(botId) {
                    if (!botId) {
                        alert("机器人ID为空");
                        return;
                    }
                    
                    // 发送AJAX请求获取案例数量
                    $.ajax({
                        url: "' . url('get_case_count') . '",
                        type: "POST",
                        data: {bot_id: botId},
                        dataType: "json",
                        success: function(response) {
                            if (response.code == 1) {
                                alert("机器人ID: " + botId + "\\n案例数量: " + response.data.count + " 个\\n启用案例: " + response.data.enabled_count + " 个");
                            } else {
                                alert("查询失败: " + response.msg);
                            }
                        },
                        error: function() {
                            alert("查询失败，请稍后重试");
                        }
                    });
                }
                </script>
                ' . $js)
            ->fetch(); // 渲染页面
    }
    
    /**
     * 获取案例数量
     */
    public function get_case_count()
    {
        if ($this->request->isPost()) {
            $bot_id = $this->request->post('bot_id', '');
            
            if (empty($bot_id)) {
                return json(['code' => 0, 'msg' => '机器人ID不能为空']);
            }
            
            try {
                // 查询总案例数
                $total_count = \app\video\model\BotCaseListModel::where('bot_id', $bot_id)->count();
                
                // 查询启用案例数
                $enabled_count = \app\video\model\BotCaseListModel::where('bot_id', $bot_id)
                    ->where('status', 1)
                    ->count();
                
                return json([
                    'code' => 1, 
                    'msg' => '查询成功',
                    'data' => [
                        'count' => $total_count,
                        'enabled_count' => $enabled_count
                    ]
                ]);
                
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '查询失败: ' . $e->getMessage()]);
            }
        }
        
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            // 执行更新
            $result = BotListModel::where('id', $id)->update($data);
            
            if ($result !== false) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = BotListModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 状态选项
        $status_options = [
            0 => '创建中',
            1 => '已上架', 
            2 => '审核失败',
            3 => '审核中'
        ];

        // 构建表单项
        $form_items = [
            ['hidden', 'id'], // 添加隐藏的id字段
            ['static', 'bot_name', '智能体名称'],
            ['static', 'bot_description', '智能体描述'],  
            ['select', 'status', '状态', '选择智能体状态', $status_options, 'required'],
            ['text', 'bot_url', 'Bot URL', '机器人接口地址'],
            ['textarea', 'error_msg', '审核失败原因', '如果选择"审核失败"状态，请填写失败原因'],
        ];

        

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑智能体 - ' . $info['bot_name']) // 设置页面标题
            ->addFormItems($form_items) // 添加表单项
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
    

    
    /**
     * 获取图表JS代码
     */
    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'time=') === 0) {
                    $daterangeValue = substr($param, strlen('time='));
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

        // 获取总数据按时间分组
        $data_list_time = BotListModel::whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select()
        ->toArray();

        $x_data = array();
        $y_data_time = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);

        // 获取审核中(status=3)按时间分组的数据
        $audit_data_list = BotListModel::whereTime('time', 'between', [$startDate, $endDate])
        ->where('status', 3)
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select()
        ->toArray();

        // 获取已上架(status=1)按时间分组的数据
        $online_data_list = BotListModel::whereTime('time', 'between', [$startDate, $endDate])
        ->where('status', 1)
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select()
        ->toArray();

        // 获取审核失败(status=2)按时间分组的数据
        $failed_data_list = BotListModel::whereTime('time', 'between', [$startDate, $endDate])
        ->where('status', 2)
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select()
        ->toArray();

        // 创建状态数据数组，确保与x轴数据对应
        $y_data_audit = array();
        $y_data_online = array();
        $y_data_failed = array();

        // 将状态数据转换为关联数组便于查找
        $audit_map = array();
        foreach ($audit_data_list as $item) {
            $audit_map[$item['axisValue']] = $item['count'];
        }
        $online_map = array();
        foreach ($online_data_list as $item) {
            $online_map[$item['axisValue']] = $item['count'];
        }
        $failed_map = array();
        foreach ($failed_data_list as $item) {
            $failed_map[$item['axisValue']] = $item['count'];
        }

        // 按x轴数据填充状态数据，没有数据的时间点填充0
        foreach ($x_data as $axisValue) {
            array_push($y_data_audit, isset($audit_map[$axisValue]) ? $audit_map[$axisValue] : 0);
            array_push($y_data_online, isset($online_map[$axisValue]) ? $online_map[$axisValue] : 0);
            array_push($y_data_failed, isset($failed_map[$axisValue]) ? $failed_map[$axisValue] : 0);
        }

        $y_data_audit_json = json_encode($y_data_audit);
        $y_data_online_json = json_encode($y_data_online);
        $y_data_failed_json = json_encode($y_data_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '智能体{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总智能体数', '提交审核', '已上架', '审核失败']
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
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: '总智能体数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    lineStyle: {
                        color: '#5470c6'
                    }
                },
                {
                    name: '提交审核',
                    type: 'line',
                    data: {$y_data_audit_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    lineStyle: {
                        color: '#91cc75'
                    }
                },
                {
                    name: '已上架',
                    type: 'line',
                    data: {$y_data_online_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    lineStyle: {
                        color: '#fac858'
                    }
                },
                {
                    name: '审核失败',
                    type: 'line',
                    data: {$y_data_failed_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    lineStyle: {
                        color: '#ee6666'
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }

    /**
     * 创建测试数据 (仅用于测试审核功能)
     */
    public function create_test_data()
    {
        try {
            // 创建一个状态为审核中(3)的测试记录
            $test_data = [
                'bot_id' => 'test_bot_' . time(),
                'space_id' => 'test_space_' . time(),
                'bot_name' => '测试智能体_' . date('Y-m-d H:i:s'),
                'bot_description' => '这是一个用于测试审核功能的智能体',
                'icon_url' => 'https://example.com/icon.png',
                'level' => 1,
                'is_offical' => 0,
                'money' => 0,
                'sort' => 0,
                'user_id' => 1,
                'status' => 3, // 设置为审核中状态
                'time' => date('Y-m-d H:i:s'),
                'bot_url' => 'https://example.com/bot'
            ];
            
            $result = BotListModel::create($test_data);
            
            if ($result) {
                $this->success('测试数据创建成功，ID：' . $result->id . '，现在可以测试审核功能了', 'index');
            } else {
                $this->error('测试数据创建失败');
            }
        } catch (\Exception $e) {
            $this->error('创建测试数据失败：' . $e->getMessage());
        }
    }
    
} 