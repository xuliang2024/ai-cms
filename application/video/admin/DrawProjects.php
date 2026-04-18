<?php
// AI绘画项目管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\DrawProjectsModel;

class DrawProjects extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = DrawProjectsModel::where($map)
        ->order('sort desc, id desc')
        ->paginate();

        cookie('ts_draw_projects', $map);
        
        // 统计项目信息
        $stats = [
            'public_count' => DrawProjectsModel::where('is_public', 1)->count(),
            'private_count' => DrawProjectsModel::where('is_public', 0)->count(),
            'total_count' => DrawProjectsModel::count(),
            'today_count' => DrawProjectsModel::whereTime('created_at', 'today')->count(),
            'week_count' => DrawProjectsModel::whereTime('created_at', 'week')->count(),
            'month_count' => DrawProjectsModel::whereTime('created_at', 'month')->count()
        ];
        
        // 公开状态定义
        $public_types = [
            0 => '私有',
            1 => '公开'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("总项目：{$stats['total_count']} | 公开项目：{$stats['public_count']} | 私有项目：{$stats['private_count']} | 今日新增：{$stats['today_count']} | 本周新增：{$stats['week_count']} | 本月新增：{$stats['month_count']}")
            ->setTableName('video/DrawProjectsModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['uuid', '项目UUID', 'callback', function($value){
                    return mb_strlen($value) > 15 ? mb_substr($value, 0, 15).'...' : $value;
                }],
                ['user_id', '用户ID'],
                ['name', '项目名称', 'callback', function($value){
                    if(empty($value)) return '无名称';
                    return mb_strlen($value) > 20 ? mb_substr($value, 0, 20).'...' : $value;
                }],
                ['thumbnail_url', '缩略图', 'picture'],
                ['is_public', '公开状态', 'switch'],
                ['sort', '排序权重', 'text.edit'],
                ['last_task_id', '关联任务ID'],
                ['created_at', '创建时间'],
                ['last_accessed_at', '最后访问'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'id', 'ID'],
                ['text', 'uuid', '项目UUID'],
                ['text', 'user_id', '用户ID'],
                ['text', 'name', '项目名称'],
                ['select', 'is_public', '公开状态', '', '', $public_types],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->addRightButton('custom', ['title' => '查看', 'icon' => 'fa fa-fw fa-eye', 'href' => url('view', ['id' => '__id__'])])
            ->setHeight('auto')
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

        $data_list_time = DrawProjectsModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN is_public = 0 THEN 1 ELSE 0 END) as private_count',
            'SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public_count',
            'COUNT(DISTINCT user_id) as user_count'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_private = array();
        $y_data_public = array();
        $y_data_user = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_private, $value['private_count']);
            array_push($y_data_public, $value['public_count']);
            array_push($y_data_user, $value['user_count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_private_json = json_encode($y_data_private);
        $y_data_public_json = json_encode($y_data_public);
        $y_data_user_json = json_encode($y_data_user);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: 'AI绘画项目{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['项目总数', '私有项目', '公开项目', '用户数']
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
                    name: '项目总数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '私有项目',
                    type: 'line',
                    data: {$y_data_private_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '公开项目',
                    type: 'line',
                    data: {$y_data_public_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '用户数',
                    type: 'line',
                    data: {$y_data_user_json},
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
            
            // 生成UUID
            if (empty($data['uuid'])) {
                $data['uuid'] = $this->generateUUID();
            }
            
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['last_accessed_at'] = date('Y-m-d H:i:s');
            
            if (DrawProjectsModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增AI绘画项目') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '项目名称', '请输入项目名称', '', 'required'],
                ['text', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['textarea', 'description', '项目描述', '请输入项目描述'],
                ['radio', 'is_public', '公开状态', '选择项目公开状态', ['0' => '私有', '1' => '公开'], 0],
                ['number', 'sort', '排序权重', '数值越大越靠前，用于推荐案例排序', 0],
                ['text', 'canvas_url', '画布数据URL', '画布数据文件URL'],
                ['image', 'thumbnail_url', '缩略图URL', '项目缩略图'],
                ['number', 'last_task_id', '关联任务ID', '最后关联的任务ID'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if (DrawProjectsModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DrawProjectsModel::where('id', $id)->find();
        if (!$info) {
            $this->error('项目不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑AI绘画项目') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['static', 'uuid', '项目UUID', '项目唯一标识'],
                ['text', 'name', '项目名称', '请输入项目名称', '', 'required'],
                ['text', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['textarea', 'description', '项目描述', '请输入项目描述'],
                ['radio', 'is_public', '公开状态', '选择项目公开状态', ['0' => '私有', '1' => '公开']],
                ['number', 'sort', '排序权重', '数值越大越靠前，用于推荐案例排序'],
                ['text', 'canvas_url', '画布数据URL', '画布数据文件URL'],
                ['image', 'thumbnail_url', '缩略图URL', '项目缩略图'],
                ['number', 'last_task_id', '关联任务ID', '最后关联的任务ID'],
                ['static', 'created_at', '创建时间'],
                ['static', 'last_accessed_at', '最后访问时间'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function view($id = null)
    {
        $info = DrawProjectsModel::where('id', $id)->find();
        if (!$info) {
            $this->error('项目不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('查看AI绘画项目详情')
            ->addFormItems([
                ['static', 'id', 'ID'],
                ['static', 'uuid', '项目UUID'],
                ['static', 'name', '项目名称'],
                ['static', 'user_id', '用户ID'],
                ['static', 'description', '项目描述'],
                ['static', 'is_public', '公开状态', '', '', ['0' => '私有', '1' => '公开']],
                ['static', 'sort', '排序权重'],
                ['static', 'canvas_url', '画布数据URL'],
                ['static', 'thumbnail_url', '缩略图URL'],
                ['static', 'last_task_id', '关联任务ID'],
                ['static', 'created_at', '创建时间'],
                ['static', 'updated_at', '更新时间'],
                ['static', 'last_accessed_at', '最后访问时间'],
            ])
            ->setFormData($info)
            ->hideBtn('submit')
            ->addBtn('<a class="btn btn-default" href="'.url('index').'">返回列表</a>')
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = DrawProjectsModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 生成UUID
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

