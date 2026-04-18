<?php
// 分成明细表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class DraftList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_jianying_draft')->where($map)
        ->order('time desc')
        ->paginate();

        // 查询今日status=2的记录按mac_address分组统计
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $mac_stats = DB::connect('translate')->table('ts_jianying_draft')
            ->where('status', 2)
            ->whereTime('time', 'between', [$today, $tomorrow])
            ->field(['mac_address', 'COUNT(*) as count'])
            ->group('mac_address')
            ->order('count desc')
            ->select();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        // 生成今日处理中统计表格HTML
        // $stats_html = $this->getMacStatsHtml($mac_stats);
        $stats_html = "";

        cookie('ts_jianying_draft', $map);
        
        return ZBuilder::make('table')
            // ->setTableName('video_task') // 设置数据表名
            ->setTableName('video/DraftListModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                
                ['id', 'ID'],
                    ['user_id','用户ID'],
                    ['dayid','日期'],
                    ['draft_id','草稿ID'],
                    ['mac_address','mac地址'],
                    ['dispatch_time','调度时间'],
                    ['finish_time','完成时间'],
                    ['duration_seconds','时长'],
                    ['video_url', '视频','image_video'],
                    ['gen_cnt','生成次数','text.edit'],
                    ['status','状态','text.edit'],
                    ['draft_url', '下载次数'],
                    ['time', '时间'],
                    
            ])
             ->setSearchArea([  
                ['text', 'user_id', '用户'],
                ['text', 'mac_address', 'mac地址'],
                ['text', 'draft_id', '草稿'],
                ['text', 'dayid', '日期'],
                ['text', 'status', '状态'],
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
              
            ])
            // ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html . $stats_html, 'toolbar_top')
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
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

        $data_list_time = DB::connect('translate')->table('ts_jianying_draft')
        ->whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as status_0',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as status_1',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as status_2',
            'SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) as status_5',
            'SUM(CASE WHEN status = 7 THEN 1 ELSE 0 END) as status_7'
        ])
        ->group('axisValue')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_0 = array();
        $y_data_status_1 = array();
        $y_data_status_2 = array();
        $y_data_status_5 = array();
        $y_data_status_7 = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_0, $value['status_0']);
            array_push($y_data_status_1, $value['status_1']);
            array_push($y_data_status_2, $value['status_2']);
            array_push($y_data_status_5, $value['status_5']);
            array_push($y_data_status_7, $value['status_7']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_0_json = json_encode($y_data_status_0);
        $y_data_status_1_json = json_encode($y_data_status_1);
        $y_data_status_2_json = json_encode($y_data_status_2);
        $y_data_status_5_json = json_encode($y_data_status_5);
        $y_data_status_7_json = json_encode($y_data_status_7);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总记录数', '未提交', '等待中', '处理中', '失败', '渲染完成']
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
                    name: '总记录数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '未提交',
                    type: 'line',
                    data: {$y_data_status_0_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '等待中',
                    type: 'line',
                    data: {$y_data_status_1_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '处理中',
                    type: 'line',
                    data: {$y_data_status_2_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败',
                    type: 'line',
                    data: {$y_data_status_5_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '渲染完成',
                    type: 'line',
                    data: {$y_data_status_7_json},
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

    /**
     * 生成MAC地址统计表格HTML
     */
    private function getMacStatsHtml($mac_stats) {
        if (empty($mac_stats)) {
            return '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;"><h4>今日处理中任务统计（按MAC地址）</h4><p>暂无数据</p></div>';
        }

        $total_count = array_sum(array_column($mac_stats, 'count'));
        
        $html = '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
        $html .= '<h4>今日处理中任务统计（按MAC地址）</h4>';
        $html .= '<p style="color: #666; margin-bottom: 10px;">总计：' . $total_count . ' 条处理中记录</p>';
        $html .= '<table class="table table-bordered table-striped" style="margin-bottom: 0;">';
        $html .= '<thead><tr><th style="width: 8%;">序号</th><th style="width: 40%;">MAC地址</th><th style="width: 15%;">记录数量</th><th style="width: 15%;">占比</th><th style="width: 22%;">操作</th></tr></thead>';
        $html .= '<tbody>';
        
        $index = 1;
        foreach ($mac_stats as $stat) {
            $percentage = $total_count > 0 ? round(($stat['count'] / $total_count) * 100, 1) : 0;
            $mac_address = htmlspecialchars($stat['mac_address']);
            $jump_url = 'https://ai-cms.fyshark.com/admin.php/video/online_machines/index.html?_s=mac_address=' . urlencode($stat['mac_address']) . '|name=|status=&_o=mac_address=eq|name=eq|status=eq';
            
            $html .= '<tr>';
            $html .= '<td><strong>' . $index . '</strong></td>';
            $html .= '<td>' . $mac_address . '</td>';
            $html .= '<td><span class="badge badge-primary">' . $stat['count'] . '</span></td>';
            $html .= '<td>' . $percentage . '%</td>';
            $html .= '<td><a href="' . $jump_url . '" target="_blank" class="btn btn-sm btn-info" title="查看设备详情"><i class="fa fa-external-link"></i> 查看设备</a></td>';
            $html .= '</tr>';
            $index++;
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        return $html;
    }
}
