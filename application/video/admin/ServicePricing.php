<?php
// 服务定价管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\ServicePricingModel;

class ServicePricing extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = ServicePricingModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_service_pricing', $map);
        
        // 统计服务信息
        $stats = [
            'active_count' => ServicePricingModel::where('is_active', 1)->count(),
            'inactive_count' => ServicePricingModel::where('is_active', 0)->count(),
            'total_count' => ServicePricingModel::count(),
            'avg_base_price' => ServicePricingModel::where('base_price', '>', 0)->avg('base_price'),
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("启用服务：{$stats['active_count']} | 禁用服务：{$stats['inactive_count']} | 总服务数：{$stats['total_count']} | 平均基础价格：" . number_format($stats['avg_base_price'], 2) . "分")
            ->setTableName('video/ServicePricingModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['app_name', '应用名称'],
                ['display_name', '显示名称', 'text.edit'],
                ['base_price', '基础价格(分)'],
                ['pricing_config', '定价配置', 'callback', function($value){
                    if(empty($value)) return '无配置';
                    $config = json_decode($value, true);
                    return is_array($config) ? '已配置(' . count($config) . '项)' : '配置格式错误';
                }],
                ['vip_config', 'VIP配置', 'callback', function($value){
                    if(empty($value)) return '无VIP配置';
                    $config = json_decode($value, true);
                    return is_array($config) ? '已配置VIP折扣' : '配置格式错误';
                }],
                ['is_active', '服务状态', 'switch'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'app_name', '应用名称'],
                ['text', 'display_name', '显示名称'],
                ['select', 'is_active', '服务状态', '', ['1' => '启用', '0' => '禁用']],
                ['daterange', 'updated_at', '更新时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    public function getChartjs() {
        $queryParameters = $this->request->get();
        $daterangeValue = null;
        if (isset($queryParameters['_s'])) {
            $searchParams = explode('|', $queryParameters['_s']);
            foreach ($searchParams as $param) {
                if (strpos($param, 'updated_at=') === 0) {
                    $daterangeValue = substr($param, strlen('updated_at='));
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
        $groupBy = $isSameDay ? 'HOUR(updated_at)' : 'DATE(updated_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = ServicePricingModel::whereTime('updated_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count',
            'SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count',
            'AVG(base_price) as avg_price'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_time = array();
        $y_data_inactive = array();
        $y_data_active = array();
        $y_data_avg_price = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
            array_push($y_data_inactive, $value['inactive_count']);
            array_push($y_data_active, $value['active_count']);
            array_push($y_data_avg_price, round($value['avg_price'], 2));
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);
        $y_data_inactive_json = json_encode($y_data_inactive);
        $y_data_active_json = json_encode($y_data_active);
        $y_data_avg_price_json = json_encode($y_data_avg_price);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '服务定价{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['服务总数', '禁用服务', '启用服务', '平均价格']
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
                    name: '服务总数',
                    type: 'line',
                    data: {$y_data_time_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '禁用服务',
                    type: 'line',
                    data: {$y_data_inactive_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '启用服务',
                    type: 'line',
                    data: {$y_data_active_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '平均价格',
                    type: 'line',
                    data: {$y_data_avg_price_json},
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
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证JSON格式
            if (!empty($data['pricing_config'])) {
                $pricing_config = json_decode($data['pricing_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('定价配置JSON格式错误');
                }
            }
            
            if (!empty($data['vip_config'])) {
                $vip_config = json_decode($data['vip_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('VIP配置JSON格式错误');
                }
            }
            
            if (ServicePricingModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增服务定价') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'app_name', '应用名称', '请输入应用名称（唯一标识）<br>示例：fal-ai/qwen-image', '', 'required'],
                ['text', 'display_name', '显示名称', '请输入显示名称<br>示例：中文模型', '', 'required'],
                ['number', 'base_price', '基础价格(分)', '请输入基础价格，单位：分', 10, 'required'],
                ['textarea', 'pricing_config', '定价配置JSON', '请输入定价配置JSON格式数据<br>示例：{"type": "fixed", "price": 3}', '{"type": "fixed", "price": 3}', '', 'rows="4"'],
                ['textarea', 'vip_config', 'VIP配置JSON', '请输入VIP折扣配置JSON格式数据<br>示例：{"0": {"multiplier": 2.0}, "1": {"multiplier": 1.5}, "2": {"multiplier": 1.0}}', '{"0": {"multiplier": 2.0}, "1": {"multiplier": 1.5}, "2": {"multiplier": 1.0}}', '', 'rows="4"'],
                ['radio', 'is_active', '服务状态', '选择服务状态', ['0' => '禁用', '1' => '启用'], 1],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证JSON格式
            if (!empty($data['pricing_config'])) {
                $pricing_config = json_decode($data['pricing_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('定价配置JSON格式错误');
                }
            }
            
            if (!empty($data['vip_config'])) {
                $vip_config = json_decode($data['vip_config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('VIP配置JSON格式错误');
                }
            }
            
            if (ServicePricingModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = ServicePricingModel::where('id', $id)->find();
        if (!$info) {
            $this->error('服务定价不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑服务定价') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'app_name', '应用名称', '请输入应用名称（唯一标识）<br>示例：fal-ai/qwen-image', '', 'required'],
                ['text', 'display_name', '显示名称', '请输入显示名称<br>示例：中文模型', '', 'required'],
                ['number', 'base_price', '基础价格(分)', '请输入基础价格，单位：分', '', 'required'],
                ['textarea', 'pricing_config', '定价配置JSON', '请输入定价配置JSON格式数据<br>示例：{"tier1": 100, "tier2": 200}', '', '', 'rows="4"'],
                ['textarea', 'vip_config', 'VIP配置JSON', '请输入VIP折扣配置JSON格式数据<br>示例：{"discount": 0.8, "min_level": 1}', '', '', 'rows="4"'],
                ['radio', 'is_active', '服务状态', '选择服务状态', ['0' => '禁用', '1' => '启用']],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = ServicePricingModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 快速编辑
     * @param array $record 行为日志内容
     * @author 用户
     */
    public function quickEdit($record = [])
    {
        $field = input('post.name', '');
        $value = input('post.value', '');
        $id = input('post.pk', '');
        
        // 验证参数
        if (empty($field)) {
            $this->error('缺少字段名');
        }
        if (empty($id)) {
            $this->error('缺少主键值');
        }
        
        // 获取原始数据用于记录
        $original = ServicePricingModel::where('id', $id)->find();
        if (!$original) {
            $this->error('记录不存在');
        }
        
        // 构建更新数据
        $updateData = [
            $field => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 执行更新
        $result = ServicePricingModel::where('id', $id)->update($updateData);
        
        if ($result !== false) {
            $this->success('显示名称更新成功！');
        } else {
            $this->error('更新失败，请重试');
        }
    }
} 
