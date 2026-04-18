<?php
// 用户优惠券管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\UserCouponModel;
use app\video\model\CouponTemplateModel;

class UserCoupon extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询，关联优惠券模板表获取模板信息
        $data_list = UserCouponModel::alias('uc')
        ->leftJoin('ts_coupon_template ct', 'uc.template_id = ct.id')
        ->field('uc.*, ct.name as template_name, ct.type as template_type, ct.discount_value')
        ->where($map)
        ->order('uc.id desc')
        ->paginate();

        cookie('ts_user_coupon', $map);
        
        // 统计用户优惠券信息
        $stats = [
            'unused_count' => UserCouponModel::where('status', 1)->count(),
            'used_count' => UserCouponModel::where('status', 2)->count(),
            'expired_count' => UserCouponModel::where('status', 0)->count(),
            'frozen_count' => UserCouponModel::where('status', 3)->count(),
            'total_count' => UserCouponModel::count(),
            'total_discount_amount' => UserCouponModel::where('status', 2)->sum('discount_amount'),
            'today_used' => UserCouponModel::where('status', 2)->whereTime('used_at', 'today')->count(),
            'today_expired' => UserCouponModel::where('status', 0)->whereTime('expire_at', 'today')->count(),
        ];
        
        // 计算使用率
        $usage_rate = $stats['total_count'] > 0 ? round((($stats['used_count'] + $stats['expired_count']) / $stats['total_count']) * 100, 2) : 0;
        
        // 优惠券状态定义
        $coupon_status = [
            0 => '已过期',
            1 => '未使用',
            2 => '已使用',
            3 => '已冻结'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("未使用：{$stats['unused_count']} | 已使用：{$stats['used_count']} | 已过期：{$stats['expired_count']} | 已冻结：{$stats['frozen_count']} | 总计：{$stats['total_count']} | 使用率：{$usage_rate}% | 总优惠金额：" . number_format($stats['total_discount_amount']/100, 2) . "元 | 今日使用：{$stats['today_used']} | 今日过期：{$stats['today_expired']}")
            ->setTableName('video/UserCouponModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['template_id', '模板ID'],
                ['template_name', '优惠券名称'],
                ['template_type', '类型', 'callback', function($value) {
                    $types = [1 => '满减券', 2 => '折扣券', 3 => '固定金额券'];
                    return isset($types[$value]) ? $types[$value] : '未知';
                }],
                ['coupon_code', '优惠券码', 'callback', function($value){
                    return mb_strlen($value) > 16 ? mb_substr($value, 0, 16).'...' : $value;
                }],
                ['status', '状态', 'callback', function($value) use ($coupon_status) {
                    $color_map = [0 => 'danger', 1 => 'success', 2 => 'info', 3 => 'warning'];
                    $color = isset($color_map[$value]) ? $color_map[$value] : 'default';
                    $text = isset($coupon_status[$value]) ? $coupon_status[$value] : '未知';
                    return "<span class='label label-{$color}'>{$text}</span>";
                }],
                ['order_id', '订单ID', 'callback', function($value){
                    return $value ? $value : '-';
                }],
                ['discount_amount', '优惠金额(分)', 'callback', function($value){
                    return $value > 0 ? number_format($value, 0) . '分 (' . number_format($value/100, 2) . '元)' : '-';
                }],
                ['obtained_at', '获得时间'],
                ['used_at', '使用时间', 'callback', function($value){
                    return $value ? $value : '-';
                }],
                ['expire_at', '过期时间', 'callback', function($value){
                    if (!$value) return '-';
                    $is_expired = strtotime($value) < time();
                    return $value . ($is_expired ? ' <span style="color:red;">(已过期)</span>' : '');
                }],
                ['created_at', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'uc.user_id', '用户ID'],
                ['text', 'uc.template_id', '模板ID'],
                ['text', 'uc.coupon_code', '优惠券码'],
                ['select', 'uc.status', '状态', '', $coupon_status],
                ['text', 'uc.order_id', '订单ID'],
                ['text', 'ct.name', '优惠券名称'],
                ['daterange', 'uc.obtained_at', '获得时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'uc.used_at', '使用时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'uc.expire_at', '过期时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '手动发放']) // 添加手动发放按钮
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
                if (strpos($param, 'obtained_at=') === 0) {
                    $daterangeValue = substr($param, strlen('obtained_at='));
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
        $groupBy = $isSameDay ? 'HOUR(obtained_at)' : 'DATE(obtained_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = UserCouponModel::whereTime('obtained_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as expired_count',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as unused_count',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as used_count',
            'SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as frozen_count',
            'SUM(discount_amount) as total_discount'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_count = array();
        $y_data_expired = array();
        $y_data_unused = array();
        $y_data_used = array();
        $y_data_frozen = array();
        $y_data_discount = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_count, $value['count']);
            array_push($y_data_expired, $value['expired_count']);
            array_push($y_data_unused, $value['unused_count']);
            array_push($y_data_used, $value['used_count']);
            array_push($y_data_frozen, $value['frozen_count']);
            array_push($y_data_discount, round($value['total_discount']/100, 2));
        }

        $x_data_json = json_encode($x_data);
        $y_data_count_json = json_encode($y_data_count);
        $y_data_expired_json = json_encode($y_data_expired);
        $y_data_unused_json = json_encode($y_data_unused);
        $y_data_used_json = json_encode($y_data_used);
        $y_data_frozen_json = json_encode($y_data_frozen);
        $y_data_discount_json = json_encode($y_data_discount);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '用户优惠券{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总发放量', '已过期', '未使用', '已使用', '已冻结', '优惠金额(元)']
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
            yAxis: [
                {
                    type: 'value',
                    name: '数量'
                },
                {
                    type: 'value',
                    name: '金额(元)',
                    position: 'right'
                }
            ],
            series: [
                {
                    name: '总发放量',
                    type: 'line',
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '已过期',
                    type: 'line',
                    data: {$y_data_expired_json},
                    itemStyle: {color: '#f56c6c'}
                },
                {
                    name: '未使用',
                    type: 'line',
                    data: {$y_data_unused_json},
                    itemStyle: {color: '#67c23a'}
                },
                {
                    name: '已使用',
                    type: 'line',
                    data: {$y_data_used_json},
                    itemStyle: {color: '#409eff'}
                },
                {
                    name: '已冻结',
                    type: 'line',
                    data: {$y_data_frozen_json},
                    itemStyle: {color: '#e6a23c'}
                },
                {
                    name: '优惠金额(元)',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: {$y_data_discount_json},
                    itemStyle: {color: '#909399'}
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
            $data['obtained_at'] = date('Y-m-d H:i:s');
            
            // 验证优惠券码唯一性
            $exists = UserCouponModel::where('coupon_code', $data['coupon_code'])->find();
            if ($exists) {
                $this->error('优惠券码已存在，请使用其他优惠券码');
            }
            
            // 验证模板是否存在
            $template = CouponTemplateModel::where('id', $data['template_id'])->find();
            if (!$template) {
                $this->error('优惠券模板不存在');
            }
            
            // 验证过期时间
            if (strtotime($data['expire_at']) <= time()) {
                $this->error('过期时间必须晚于当前时间');
            }
            
            if (UserCouponModel::create($data)) {
                $this->success('发放成功', url('index'));
            } else {
                $this->error('发放失败');
            }
        }

        // 获取可用的优惠券模板
        $templates = CouponTemplateModel::where('status', 1)->field('id,name')->select();
        $template_options = [];
        foreach ($templates as $template) {
            $template_options[$template['id']] = $template['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('手动发放优惠券') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['select', 'template_id', '优惠券模板', '请选择优惠券模板', $template_options, '', 'required'],
                ['text', 'coupon_code', '优惠券码', '请输入优惠券唯一码', '', 'required'],
                ['radio', 'status', '状态', '选择优惠券状态', ['1' => '未使用', '3' => '已冻结'], 1],
                ['datetime', 'expire_at', '过期时间', '请选择过期时间', '', 'required'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证优惠券码唯一性（排除当前记录）
            $exists = UserCouponModel::where('coupon_code', $data['coupon_code'])->where('id', 'neq', $id)->find();
            if ($exists) {
                $this->error('优惠券码已存在，请使用其他优惠券码');
            }
            
            // 如果状态改为已使用，记录使用时间
            if ($data['status'] == 2 && !isset($data['used_at'])) {
                $data['used_at'] = date('Y-m-d H:i:s');
            }
            
            if (UserCouponModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = UserCouponModel::where('id', $id)->find();
        if (!$info) {
            $this->error('用户优惠券不存在');
        }

        // 获取可用的优惠券模板
        $templates = CouponTemplateModel::where('status', 1)->field('id,name')->select();
        $template_options = [];
        foreach ($templates as $template) {
            $template_options[$template['id']] = $template['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑用户优惠券') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['select', 'template_id', '优惠券模板', '请选择优惠券模板', $template_options, '', 'required'],
                ['text', 'coupon_code', '优惠券码', '请输入优惠券唯一码', '', 'required'],
                ['radio', 'status', '状态', '选择优惠券状态', ['0' => '已过期', '1' => '未使用', '2' => '已使用', '3' => '已冻结']],
                ['number', 'order_id', '订单ID', '使用订单ID'],
                ['number', 'discount_amount', '实际优惠金额(分)', '实际优惠金额，单位：分'],
                ['datetime', 'obtained_at', '获得时间', '优惠券获得时间'],
                ['datetime', 'used_at', '使用时间', '优惠券使用时间'],
                ['datetime', 'expire_at', '过期时间', '请选择过期时间'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = UserCouponModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function freeze($id)
    {
        $coupon = UserCouponModel::where('id', $id)->find();
        if (!$coupon) {
            $this->error('优惠券不存在');
        }
        
        if ($coupon['status'] != 1) {
            $this->error('只能冻结未使用的优惠券');
        }
        
        if (UserCouponModel::where('id', $id)->update(['status' => 3, 'updated_at' => date('Y-m-d H:i:s')])) {
            $this->success('冻结成功');
        } else {
            $this->error('冻结失败');
        }
    }

    public function unfreeze($id)
    {
        $coupon = UserCouponModel::where('id', $id)->find();
        if (!$coupon) {
            $this->error('优惠券不存在');
        }
        
        if ($coupon['status'] != 3) {
            $this->error('只能解冻已冻结的优惠券');
        }
        
        // 检查是否过期
        $status = strtotime($coupon['expire_at']) > time() ? 1 : 0;
        
        if (UserCouponModel::where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')])) {
            $message = $status == 1 ? '解冻成功' : '解冻成功，但优惠券已过期';
            $this->success($message);
        } else {
            $this->error('解冻失败');
        }
    }

    public function batchIssue()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $user_ids = trim($data['user_ids']);
            $template_id = $data['template_id'];
            $expire_days = $data['expire_days'];
            
            if (empty($user_ids)) {
                $this->error('请输入用户ID列表');
            }
            
            // 验证模板是否存在
            $template = CouponTemplateModel::where('id', $template_id)->find();
            if (!$template) {
                $this->error('优惠券模板不存在');
            }
            
            $user_id_array = explode(',', $user_ids);
            $user_id_array = array_map('trim', $user_id_array);
            $user_id_array = array_filter($user_id_array);
            
            if (count($user_id_array) > 1000) {
                $this->error('单次批量发放不能超过1000个用户');
            }
            
            $success_count = 0;
            $error_messages = [];
            $expire_time = date('Y-m-d H:i:s', time() + $expire_days * 24 * 3600);
            
            foreach ($user_id_array as $user_id) {
                if (!is_numeric($user_id)) {
                    $error_messages[] = "用户ID {$user_id} 格式错误";
                    continue;
                }
                
                $coupon_code = 'UC' . date('YmdHis') . sprintf('%06d', $user_id) . sprintf('%04d', mt_rand(1000, 9999));
                
                $coupon_data = [
                    'user_id' => (int)$user_id,
                    'template_id' => $template_id,
                    'coupon_code' => $coupon_code,
                    'status' => 1,
                    'discount_amount' => 0,
                    'obtained_at' => date('Y-m-d H:i:s'),
                    'expire_at' => $expire_time,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    if (UserCouponModel::create($coupon_data)) {
                        $success_count++;
                    }
                } catch (Exception $e) {
                    $error_messages[] = "用户 {$user_id} 发放失败：" . $e->getMessage();
                }
            }
            
            $message = "成功发放 {$success_count} 张优惠券";
            if (!empty($error_messages)) {
                $message .= "，以下用户发放失败：<br>" . implode('<br>', array_slice($error_messages, 0, 10));
                if (count($error_messages) > 10) {
                    $message .= "<br>... 还有 " . (count($error_messages) - 10) . " 个错误";
                }
            }
            
            $this->success($message, url('index'));
        }

        // 获取可用的优惠券模板
        $templates = CouponTemplateModel::where('status', 1)->field('id,name')->select();
        $template_options = [];
        foreach ($templates as $template) {
            $template_options[$template['id']] = $template['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('批量发放优惠券')
            ->addFormItems([
                ['textarea', 'user_ids', '用户ID列表', '请输入用户ID列表，多个ID用英文逗号分隔<br>示例：1001,1002,1003', '', 'required', 'rows="5"'],
                ['select', 'template_id', '优惠券模板', '请选择优惠券模板', $template_options, '', 'required'],
                ['number', 'expire_days', '有效天数', '请输入优惠券有效天数', 30, 'required'],
            ])
            ->fetch();
    }

    public function expireProcess()
    {
        if (request()->isPost()) {
            $action = input('post.action');
            
            if ($action == 'check') {
                // 检查过期优惠券数量
                $expire_count = UserCouponModel::where('status', 1)
                    ->where('expire_at', '<', date('Y-m-d H:i:s'))
                    ->count();
                
                $this->success("发现 {$expire_count} 张过期优惠券");
            } elseif ($action == 'process') {
                // 处理过期优惠券
                $result = UserCouponModel::where('status', 1)
                    ->where('expire_at', '<', date('Y-m-d H:i:s'))
                    ->update([
                        'status' => 0,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                $this->success("成功处理 {$result} 张过期优惠券", url('index'));
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('过期优惠券处理')
            ->addFormItems([
                ['static', 'info', '操作说明', '此功能用于处理已过期但状态仍为"未使用"的优惠券<br><span style="color:red;">注意：此操作不可逆，请谨慎操作</span>'],
                ['radio', 'action', '操作类型', '选择要执行的操作', ['check' => '检查过期数量', 'process' => '处理过期优惠券'], 'check'],
            ])
            ->fetch();
    }
} 
