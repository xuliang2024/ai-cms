<?php
// 迁移任务列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\MigrationTaskModel;

class MigrationTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = MigrationTaskModel::where($map)
        ->order('id desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_migration_task', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/MigrationTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                
                ['space_id', '目标空间ID'],
                ['migration_type', '迁移类型', 'callback', function($value) {
                    $types = [0 => '工作流', 1 => '智能体'];
                    return isset($types[$value]) ? $types[$value] : '未知';
                }],
                ['bot_id', '智能体ID'],
                ['bot_name', '智能体名称'],
                ['bot_description', '智能体描述'],
                ['workflow_id', '工作流ID'],
                ['status', '状态编辑','text.edit'],
                ['status', '状态', 'callback', function($value) {
                    $status = [0 => '等待迁移', 1 => '操作中', 2 => '迁移完成', 3 => '失败'];
                    return isset($status[$value]) ? $status[$value] : '未知';
                }],
                ['msg', '消息或错误信息', 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 40, '...');
                }],
                ['time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text','a.id','ID'],
                ['text', 'user_id', '用户ID'],
                ['text', 'space_id', '目标空间ID'],
                ['select', 'migration_type', '迁移类型', '', '', [
                    0 => '工作流',
                    1 => '智能体'
                ]],
                ['text', 'bot_id', '智能体ID'],
                ['text', 'workflow_id', '工作流ID'],
                ['select', 'status', '状态', '', '', [
                    0 => '等待迁移',
                    1 => '操作中',
                    2 => '迁移完成',
                    3 => '失败'
                ]],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
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

        // 获取总的迁移任务数据
        $data_list = MigrationTaskModel::whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取成功的任务数据 (status = 2 表示迁移完成)
        $data_success = MigrationTaskModel::where('status', 2)
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取失败的任务数据 (status = 3 表示失败)
        $data_failed = MigrationTaskModel::where('status', 3)
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 处理数据
        $x_data = array();
        $y_data_total = array();
        $y_data_success = array();
        $y_data_failed = array();

        // 创建索引数组
        $success_data = array();
        $failed_data = array();
        
        foreach ($data_success as $value) {
            $success_data[$value['axisValue']] = $value['count'];
        }
        
        foreach ($data_failed as $value) {
            $failed_data[$value['axisValue']] = $value['count'];
        }

        foreach ($data_list as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['count']);
            
            // 如果在成功任务数据中存在，添加对应值，否则添加0
            if (isset($success_data[$value['axisValue']])) {
                array_push($y_data_success, $success_data[$value['axisValue']]);
            } else {
                array_push($y_data_success, 0);
            }
            
            // 如果在失败任务数据中存在，添加对应值，否则添加0
            if (isset($failed_data[$value['axisValue']])) {
                array_push($y_data_failed, $failed_data[$value['axisValue']]);
            } else {
                array_push($y_data_failed, 0);
            }
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_success_json = json_encode($y_data_success);
        $y_data_failed_json = json_encode($y_data_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '迁移任务{$xAxisType}统计 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '成功的任务数', '失败的任务数']
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
                    name: '总任务数',
                    type: 'line',
                    data: {$y_data_total_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '成功的任务数',
                    type: 'line',
                    data: {$y_data_success_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#67C23A'
                    }
                },
                {
                    name: '失败的任务数',
                    type: 'line',
                    data: {$y_data_failed_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#F56C6C'
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }
    
    /**
     * 查询迁移任务详情
     * @param int $id 记录ID
     * @return mixed
     */
    public function viewMigrationDetail($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取迁移任务记录信息
        $migration = MigrationTaskModel::get($id);
        if (!$migration) {
            return $this->error('迁移任务记录不存在');
        }
        
        // 这里可以添加查询迁移任务详情的逻辑
        // 例如调用API或者其他操作
        
        return $this->success('查询成功');
    }
} 