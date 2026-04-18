<?php
// Flux Kontext Pro 图像生成任务管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\FluxKontextProTaskModel;

class FluxKontextProTask extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = FluxKontextProTaskModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_flux_kontext_pro_task', $map);
        
        // 任务状态定义
        $status_types = [
            'pending' => '待处理',
            'processing' => '处理中', 
            'completed' => '已完成',
            'failed' => '失败',
            'cancelled' => '已取消'
        ];
        
        // 任务类型定义
        $task_types = [
            'storyboard' => '分镜',
            'character' => '角色',
            'general' => '通用'
        ];
        
        // Coze状态定义
        $coze_status_types = [
            0 => '未开始',
            1 => '进行中',
            2 => '完成',
            3 => '失败'
        ];
        
        return ZBuilder::make('table')
            ->setTableName('video/FluxKontextProTaskModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['task_id', '任务ID'],
                ['user_id', '用户ID'],
                ['task_type', '任务类型'],
                ['status', '任务状态'],
                ['input_image', '输入图像', 'img_url'],
                ['result_image_url', '结果图像', 'img_url'],
                ['processing_time', '处理耗时(秒)'],
                ['cost_credits', '消耗积分'],
                ['retry_count', '重试次数'],
                ['coze_status', 'Coze状态'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'task_id', '任务ID'],
                ['text', 'user_id', '用户ID'],
                ['select', 'task_type', '任务类型'],
                ['select', 'status', '任务状态'],
                ['select', 'coze_status', 'Coze状态'],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
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

        $data_list_time = FluxKontextProTaskModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as total_count',
            'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count',
            'SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing_count',
            'SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count',
            'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count',
            'SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count',
            'SUM(cost_credits) as total_cost'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_total = array();
        $y_data_pending = array();
        $y_data_processing = array();
        $y_data_completed = array();
        $y_data_failed = array();
        $y_data_cancelled = array();
        $y_data_cost = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['total_count']);
            array_push($y_data_pending, $value['pending_count']);
            array_push($y_data_processing, $value['processing_count']);
            array_push($y_data_completed, $value['completed_count']);
            array_push($y_data_failed, $value['failed_count']);
            array_push($y_data_cancelled, $value['cancelled_count']);
            array_push($y_data_cost, $value['total_cost']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_pending_json = json_encode($y_data_pending);
        $y_data_processing_json = json_encode($y_data_processing);
        $y_data_completed_json = json_encode($y_data_completed);
        $y_data_failed_json = json_encode($y_data_failed);
        $y_data_cancelled_json = json_encode($y_data_cancelled);
        $y_data_cost_json = json_encode($y_data_cost);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'Flux Kontext Pro任务{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总任务数', '待处理', '处理中', '已完成', '失败', '取消', '消耗积分']
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
                    name: '待处理',
                    type: 'line',
                    data: {$y_data_pending_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '处理中',
                    type: 'line',
                    data: {$y_data_processing_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '已完成',
                    type: 'line',
                    data: {$y_data_completed_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '失败',
                    type: 'line',
                    data: {$y_data_failed_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '取消',
                    type: 'line',
                    data: {$y_data_cancelled_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '消耗积分',
                    type: 'line',
                    data: {$y_data_cost_json},
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
        // 构建表单
        if (request()->isPost()) {
            $data = input('post.');
            
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (FluxKontextProTaskModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增Flux Kontext Pro任务') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                // 基本任务信息
                ['text', 'task_id', '任务唯一ID', '请输入任务唯一ID', '', 'required'],
                ['number', 'user_id', '用户ID', '创建用户ID', 0],
                ['select', 'task_type', '任务类型', '选择任务类型', [
                    'storyboard' => '分镜',
                    'character' => '角色', 
                    'general' => '通用'
                ], 'general'],
                ['text', 'related_id', '关联对象ID', '关联的分镜UUID或角色ID等'],
                ['text', 'story_uuid', '故事UUID', '所属故事的唯一标识'],
                
                // 请求参数
                ['text', 'x_key', 'API密钥', '请输入Flux API密钥', '', 'required'],
                ['textarea', 'prompt', '文本提示词', '请输入图像生成的文本描述', '', 'required'],
                ['textarea', 'input_image', '输入图像', 'Base64编码的图像或图像URL'],
                ['number', 'seed', '随机种子', '用于可重复性的种子值'],
                ['text', 'aspect_ratio', '宽高比', '图像宽高比，支持21:9到9:21之间的比例'],
                ['select', 'output_format', '输出格式', '选择输出图像格式', [
                    'png' => 'PNG',
                    'jpeg' => 'JPEG'
                ], 'png'],
                
                // Webhook相关
                ['text', 'webhook_url', 'Webhook URL', '接收任务完成通知的URL'],
                ['text', 'webhook_secret', 'Webhook密钥', 'Webhook签名验证密钥'],
                
                // 高级参数
                ['radio', 'prompt_upsampling', '提示词增强', '是否自动增强提示词', [
                    '0' => '否',
                    '1' => '是'
                ], '0'],
                ['number', 'safety_tolerance', '安全容忍度', '输入和输出审核的容忍度，0-6级别', 2],
                
                // 优先级和队列
                ['number', 'priority', '优先级', '任务优先级 1-10，数字越大优先级越高', 5],
                ['text', 'queue_name', '队列名称', '任务队列名称', 'flux_kontext'],
                ['number', 'max_retry', '最大重试次数', '最大允许重试次数', 3],
                
                // 扩展字段
                ['textarea', 'tags', '标签', '任务标签，多个标签用逗号分隔'],
                ['textarea', 'metadata', '扩展数据', 'JSON格式的扩展信息'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (FluxKontextProTaskModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = FluxKontextProTaskModel::where('id', $id)->find();
        if (!$info) {
            $this->error('任务不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑Flux Kontext Pro任务') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                
                // 基本任务信息
                ['text', 'task_id', '任务唯一ID', '请输入任务唯一ID', '', 'required'],
                ['number', 'user_id', '用户ID', '创建用户ID'],
                ['select', 'task_type', '任务类型', '选择任务类型', [
                    'storyboard' => '分镜',
                    'character' => '角色',
                    'general' => '通用'
                ]],
                ['text', 'related_id', '关联对象ID', '关联的分镜UUID或角色ID等'],
                ['text', 'story_uuid', '故事UUID', '所属故事的唯一标识'],
                
                // 请求参数
                ['text', 'x_key', 'API密钥', '请输入Flux API密钥', '', 'required'],
                ['textarea', 'prompt', '文本提示词', '请输入图像生成的文本描述', '', 'required'],
                ['textarea', 'input_image', '输入图像', 'Base64编码的图像或图像URL'],
                ['number', 'seed', '随机种子', '用于可重复性的种子值'],
                ['text', 'aspect_ratio', '宽高比', '图像宽高比，支持21:9到9:21之间的比例'],
                ['select', 'output_format', '输出格式', '选择输出图像格式', [
                    'png' => 'PNG',
                    'jpeg' => 'JPEG'
                ]],
                
                // Webhook相关
                ['text', 'webhook_url', 'Webhook URL', '接收任务完成通知的URL'],
                ['text', 'webhook_secret', 'Webhook密钥', 'Webhook签名验证密钥'],
                
                // 高级参数
                ['radio', 'prompt_upsampling', '提示词增强', '是否自动增强提示词', [
                    '0' => '否',
                    '1' => '是'
                ]],
                ['number', 'safety_tolerance', '安全容忍度', '输入和输出审核的容忍度，0-6级别'],
                
                // 响应数据
                ['text', 'api_task_id', 'API任务ID', 'Flux API返回的任务标识'],
                ['text', 'polling_url', '轮询URL', '用于查询任务状态的URL'],
                ['select', 'status', '任务状态', '选择任务状态', [
                    'pending' => '待处理',
                    'processing' => '处理中',
                    'completed' => '已完成',
                    'failed' => '失败',
                    'cancelled' => '已取消'
                ]],
                
                // 生成结果
                ['text', 'result_image_url', '结果图像URL', '生成的图像访问链接'],
                ['text', 'result_image_local_path', '本地图像路径', '下载到本地的图像路径'],
                
                // 图像属性
                ['number', 'image_width', '图像宽度', '生成图像的宽度（像素）'],
                ['number', 'image_height', '图像高度', '生成图像的高度（像素）'],
                ['number', 'image_size', '图像大小', '图像文件大小（字节）'],
                
                // 处理信息
                ['number', 'processing_time', '处理耗时', '任务处理耗时（秒）'],
                ['number', 'queue_time', '排队耗时', '任务排队等待时间（秒）'],
                
                // 错误信息
                ['textarea', 'error_message', '错误信息', '任务失败时的详细错误信息'],
                ['text', 'error_code', '错误代码', '错误类型代码'],
                
                // 重试和优先级
                ['number', 'retry_count', '重试次数', '任务重试次数'],
                ['number', 'max_retry', '最大重试次数', '最大允许重试次数'],
                ['number', 'priority', '优先级', '任务优先级 1-10，数字越大优先级越高'],
                ['text', 'queue_name', '队列名称', '任务队列名称'],
                
                // Coze相关
                ['select', 'coze_status', 'Coze状态', '选择Coze状态', [
                    '0' => '未开始',
                    '1' => '进行中',
                    '2' => '完成',
                    '3' => '失败'
                ]],
                ['number', 'coze_cnt', 'Coze次数', 'Coze次数'],
                
                // 成本和扩展
                ['text', 'cost_credits', '消耗积分', '任务消耗的积分或费用'],
                ['textarea', 'tags', '标签', '任务标签，多个标签用逗号分隔'],
                ['textarea', 'metadata', '扩展数据', 'JSON格式的扩展信息'],
                
                // 时间字段
                ['datetime', 'started_at', '开始处理时间', '任务开始处理的时间'],
                ['datetime', 'completed_at', '完成时间', '任务完成的时间'],
                ['datetime', 'submitted_at', '提交到API时间', '任务提交到API的时间'],
                ['datetime', 'webhook_received_at', 'Webhook接收时间', 'Webhook接收的时间'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = FluxKontextProTaskModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 