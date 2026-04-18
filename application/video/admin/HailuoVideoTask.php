<?php
// 海螺视频任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\HailuoVideoTaskModel;

class HailuoVideoTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = HailuoVideoTaskModel::where($map)
        ->order('created_at desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_hailuo_video_tasks', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/HailuoVideoTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['user_id', '用户ID'],
                ['video_id', '视频ID'],
                ['prompt', '提示词' , 'callback', function($value){
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['hl_user_id', '海螺用户ID'],
                ['image_url', '图片URL', 'img_url'],
                ['status', '状态'],
                ['canRetry', '可重试'],
                ['videoURL', '视频URL', 'image_video'],
                ['downloadURLWithoutWatermark', '无水印下载URL', 'image_video'],
                ['modelID', '模型ID'],
                ['add_failed_count', '失败次数', 'number'],
                ['image_type', '图片类型'],
                ['coverURL', '封面URL', 'img_url'],
                
                ['failed_msg', '失败信息'],
                ['width', '宽度', 'number'],
                ['height', '高度', 'number'],
                ['canAppeal', '可申诉'],
                ['downloadURL', '下载URL', 'image_video'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'task_id', '任务ID'],
                ['number', 'user_id', '用户ID'],
                ['text', 'video_id', '视频ID'],
                ['text', 'hl_user_id', '海螺用户ID'],
                ['select', 'status', '状态', '', [
                    'queue' => '队列中',
                    'create' => '云创建',
                    'hl_queue' => '云排队',
                    'success' => '成功',
                    'failed' => '处理失败'
                ]],
                ['select', 'canRetry', '可重试', '', [0 => '否', 1 => '是']],
                ['select', 'canAppeal', '可申诉', '', [0 => '否', 1 => '是']],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            // ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons([ 'delete']) // 添加编辑和删除按钮
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

        $data_list_time = HailuoVideoTaskModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = "queue" THEN 1 ELSE 0 END) as status_queue',
            'SUM(CASE WHEN status = "create" THEN 1 ELSE 0 END) as status_create',
            'SUM(CASE WHEN status = "hl_queue" THEN 1 ELSE 0 END) as status_hl_queue',
            'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as status_success',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as status_failed'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_status_queue = array();
        $y_data_status_create = array();
        $y_data_status_hl_queue = array();
        $y_data_status_success = array();
        $y_data_status_failed = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_status_queue, $value['status_queue']);
            array_push($y_data_status_create, $value['status_create']);
            array_push($y_data_status_hl_queue, $value['status_hl_queue']);
            array_push($y_data_status_success, $value['status_success']);
            array_push($y_data_status_failed, $value['status_failed']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_status_queue_json = json_encode($y_data_status_queue);
        $y_data_status_create_json = json_encode($y_data_status_create);
        $y_data_status_hl_queue_json = json_encode($y_data_status_hl_queue);
        $y_data_status_success_json = json_encode($y_data_status_success);
        $y_data_status_failed_json = json_encode($y_data_status_failed);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '海螺视频任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '队列中', '云创建', '云排队', '成功', '处理失败']
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
                    name: '队列中',
                    type: 'line',
                    data: {$y_data_status_queue_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '云创建',
                    type: 'line',
                    data: {$y_data_status_create_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '云排队',
                    type: 'line',
                    data: {$y_data_status_hl_queue_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '成功',
                    type: 'line',
                    data: {$y_data_status_success_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '处理失败',
                    type: 'line',
                    data: {$y_data_status_failed_json},
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
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $r = HailuoVideoTaskModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'task_id', '任务ID', '请输入任务ID', '', 'required'],
                ['text', 'u_id', 'U_ID', '请输入U_ID', '', 'required'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'hl_user_id', '海螺用户ID', '请输入海螺用户ID'],
                ['text', 'video_id', '视频ID', '请输入视频ID', '', 'required'],
                ['text', 'desc', '描述', '请输入描述'],
                ['text', 'prompt', '提示词', '请输入提示词'],
                ['text', 'modelID', '模型ID', '请输入模型ID'],
                ['text', 'image_url', '图片URL', '请输入图片URL'],
                ['number', 'add_failed_count', '失败次数', '请输入失败次数', 0],
                ['text', 'image_type', '图片类型', '请输入图片类型'],
                ['text', 'coverURL', '封面URL', '请输入封面URL', '', 'required'],
                ['text', 'videoURL', '视频URL', '请输入视频URL', '', 'required'],
                ['text', 'downloadURLWithoutWatermark', '无水印下载URL', '请输入无水印下载URL'],
                ['select', 'status', '状态', '', [
                    'queue' => '队列中',
                    'create' => '云创建',
                    'hl_queue' => '云排队',
                    'success' => '成功',
                    'failed' => '处理失败'
                ], 'queue'],
                ['select', 'canRetry', '可重试', '', [0 => '否', 1 => '是'], 0],
                ['text', 'failed_msg', '失败信息', '请输入失败信息'],
                ['number', 'width', '宽度', '请输入宽度', '', 'required'],
                ['number', 'height', '高度', '请输入高度', '', 'required'],
                ['textarea', 'originFiles', '原始文件', '请输入原始文件信息'],
                ['select', 'canAppeal', '可申诉', '', [0 => '否', 1 => '是'], 0],
                ['text', 'downloadURL', '下载URL', '请输入下载URL', '', 'required']
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
            $data['updated_at'] = date('Y-m-d H:i:s');

            $r = HailuoVideoTaskModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = HailuoVideoTaskModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'task_id', '任务ID', '请输入任务ID', '', 'required'],
                ['text', 'u_id', 'U_ID', '请输入U_ID', '', 'required'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['text', 'hl_user_id', '海螺用户ID', '请输入海螺用户ID'],
                ['text', 'video_id', '视频ID', '请输入视频ID', '', 'required'],
                ['text', 'desc', '描述', '请输入描述'],
                ['text', 'prompt', '提示词', '请输入提示词'],
                ['text', 'modelID', '模型ID', '请输入模型ID'],
                ['text', 'image_url', '图片URL', '请输入图片URL'],
                ['number', 'add_failed_count', '失败次数', '请输入失败次数'],
                ['text', 'image_type', '图片类型', '请输入图片类型'],
                ['text', 'coverURL', '封面URL', '请输入封面URL', '', 'required'],
                ['text', 'videoURL', '视频URL', '请输入视频URL', '', 'required'],
                ['text', 'downloadURLWithoutWatermark', '无水印下载URL', '请输入无水印下载URL'],
                ['select', 'status', '状态', '', [
                    'queue' => '队列中',
                    'create' => '云创建',
                    'hl_queue' => '云排队',
                    'success' => '成功',
                    'failed' => '处理失败'
                ]],
                ['select', 'canRetry', '可重试', '', [0 => '否', 1 => '是']],
                ['text', 'failed_msg', '失败信息', '请输入失败信息'],
                ['number', 'width', '宽度', '请输入宽度', '', 'required'],
                ['number', 'height', '高度', '请输入高度', '', 'required'],
                ['textarea', 'originFiles', '原始文件', '请输入原始文件信息'],
                ['select', 'canAppeal', '可申诉', '', [0 => '否', 1 => '是']],
                ['text', 'downloadURL', '下载URL', '请输入下载URL', '', 'required']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = HailuoVideoTaskModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 