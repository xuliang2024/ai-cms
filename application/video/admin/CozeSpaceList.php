<?php
// 扣子空间列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\CozeSpaceListModel;

class CozeSpaceList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = CozeSpaceListModel::where($map)
        ->order('time desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_coze_space_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CozeSpaceListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['name', '空间名称'],
                ['icon_url', '空间图标' ,'img_url'],
                ['space_id', '扣子空间ID'],
                ['url', '扣子空间URL'],
                ['status', '状态'],
                ['level', '等级'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'space_id', '扣子空间ID'],
                ['text', 'status', '状态'],
                ['text', 'level', '等级'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
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

        // 获取总的空间数据
        $data_list = CozeSpaceListModel::whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取队列中的空间数据
        $data_queue = CozeSpaceListModel::where('status', 'queue')
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取已加入的空间数据
        $data_joined = CozeSpaceListModel::where('status', 'joined')
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取失败的空间数据
        $data_failed = CozeSpaceListModel::where('status', 'failed')
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();

        // 获取删除的空间数据
        $data_delete = CozeSpaceListModel::where('status', 'delete')
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
        $y_data_queue = array();
        $y_data_joined = array();
        $y_data_failed = array();
        $y_data_delete = array();

        // 创建索引数组
        $queue_data = array();
        $joined_data = array();
        $failed_data = array();
        $delete_data = array();
        
        foreach ($data_queue as $value) {
            $queue_data[$value['axisValue']] = $value['count'];
        }
        
        foreach ($data_joined as $value) {
            $joined_data[$value['axisValue']] = $value['count'];
        }
        
        foreach ($data_failed as $value) {
            $failed_data[$value['axisValue']] = $value['count'];
        }
        
        foreach ($data_delete as $value) {
            $delete_data[$value['axisValue']] = $value['count'];
        }

        foreach ($data_list as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['count']);
            
            // 如果在队列中数据中存在，添加对应值，否则添加0
            if (isset($queue_data[$value['axisValue']])) {
                array_push($y_data_queue, $queue_data[$value['axisValue']]);
            } else {
                array_push($y_data_queue, 0);
            }
            
            // 如果在已加入数据中存在，添加对应值，否则添加0
            if (isset($joined_data[$value['axisValue']])) {
                array_push($y_data_joined, $joined_data[$value['axisValue']]);
            } else {
                array_push($y_data_joined, 0);
            }
            
            // 如果在失败数据中存在，添加对应值，否则添加0
            if (isset($failed_data[$value['axisValue']])) {
                array_push($y_data_failed, $failed_data[$value['axisValue']]);
            } else {
                array_push($y_data_failed, 0);
            }
            
            // 如果在删除数据中存在，添加对应值，否则添加0
            if (isset($delete_data[$value['axisValue']])) {
                array_push($y_data_delete, $delete_data[$value['axisValue']]);
            } else {
                array_push($y_data_delete, 0);
            }
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_queue_json = json_encode($y_data_queue);
        $y_data_joined_json = json_encode($y_data_joined);
        $y_data_failed_json = json_encode($y_data_failed);
        $y_data_delete_json = json_encode($y_data_delete);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '扣子空间{$xAxisType}统计 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总空间数', '队列中', '已加入', '失败', '已删除']
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
                    name: '总空间数',
                    type: 'line',
                    data: {$y_data_total_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '队列中',
                    type: 'line',
                    data: {$y_data_queue_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#E6A23C'
                    }
                },
                {
                    name: '已加入',
                    type: 'line',
                    data: {$y_data_joined_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#67C23A'
                    }
                },
                {
                    name: '失败',
                    type: 'line',
                    data: {$y_data_failed_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#F56C6C'
                    }
                },
                {
                    name: '已删除',
                    type: 'line',
                    data: {$y_data_delete_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {
                        color: '#909399'
                    }
                }
            ]
        };
        myChart.setOption(option);
        </script>";

        return $js;
    }
    
    /**
     * 查询空间详情
     * @param int $id 记录ID
     * @return mixed
     */
    public function viewSpaceDetail($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取空间记录信息
        $space = CozeSpaceListModel::get($id);
        if (!$space) {
            return $this->error('空间记录不存在');
        }
        
        // 这里可以添加查询空间详情的逻辑
        // 例如调用API或者其他操作
        
        return $this->success('查询成功');
    }
} 