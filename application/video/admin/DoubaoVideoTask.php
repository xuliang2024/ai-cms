<?php
// 豆包视频任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DoubaoVideoTaskModel;

class DoubaoVideoTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = DoubaoVideoTaskModel::where($map)
        ->order('created_at desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }
        
        // 添加模态框HTML
        $modal_html = '
        <!-- 内容查看模态框 -->
        <div class="modal fade" id="contentModal" tabindex="-1" role="dialog" aria-labelledby="contentModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="关闭">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="contentModalLabel">查看完整内容</h4>
                    </div>
                    <div class="modal-body">
                        <div id="fullContentText" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function showFullContent(title, content) {
            document.getElementById("contentModalLabel").innerText = "查看完整" + title;
            document.getElementById("fullContentText").innerText = content;
            $("#contentModal").modal("show");
        }
        </script>';
        
        $content_html .= $modal_html;

        cookie('ts_doubao_video_tasks', $map);
        
        // 状态类型定义
        $status_types = [
            'queued' => '排队中',
            'running' => '任务运行中',
            'cancelled' => '取消任务',
            'succeeded' => '任务成功',
            'failed' => '任务失败',
            'processing' => '处理中'
        ];
        
        return ZBuilder::make('table')
            ->setTableName('video/DoubaoVideoTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID' ],
                ['task_id', '任务ID' ],
                ['hs_token', 'HS Token'],
                ['money', '金额'],
                ['image_url', '首张图片', 'img_url'],
                ['end_image_url', '尾张图片', 'img_url'],
                ['video_url', '视频链接', 'image_video'],
                ['prompt', '提示词', 'callback', function($value){
                    if(mb_strlen($value) > 20) {
                        $short_text = mb_substr($value, 0, 20).'...';
                        $full_text = htmlspecialchars($value);
                        return '<span style="color: #007cba; cursor: pointer;" onclick="showFullContent(\'提示词\', \''.$full_text.'\')">'.$short_text.'</span>';
                    }
                    return $value;
                }],
                ['model', '使用模型'],
                ['resolution', '分辨率'],
                ['duration', '时长(秒)'],
                ['status', '状态' ],
                ['msg', '错误信息', 'callback', function($value){
                    if(mb_strlen($value) > 20) {
                        $short_text = mb_substr($value, 0, 20).'...';
                        $full_text = htmlspecialchars($value);
                        return '<span style="color: #007cba; cursor: pointer;" onclick="showFullContent(\'错误信息\', \''.$full_text.'\')">'.$short_text.'</span>';
                    }
                    return $value;
                } ],
                ['completion_tokens', '完成Tokens'],
                ['prompt_tokens', '提示Tokens'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'task_id', '任务ID'],
                ['text', 'user_id', '用户ID'],
                ['text', 'status', '状态'],
                ['text', 'model', '模型'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons([ 'delete']) // 添加查看详情和删除按钮
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }

    

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'created_at=') === 0) {
                    $daterangeValue = substr($param, strlen('created_at='));
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
        $groupBy = $isSameDay ? 'HOUR(created_at)' : 'DATE(created_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = DoubaoVideoTaskModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = "queued" THEN 1 ELSE 0 END) as status_queued',
            'SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as status_running',
            'SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as status_cancelled',
            'SUM(CASE WHEN status = "succeeded" THEN 1 ELSE 0 END) as status_succeeded',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as status_failed',
            'SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as status_processing'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_queued = array();
        $y_data_status_running = array();
        $y_data_status_cancelled = array();
        $y_data_status_succeeded = array();
        $y_data_status_failed = array();
        $y_data_status_processing = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_queued, $value['status_queued']);
            array_push($y_data_status_running, $value['status_running']);
            array_push($y_data_status_cancelled, $value['status_cancelled']);
            array_push($y_data_status_succeeded, $value['status_succeeded']);
            array_push($y_data_status_failed, $value['status_failed']);
            array_push($y_data_status_processing, $value['status_processing']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_queued_json = json_encode($y_data_status_queued);
        $y_data_status_running_json = json_encode($y_data_status_running);
        $y_data_status_cancelled_json = json_encode($y_data_status_cancelled);
        $y_data_status_succeeded_json = json_encode($y_data_status_succeeded);
        $y_data_status_failed_json = json_encode($y_data_status_failed);
        $y_data_status_processing_json = json_encode($y_data_status_processing);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '豆包视频任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '排队中', '任务运行中', '取消任务', '任务成功', '任务失败', '处理中']
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
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '排队中',
                    type: 'line',
                    data: {$y_data_status_queued_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '任务运行中',
                    type: 'line',
                    data: {$y_data_status_running_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '取消任务',
                    type: 'line',
                    data: {$y_data_status_cancelled_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '任务成功',
                    type: 'line',
                    data: {$y_data_status_succeeded_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '任务失败',
                    type: 'line',
                    data: {$y_data_status_failed_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '处理中',
                    type: 'line',
                    data: {$y_data_status_processing_json},
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

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = DoubaoVideoTaskModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 