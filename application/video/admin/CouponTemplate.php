<?php
// 优惠券模板管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CouponTemplateModel;

class CouponTemplate extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = CouponTemplateModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_coupon_template', $map);
        
        // 统计优惠券信息
        $stats = [
            'active_count' => CouponTemplateModel::where('status', 1)->count(),
            'inactive_count' => CouponTemplateModel::where('status', 0)->count(),
            'total_count' => CouponTemplateModel::count(),
            'total_issued' => CouponTemplateModel::where('total_count', '>', 0)->sum('total_count'),
            'total_used' => CouponTemplateModel::sum('used_count'),
            'expired_count' => CouponTemplateModel::where('end_time', '<', date('Y-m-d H:i:s'))->where('end_time', 'not null')->count(),
        ];
        
        // 计算使用率
        $usage_rate = $stats['total_issued'] > 0 ? round(($stats['total_used'] / $stats['total_issued']) * 100, 2) : 0;
        
        // 优惠券类型定义
        $coupon_types = [
            1 => '满减券',
            2 => '折扣券', 
            3 => '固定金额券'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("启用模板：{$stats['active_count']} | 禁用模板：{$stats['inactive_count']} | 总模板数：{$stats['total_count']} | 总发行量：{$stats['total_issued']} | 总使用量：{$stats['total_used']} | 使用率：{$usage_rate}% | 已过期：{$stats['expired_count']}")
            ->setTableName('video/CouponTemplateModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '优惠券名称'],
                ['code', '优惠券代码'],
                ['type', '类型', 'callback', function($value) use ($coupon_types) {
                    return isset($coupon_types[$value]) ? $coupon_types[$value] : '未知';
                }],
                ['shop_id', '商品ID'],
                ['discount_value', '优惠值(分)', 'callback', function($value){
                    return number_format($value, 0) . '分';
                }],
                ['min_amount', '最低消费(分)', 'callback', function($value){
                    return $value > 0 ? number_format($value, 0) . '分' : '无限制';
                }],
                ['max_discount', '最大优惠(分)', 'callback', function($value){
                    return $value > 0 ? number_format($value, 0) . '分' : '无限制';
                }],
                ['total_count', '发行总量', 'callback', function($value){
                    return $value > 0 ? number_format($value, 0) : '不限制';
                }],
                ['used_count', '已使用', 'callback', function($value){
                    return number_format($value, 0);
                }],
                ['per_user_limit', '每人限领'],
                ['valid_days', '有效天数'],
                ['start_time', '开始时间', 'callback', function($value){
                    return $value ? $value : '立即生效';
                }],
                ['end_time', '结束时间', 'callback', function($value){
                    if (!$value) return '永久有效';
                    $is_expired = strtotime($value) < time();
                    return $value . ($is_expired ? ' <span style="color:red;">(已过期)</span>' : '');
                }],
                ['status', '状态', 'switch'],
                ['description', '描述', 'callback', function($value){
                    if(empty($value)) return '无描述';
                    return mb_strlen($value) > 30 ? mb_substr($value, 0, 30).'...' : $value;
                }],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'name', '优惠券名称'],
                ['text', 'code', '优惠券代码'],
                ['select', 'type', '优惠券类型', '', $coupon_types],
                ['text', 'shop_id', '商品ID'],
                ['select', 'status', '状态', '', ['1' => '启用', '0' => '禁用']],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'start_time', '活动时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('custom', ['title' => '批量生成', 'href' => url('batchGenerate')]) // 添加批量生成按钮
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

        $data_list_time = CouponTemplateModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_count',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_count',
            'SUM(total_count) as total_issued',
            'SUM(used_count) as total_used',
            'SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as type_1',
            'SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as type_2',
            'SUM(CASE WHEN type = 3 THEN 1 ELSE 0 END) as type_3'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_count = array();
        $y_data_inactive = array();
        $y_data_active = array();
        $y_data_issued = array();
        $y_data_used = array();
        $y_data_type1 = array();
        $y_data_type2 = array();
        $y_data_type3 = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_count, $value['count']);
            array_push($y_data_inactive, $value['inactive_count']);
            array_push($y_data_active, $value['active_count']);
            array_push($y_data_issued, $value['total_issued']);
            array_push($y_data_used, $value['total_used']);
            array_push($y_data_type1, $value['type_1']);
            array_push($y_data_type2, $value['type_2']);
            array_push($y_data_type3, $value['type_3']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_count_json = json_encode($y_data_count);
        $y_data_inactive_json = json_encode($y_data_inactive);
        $y_data_active_json = json_encode($y_data_active);
        $y_data_issued_json = json_encode($y_data_issued);
        $y_data_used_json = json_encode($y_data_used);
        $y_data_type1_json = json_encode($y_data_type1);
        $y_data_type2_json = json_encode($y_data_type2);
        $y_data_type3_json = json_encode($y_data_type3);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '优惠券模板{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['模板总数', '禁用模板', '启用模板', '发行量', '使用量', '满减券', '折扣券', '固定金额券']
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
                    name: '模板总数',
                    type: 'line',
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '禁用模板',
                    type: 'line',
                    data: {$y_data_inactive_json}
                },
                {
                    name: '启用模板',
                    type: 'line',
                    data: {$y_data_active_json}
                },
                {
                    name: '发行量',
                    type: 'bar',
                    data: {$y_data_issued_json}
                },
                {
                    name: '使用量',
                    type: 'bar',
                    data: {$y_data_used_json}
                },
                {
                    name: '满减券',
                    type: 'line',
                    data: {$y_data_type1_json}
                },
                {
                    name: '折扣券',
                    type: 'line',
                    data: {$y_data_type2_json}
                },
                {
                    name: '固定金额券',
                    type: 'line',
                    data: {$y_data_type3_json}
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
            
            // 验证优惠券代码唯一性
            $exists = CouponTemplateModel::where('code', $data['code'])->find();
            if ($exists) {
                $this->error('优惠券代码已存在，请使用其他代码');
            }
            
            // 验证适用商品JSON格式
            if (!empty($data['applicable_products'])) {
                $products = json_decode($data['applicable_products'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('适用商品JSON格式错误');
                }
            }
            
            // 验证时间逻辑
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                    $this->error('结束时间必须晚于开始时间');
                }
            }
            
            if (CouponTemplateModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增优惠券模板') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '优惠券名称', '请输入优惠券名称', '', 'required'],
                ['text', 'code', '优惠券代码', '请输入优惠券代码（唯一标识）', '', 'required'],
                ['radio', 'type', '优惠券类型', '选择优惠券类型', ['1' => '满减券', '2' => '折扣券', '3' => '固定金额券'], 1],
                ['number', 'shop_id', '商品ID', '请输入关联商品ID', '', 'required'],
                ['number', 'discount_value', '优惠值(分)', '请输入优惠值，单位：分', 0, 'required'],
                ['number', 'min_amount', '最低消费金额(分)', '请输入最低消费金额，单位：分，0为无限制', 0],
                ['number', 'max_discount', '最大优惠金额(分)', '请输入最大优惠金额，单位：分，0为无限制', 0],
                ['number', 'total_count', '发行总数量', '请输入发行总数量，0为不限制', 0],
                ['number', 'per_user_limit', '每用户限领数量', '请输入每用户限领数量', 1],
                ['number', 'valid_days', '有效天数', '请输入优惠券有效天数', 30],
                ['datetime', 'start_time', '活动开始时间', '请选择活动开始时间，留空为立即生效'],
                ['datetime', 'end_time', '活动结束时间', '请选择活动结束时间，留空为永久有效'],
                ['textarea', 'applicable_products', '适用商品ID列表', '请输入适用商品ID列表JSON格式<br>示例：[1,2,3] 或 {"category":[1,2],"products":[10,20]}', '', '', 'rows="3"'],
                ['radio', 'status', '状态', '选择优惠券状态', ['0' => '禁用', '1' => '启用'], 1],
                ['text', 'description', '优惠券描述', '请输入优惠券描述信息'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证优惠券代码唯一性（排除当前记录）
            $exists = CouponTemplateModel::where('code', $data['code'])->where('id', 'neq', $id)->find();
            if ($exists) {
                $this->error('优惠券代码已存在，请使用其他代码');
            }
            
            // 验证适用商品JSON格式
            if (!empty($data['applicable_products'])) {
                $products = json_decode($data['applicable_products'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('适用商品JSON格式错误');
                }
            }
            
            // 验证时间逻辑
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                    $this->error('结束时间必须晚于开始时间');
                }
            }
            
            if (CouponTemplateModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CouponTemplateModel::where('id', $id)->find();
        if (!$info) {
            $this->error('优惠券模板不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑优惠券模板') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'name', '优惠券名称', '请输入优惠券名称', '', 'required'],
                ['text', 'code', '优惠券代码', '请输入优惠券代码（唯一标识）', '', 'required'],
                ['radio', 'type', '优惠券类型', '选择优惠券类型', ['1' => '满减券', '2' => '折扣券', '3' => '固定金额券']],
                ['number', 'shop_id', '商品ID', '请输入关联商品ID', '', 'required'],
                ['number', 'discount_value', '优惠值(分)', '请输入优惠值，单位：分', '', 'required'],
                ['number', 'min_amount', '最低消费金额(分)', '请输入最低消费金额，单位：分，0为无限制'],
                ['number', 'max_discount', '最大优惠金额(分)', '请输入最大优惠金额，单位：分，0为无限制'],
                ['number', 'total_count', '发行总数量', '请输入发行总数量，0为不限制'],
                ['number', 'used_count', '已使用数量', '已使用的优惠券数量'],
                ['number', 'per_user_limit', '每用户限领数量', '请输入每用户限领数量'],
                ['number', 'valid_days', '有效天数', '请输入优惠券有效天数'],
                ['datetime', 'start_time', '活动开始时间', '请选择活动开始时间，留空为立即生效'],
                ['datetime', 'end_time', '活动结束时间', '请选择活动结束时间，留空为永久有效'],
                ['textarea', 'applicable_products', '适用商品ID列表', '请输入适用商品ID列表JSON格式<br>示例：[1,2,3] 或 {"category":[1,2],"products":[10,20]}', '', '', 'rows="3"'],
                ['radio', 'status', '状态', '选择优惠券状态', ['0' => '禁用', '1' => '启用']],
                ['text', 'description', '优惠券描述', '请输入优惠券描述信息'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CouponTemplateModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function batchGenerate()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $batch_count = (int)$data['batch_count'];
            $name_prefix = trim($data['name_prefix']);
            $code_prefix = trim($data['code_prefix']);
            
            if ($batch_count <= 0 || $batch_count > 100) {
                $this->error('批量生成数量必须在1-100之间');
            }
            
            if (empty($name_prefix) || empty($code_prefix)) {
                $this->error('请输入名称前缀和代码前缀');
            }
            
            $success_count = 0;
            $error_messages = [];
            
            for ($i = 1; $i <= $batch_count; $i++) {
                $coupon_data = [
                    'name' => $name_prefix . '_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'code' => $code_prefix . '_' . date('Ymd') . '_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'type' => $data['type'],
                    'shop_id' => $data['shop_id'],
                    'discount_value' => $data['discount_value'],
                    'min_amount' => $data['min_amount'],
                    'max_discount' => $data['max_discount'],
                    'total_count' => $data['total_count'],
                    'used_count' => 0,
                    'per_user_limit' => $data['per_user_limit'],
                    'valid_days' => $data['valid_days'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'applicable_products' => $data['applicable_products'],
                    'status' => $data['status'],
                    'description' => $data['description'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    if (CouponTemplateModel::create($coupon_data)) {
                        $success_count++;
                    }
                } catch (Exception $e) {
                    $error_messages[] = "第{$i}个模板创建失败：" . $e->getMessage();
                }
            }
            
            $message = "成功生成 {$success_count} 个优惠券模板";
            if (!empty($error_messages)) {
                $message .= "，以下模板创建失败：<br>" . implode('<br>', $error_messages);
            }
            
            $this->success($message, url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('批量生成优惠券模板')
            ->addFormItems([
                ['number', 'batch_count', '生成数量', '请输入要批量生成的数量（1-100）', 10, 'required'],
                ['text', 'name_prefix', '名称前缀', '请输入优惠券名称前缀', '', 'required'],
                ['text', 'code_prefix', '代码前缀', '请输入优惠券代码前缀', '', 'required'],
                ['radio', 'type', '优惠券类型', '选择优惠券类型', ['1' => '满减券', '2' => '折扣券', '3' => '固定金额券'], 1],
                ['number', 'shop_id', '商品ID', '请输入关联商品ID', '', 'required'],
                ['number', 'discount_value', '优惠值(分)', '请输入优惠值，单位：分', 0, 'required'],
                ['number', 'min_amount', '最低消费金额(分)', '请输入最低消费金额，单位：分，0为无限制', 0],
                ['number', 'max_discount', '最大优惠金额(分)', '请输入最大优惠金额，单位：分，0为无限制', 0],
                ['number', 'total_count', '发行总数量', '请输入发行总数量，0为不限制', 0],
                ['number', 'per_user_limit', '每用户限领数量', '请输入每用户限领数量', 1],
                ['number', 'valid_days', '有效天数', '请输入优惠券有效天数', 30],
                ['datetime', 'start_time', '活动开始时间', '请选择活动开始时间，留空为立即生效'],
                ['datetime', 'end_time', '活动结束时间', '请选择活动结束时间，留空为永久有效'],
                ['textarea', 'applicable_products', '适用商品ID列表', '请输入适用商品ID列表JSON格式<br>示例：[1,2,3]', '', '', 'rows="2"'],
                ['radio', 'status', '状态', '选择优惠券状态', ['0' => '禁用', '1' => '启用'], 1],
                ['text', 'description', '优惠券描述', '请输入优惠券描述信息'],
            ])
            ->fetch();
    }
} 
