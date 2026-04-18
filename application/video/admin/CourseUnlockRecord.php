<?php
// 用户课程解锁记录管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CourseUnlockRecordModel;

class CourseUnlockRecord extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        
        // 特殊处理过期时间搜索
        $expire_time_search = input('param.expire_time', '', 'trim');
        if ($expire_time_search !== '') {
            // 移除getMap中可能添加的expire_time搜索条件
            foreach ($map as $key => $condition) {
                if (is_array($condition) && isset($condition[0]) && $condition[0] == 'expire_time') {
                    unset($map[$key]);
                }
            }
            
            // 根据搜索内容添加特殊条件
            if (strpos($expire_time_search, '永久有效') !== false || strpos($expire_time_search, '永久') !== false) {
                // 搜索永久有效的记录（expire_time为null）
                $map[] = ['expire_time', 'exp', 'IS NULL'];
            } elseif (strpos($expire_time_search, '已过期') !== false || strpos($expire_time_search, '过期') !== false) {
                // 搜索已过期的记录
                $map[] = ['expire_time', 'exp', "IS NOT NULL AND expire_time < '" . date('Y-m-d H:i:s') . "'"];
            } elseif (strpos($expire_time_search, '有效') !== false && strpos($expire_time_search, '永久') === false) {
                // 搜索有效但非永久的记录
                $map[] = ['expire_time', 'exp', "IS NOT NULL AND expire_time > '" . date('Y-m-d H:i:s') . "'"];
            } else {
                // 普通时间搜索，直接使用DATE_FORMAT函数进行搜索
                $map[] = ['expire_time', 'exp', "IS NOT NULL AND DATE_FORMAT(expire_time, '%Y-%m-%d %H:%i:%s') LIKE '%" . addslashes($expire_time_search) . "%'"];
            }
            
            // 重新索引数组
            $map = array_values($map);
        }
        
        // 使用数据库连接进行查询
        $data_list = CourseUnlockRecordModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_course_unlock_record', $map);
        
        // 统计课程解锁记录信息
        $stats = [
            'total_records' => CourseUnlockRecordModel::count(),
            'valid_records' => CourseUnlockRecordModel::where('status', 1)->count(),
            'invalid_records' => CourseUnlockRecordModel::where('status', 0)->count(),
            'paid_records' => CourseUnlockRecordModel::where('unlock_type', 0)->count(),
            'member_free_records' => CourseUnlockRecordModel::where('unlock_type', 1)->count(),
            'activity_gift_records' => CourseUnlockRecordModel::where('unlock_type', 2)->count(),
            'total_revenue' => CourseUnlockRecordModel::where('unlock_type', 0)->where('status', 1)->sum('price'),
            'today_records' => CourseUnlockRecordModel::whereTime('created_at', 'today')->count(),
            'this_week_records' => CourseUnlockRecordModel::whereTime('created_at', 'week')->count(),
            'this_month_records' => CourseUnlockRecordModel::whereTime('created_at', 'month')->count(),
            'expired_records' => CourseUnlockRecordModel::where('expire_time', '<', date('Y-m-d H:i:s'))->where('expire_time', 'not null')->count(),
        ];
        
        // 解锁类型定义
        $unlock_types = [
            0 => '付费购买',
            1 => '会员免费',
            2 => '活动赠送'
        ];
        
        return ZBuilder::make('table')
            ->setPageTips("总记录：{$stats['total_records']} | 有效：{$stats['valid_records']} | 无效：{$stats['invalid_records']} | 付费：{$stats['paid_records']} | 会员：{$stats['member_free_records']} | 赠送：{$stats['activity_gift_records']} | 总收入：￥" . number_format($stats['total_revenue'], 2) . " | 今日：{$stats['today_records']} | 本周：{$stats['this_week_records']} | 本月：{$stats['this_month_records']} | 已过期：{$stats['expired_records']}")
            ->setTableName('video/CourseUnlockRecordModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['course_id', '课程ID'],
                ['unlock_type', '解锁类型', 'callback', function($value) use ($unlock_types) {
                    $color_map = [0 => 'primary', 1 => 'success', 2 => 'warning'];
                    $color = isset($color_map[$value]) ? $color_map[$value] : 'default';
                    $text = isset($unlock_types[$value]) ? $unlock_types[$value] : '未知';
                    return "<span class='label label-{$color}'>{$text}</span>";
                }],
                ['price', '解锁价格', 'callback', function($value){
                    return $value > 0 ? '￥' . number_format($value, 2) : '免费';
                }],
                ['order_id', '订单ID', 'callback', function($value){
                    return $value ? $value : '-';
                }],
                ['expire_time', '过期时间', 'callback', function($value){
                    // 处理空值或无效日期
                    if (empty($value) || $value == '0000-00-00 00:00:00' || is_null($value)) {
                        return '<span style="color:blue;font-weight:bold;">永久有效</span>';
                    }
                    
                    // 确保是有效的日期时间格式
                    $expire_timestamp = strtotime($value);
                    if ($expire_timestamp === false) {
                        return '<span style="color:gray;">日期格式错误</span>';
                    }
                    
                    $current_timestamp = time();
                    $is_expired = $expire_timestamp < $current_timestamp;
                    
                    // 格式化显示时间
                    $formatted_time = date('Y-m-d H:i:s', $expire_timestamp);
                    
                    if ($is_expired) {
                        $color = 'red';
                        $status = '(已过期)';
                    } else {
                        $color = 'green';
                        $status = '(有效)';
                        
                        // 计算剩余时间
                        $remaining_seconds = $expire_timestamp - $current_timestamp;
                        $remaining_days = floor($remaining_seconds / (24 * 3600));
                        if ($remaining_days > 0) {
                            $status = "(剩余{$remaining_days}天)";
                        } else {
                            $remaining_hours = floor($remaining_seconds / 3600);
                            if ($remaining_hours > 0) {
                                $status = "(剩余{$remaining_hours}小时)";
                            } else {
                                $status = "(即将过期)";
                            }
                        }
                    }
                    
                    return "<span style='color:{$color};'>{$formatted_time}<br><small>{$status}</small></span>";
                }],
                ['status', '状态', 'callback', function($value) {
                    $color = $value == 1 ? 'success' : 'danger';
                    $text = $value == 1 ? '有效' : '无效';
                    return "<span class='label label-{$color}'>{$text}</span>";
                }],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['text', 'course_id', '课程ID'],
                ['select', 'unlock_type', '解锁类型', '', $unlock_types],
                ['text', 'order_id', '订单ID'],
                ['select', 'status', '状态', '', ['1' => '有效', '0' => '无效']],
                ['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'expire_time', '过期时间'],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->addTopButton('add', ['title' => '新增记录']) // 添加新增按钮
            ->addTopButton('custom', ['title' => '批量解锁', 'href' => url('batchUnlock')]) // 添加批量解锁按钮
            ->addTopButton('custom', ['title' => '过期处理', 'href' => url('expireProcess'), 'class' => 'btn btn-warning']) // 添加过期处理按钮
            ->addTopButton('custom', ['title' => '统计报表', 'href' => url('statistics')]) // 添加统计报表按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButton('custom', ['title' => '延期', 'href' => url('extend', ['id' => '__id__']), 'class' => 'btn btn-info btn-xs'], 'status = 1') // 添加延期按钮
            ->addRightButton('custom', ['title' => '禁用', 'href' => url('disableRecord', ['id' => '__id__']), 'class' => 'btn btn-warning btn-xs'], 'status = 1') // 添加禁用按钮
            ->addRightButton('custom', ['title' => '启用', 'href' => url('enableRecord', ['id' => '__id__']), 'class' => 'btn btn-success btn-xs'], 'status = 0') // 添加启用按钮
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
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
        $groupBy = $isSameDay ? 'HOUR(created_at)' : 'DATE(created_at)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = CourseUnlockRecordModel::whereTime('created_at', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as total_count',
            'SUM(CASE WHEN unlock_type = 0 THEN 1 ELSE 0 END) as paid_count',
            'SUM(CASE WHEN unlock_type = 1 THEN 1 ELSE 0 END) as member_count',
            'SUM(CASE WHEN unlock_type = 2 THEN 1 ELSE 0 END) as gift_count',
            'SUM(CASE WHEN unlock_type = 0 THEN price ELSE 0 END) as revenue',
            'COUNT(DISTINCT user_id) as unique_users',
            'COUNT(DISTINCT course_id) as unique_courses'
        ])
        ->group('axisValue')
        ->order('axisValue asc')
        ->select();

        $x_data = array();
        $y_data_total = array();
        $y_data_paid = array();
        $y_data_member = array();
        $y_data_gift = array();
        $y_data_revenue = array();
        $y_data_users = array();
        $y_data_courses = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_total, $value['total_count']);
            array_push($y_data_paid, $value['paid_count']);
            array_push($y_data_member, $value['member_count']);
            array_push($y_data_gift, $value['gift_count']);
            array_push($y_data_revenue, round($value['revenue'], 2));
            array_push($y_data_users, $value['unique_users']);
            array_push($y_data_courses, $value['unique_courses']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_total_json = json_encode($y_data_total);
        $y_data_paid_json = json_encode($y_data_paid);
        $y_data_member_json = json_encode($y_data_member);
        $y_data_gift_json = json_encode($y_data_gift);
        $y_data_revenue_json = json_encode($y_data_revenue);
        $y_data_users_json = json_encode($y_data_users);
        $y_data_courses_json = json_encode($y_data_courses);

        $display_date = $daterangeValue ? $daterangeValue : ($startDate . ' - ' . date('Y-m-d', strtotime($endDate . ' -1 day')));
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '课程解锁记录{$xAxisType}趋势 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['总解锁数', '付费购买', '会员免费', '活动赠送', '收入金额', '解锁用户', '解锁课程']
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
                    name: '总解锁数',
                    type: 'line',
                    data: {$y_data_total_json},
                    label: {
                        show: true,
                        position: 'top'
                    },
                    itemStyle: {color: '#409eff'}
                },
                {
                    name: '付费购买',
                    type: 'line',
                    data: {$y_data_paid_json},
                    itemStyle: {color: '#f56c6c'}
                },
                {
                    name: '会员免费',
                    type: 'line',
                    data: {$y_data_member_json},
                    itemStyle: {color: '#67c23a'}
                },
                {
                    name: '活动赠送',
                    type: 'line',
                    data: {$y_data_gift_json},
                    itemStyle: {color: '#e6a23c'}
                },
                {
                    name: '收入金额',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: {$y_data_revenue_json},
                    itemStyle: {color: '#909399'}
                },
                {
                    name: '解锁用户',
                    type: 'line',
                    data: {$y_data_users_json},
                    itemStyle: {color: '#9254de'}
                },
                {
                    name: '解锁课程',
                    type: 'line',
                    data: {$y_data_courses_json},
                    itemStyle: {color: '#36cfc9'}
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
            
            // 验证价格格式
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                $this->error('价格格式错误');
            }
            
            // 验证过期时间
            if (!empty($data['expire_time']) && strtotime($data['expire_time']) <= time()) {
                $this->error('过期时间必须晚于当前时间');
            }
            
            // 检查是否已存在相同的解锁记录
            $exists = CourseUnlockRecordModel::where('user_id', $data['user_id'])
                ->where('course_id', $data['course_id'])
                ->where('status', 1)
                ->find();
            if ($exists) {
                $this->error('该用户已解锁此课程，请勿重复添加');
            }
            
            if (CourseUnlockRecordModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增课程解锁记录') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'course_id', '课程ID', '请输入课程ID', '', 'required'],
                ['radio', 'unlock_type', '解锁类型', '选择解锁类型', ['0' => '付费购买', '1' => '会员免费', '2' => '活动赠送'], 0],
                ['number', 'price', '解锁价格', '请输入解锁价格（元）', 0, '', 'step="0.01"'],
                ['text', 'order_id', '关联订单ID', '请输入关联的订单ID（付费购买时填写）'],
                ['datetime', 'expire_time', '过期时间', '请选择过期时间，留空表示永久有效'],
                ['radio', 'status', '状态', '选择记录状态', ['0' => '无效', '1' => '有效'], 1],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // 验证价格格式
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                $this->error('价格格式错误');
            }
            
            // 验证过期时间
            if (!empty($data['expire_time']) && strtotime($data['expire_time']) <= time()) {
                $this->error('过期时间必须晚于当前时间');
            }
            
            if (CourseUnlockRecordModel::where('id', $id)->update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CourseUnlockRecordModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑课程解锁记录') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['number', 'user_id', '用户ID', '请输入用户ID', '', 'required'],
                ['number', 'course_id', '课程ID', '请输入课程ID', '', 'required'],
                ['radio', 'unlock_type', '解锁类型', '选择解锁类型', ['0' => '付费购买', '1' => '会员免费', '2' => '活动赠送']],
                ['number', 'price', '解锁价格', '请输入解锁价格（元）', '', '', 'step="0.01"'],
                ['text', 'order_id', '关联订单ID', '请输入关联的订单ID'],
                ['datetime', 'expire_time', '过期时间', '请选择过期时间，留空表示永久有效'],
                ['radio', 'status', '状态', '选择记录状态', ['0' => '无效', '1' => '有效']],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CourseUnlockRecordModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function extend($id)
    {
        if (request()->isPost()) {
            $data = input('post.');
            $extend_days = (int)$data['extend_days'];
            
            if ($extend_days <= 0) {
                $this->error('延期天数必须大于0');
            }
            
            $record = CourseUnlockRecordModel::where('id', $id)->find();
            if (!$record) {
                $this->error('记录不存在');
            }
            
            // 计算新的过期时间
            $current_expire = $record['expire_time'] ? strtotime($record['expire_time']) : time();
            $new_expire_time = date('Y-m-d H:i:s', $current_expire + $extend_days * 24 * 3600);
            
            if (CourseUnlockRecordModel::where('id', $id)->update([
                'expire_time' => $new_expire_time,
                'updated_at' => date('Y-m-d H:i:s')
            ])) {
                $this->success('延期成功', url('index'));
            } else {
                $this->error('延期失败');
            }
        }

        $info = CourseUnlockRecordModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->setPageTitle('延期课程解锁')
            ->addFormItems([
                ['static', 'info', '当前信息', "用户ID：{$info['user_id']}<br>课程ID：{$info['course_id']}<br>当前过期时间：" . ($info['expire_time'] ? $info['expire_time'] : '永久有效')],
                ['number', 'extend_days', '延期天数', '请输入要延期的天数', 30, 'required'],
            ])
            ->fetch();
    }

    public function disableRecord($id)
    {
        $record = CourseUnlockRecordModel::where('id', $id)->find();
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record['status'] != 1) {
            $this->error('只能禁用有效的记录');
        }
        
        if (CourseUnlockRecordModel::where('id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')])) {
            $this->success('禁用成功');
        } else {
            $this->error('禁用失败');
        }
    }

    public function enableRecord($id)
    {
        $record = CourseUnlockRecordModel::where('id', $id)->find();
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record['status'] != 0) {
            $this->error('只能启用无效的记录');
        }
        
        if (CourseUnlockRecordModel::where('id', $id)->update(['status' => 1, 'updated_at' => date('Y-m-d H:i:s')])) {
            $this->success('启用成功');
        } else {
            $this->error('启用失败');
        }
    }

    public function batchUnlock()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $user_ids = trim($data['user_ids']);
            $course_ids = trim($data['course_ids']);
            $unlock_type = $data['unlock_type'];
            $price = $data['price'];
            $expire_days = $data['expire_days'];
            
            if (empty($user_ids) || empty($course_ids)) {
                $this->error('请输入用户ID和课程ID列表');
            }
            
            $user_id_array = explode(',', $user_ids);
            $course_id_array = explode(',', $course_ids);
            
            $user_id_array = array_map('trim', $user_id_array);
            $course_id_array = array_map('trim', $course_id_array);
            
            $user_id_array = array_filter($user_id_array);
            $course_id_array = array_filter($course_id_array);
            
            if (count($user_id_array) * count($course_id_array) > 1000) {
                $this->error('批量解锁记录不能超过1000条');
            }
            
            $success_count = 0;
            $error_messages = [];
            $expire_time = $expire_days > 0 ? date('Y-m-d H:i:s', time() + $expire_days * 24 * 3600) : null;
            
            foreach ($user_id_array as $user_id) {
                foreach ($course_id_array as $course_id) {
                    if (!is_numeric($user_id) || !is_numeric($course_id)) {
                        $error_messages[] = "用户ID {$user_id} 或课程ID {$course_id} 格式错误";
                        continue;
                    }
                    
                    // 检查是否已存在
                    $exists = CourseUnlockRecordModel::where('user_id', $user_id)
                        ->where('course_id', $course_id)
                        ->where('status', 1)
                        ->find();
                    if ($exists) {
                        $error_messages[] = "用户 {$user_id} 已解锁课程 {$course_id}";
                        continue;
                    }
                    
                    $record_data = [
                        'user_id' => (int)$user_id,
                        'course_id' => (int)$course_id,
                        'unlock_type' => $unlock_type,
                        'price' => $price,
                        'expire_time' => $expire_time,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    try {
                        if (CourseUnlockRecordModel::create($record_data)) {
                            $success_count++;
                        }
                    } catch (Exception $e) {
                        $error_messages[] = "用户 {$user_id} 课程 {$course_id} 解锁失败：" . $e->getMessage();
                    }
                }
            }
            
            $message = "成功解锁 {$success_count} 条记录";
            if (!empty($error_messages)) {
                $message .= "，以下记录处理失败：<br>" . implode('<br>', array_slice($error_messages, 0, 20));
                if (count($error_messages) > 20) {
                    $message .= "<br>... 还有 " . (count($error_messages) - 20) . " 个错误";
                }
            }
            
            $this->success($message, url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('批量解锁课程')
            ->addFormItems([
                ['textarea', 'user_ids', '用户ID列表', '请输入用户ID列表，多个ID用英文逗号分隔<br>示例：1001,1002,1003', '', 'required', 'rows="3"'],
                ['textarea', 'course_ids', '课程ID列表', '请输入课程ID列表，多个ID用英文逗号分隔<br>示例：101,102,103', '', 'required', 'rows="3"'],
                ['radio', 'unlock_type', '解锁类型', '选择解锁类型', ['0' => '付费购买', '1' => '会员免费', '2' => '活动赠送'], 2],
                ['number', 'price', '解锁价格', '请输入解锁价格（元）', 0, '', 'step="0.01"'],
                ['number', 'expire_days', '有效天数', '请输入有效天数，0表示永久有效', 0],
            ])
            ->fetch();
    }

    public function expireProcess()
    {
        if (request()->isPost()) {
            $action = input('post.action');
            
            if ($action == 'check') {
                // 检查过期记录数量
                $expire_count = CourseUnlockRecordModel::where('status', 1)
                    ->where('expire_time', '<', date('Y-m-d H:i:s'))
                    ->where('expire_time', 'not null')
                    ->count();
                
                $this->success("发现 {$expire_count} 条过期记录");
            } elseif ($action == 'process') {
                // 处理过期记录
                $result = CourseUnlockRecordModel::where('status', 1)
                    ->where('expire_time', '<', date('Y-m-d H:i:s'))
                    ->where('expire_time', 'not null')
                    ->update([
                        'status' => 0,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                $this->success("成功处理 {$result} 条过期记录", url('index'));
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('过期记录处理')
            ->addFormItems([
                ['static', 'info', '操作说明', '此功能用于处理已过期但状态仍为"有效"的课程解锁记录<br><span style="color:red;">注意：此操作不可逆，请谨慎操作</span>'],
                ['radio', 'action', '操作类型', '选择要执行的操作', ['check' => '检查过期数量', 'process' => '处理过期记录'], 'check'],
            ])
            ->fetch();
    }

    public function statistics()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            
            // 基础统计
            $basic_stats = [
                'total_records' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->count(),
                'paid_records' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->where('unlock_type', 0)->count(),
                'member_records' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->where('unlock_type', 1)->count(),
                'gift_records' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->where('unlock_type', 2)->count(),
                'total_revenue' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->where('unlock_type', 0)->sum('price'),
                'unique_users' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->count('DISTINCT user_id'),
                'unique_courses' => CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])->count('DISTINCT course_id'),
            ];
            
            // 热门课程TOP10
            $popular_courses = CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])
                ->field([
                    'course_id',
                    'COUNT(*) as unlock_count',
                    'SUM(price) as total_revenue'
                ])
                ->group('course_id')
                ->order('unlock_count desc')
                ->limit(10)
                ->select();
            
            // 活跃用户TOP10
            $active_users = CourseUnlockRecordModel::whereTime('created_at', 'between', [$start_date, $end_date])
                ->field([
                    'user_id',
                    'COUNT(*) as unlock_count',
                    'SUM(price) as total_spent'
                ])
                ->group('user_id')
                ->order('unlock_count desc')
                ->limit(10)
                ->select();
            
            $result = [
                'basic_stats' => $basic_stats,
                'popular_courses' => $popular_courses,
                'active_users' => $active_users
            ];
            
            $this->success('统计完成', '', $result);
        }

        return ZBuilder::make('form')
            ->setPageTitle('课程解锁统计报表')
            ->addFormItems([
                ['date', 'start_date', '开始日期', '请选择开始日期', date('Y-m-d', strtotime('-30 days')), 'required'],
                ['date', 'end_date', '结束日期', '请选择结束日期', date('Y-m-d'), 'required'],
                ['static', 'info', '说明', '统计指定日期范围内的课程解锁数据，包括：<br>
                    • 基础统计：总解锁数、各类型解锁数、总收入等<br>
                    • 热门课程：解锁次数最多的前10门课程<br>
                    • 活跃用户：解锁课程最多的前10名用户'],
            ])
            ->setAjax(false)
            ->fetch();
    }
} 
