<?php
// 用户签到记录
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class SigninRecords extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_signin_records')->where($map)
        ->order('signin_time desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html = "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_signin_records', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/SigninRecordsModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['signin_time', '签到时间'],
                ['signin_amount_cents', '签到金额(分)'],
                ['deducted_amount_cents', '扣除金额(分)'],
                ['signin_date', '签到日期'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'user_id', '用户ID'],
                ['date', 'signin_date', '签到日期'],
                ['daterange', 'signin_time', '签到时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('add') // 添加新增按钮
            ->addRightButtons(['edit', 'delete']) // 添加右侧操作按钮
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
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
                if (strpos($param, 'signin_time=') === 0) {
                    $daterangeValue = substr($param, strlen('signin_time='));
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
        $groupBy = $isSameDay ? 'HOUR(signin_time)' : 'DATE(signin_time)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = DB::connect('translate')->table('ts_signin_records')
        ->whereTime('signin_time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count',
            'SUM(signin_amount_cents) as total_signin_amount',
            'SUM(deducted_amount_cents) as total_deducted_amount',
            'SUM(signin_amount_cents - deducted_amount_cents) as net_amount'
        ])
        ->group('axisValue')
        ->select();

        $x_data = array();
        $y_data_count = array();
        $y_data_signin_amount = array();
        $y_data_deducted_amount = array();
        $y_data_net_amount = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_count, $value['count']);
            array_push($y_data_signin_amount, $value['total_signin_amount']);
            array_push($y_data_deducted_amount, $value['total_deducted_amount']);
            array_push($y_data_net_amount, $value['net_amount']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_count_json = json_encode($y_data_count);
        $y_data_signin_amount_json = json_encode($y_data_signin_amount);
        $y_data_deducted_amount_json = json_encode($y_data_deducted_amount);
        $y_data_net_amount_json = json_encode($y_data_net_amount);

        $display_date = $daterangeValue ? $daterangeValue : $startDate;
        $js = "
        <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main'));
        var option;
        option = {
            title: {
                text: '签到记录{$xAxisType}统计 - {$display_date}'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {
                data: ['签到次数', '签到金额(分)', '扣除金额(分)', '实际金额(分)']
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
                data: {$x_data_json}
            },
            yAxis: [
                {
                    type: 'value',
                    name: '次数',
                    position: 'left'
                },
                {
                    type: 'value',
                    name: '金额(分)',
                    position: 'right'
                }
            ],
            series: [
                {
                    name: '签到次数',
                    type: 'bar',
                    yAxisIndex: 0,
                    data: {$y_data_count_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '签到金额(分)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_signin_amount_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '扣除金额(分)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_deducted_amount_json},
                    label: {
                        show: true,
                        position: 'top'
                    }
                },
                {
                    name: '实际金额(分)',
                    type: 'line',
                    yAxisIndex: 1,
                    data: {$y_data_net_amount_json},
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
           
           // 设置创建和更新时间
           $now = date('Y-m-d H:i:s');
           $data['created_at'] = $now;
           $data['updated_at'] = $now;
           
           // 从签到时间提取签到日期
           if (!empty($data['signin_time'])) {
               $data['signin_date'] = date('Y-m-d', strtotime($data['signin_time']));
           }
           
           $r = DB::connect('translate')->table('ts_signin_records')->insert($data);
           if ($r) {
               $this->success('新增成功', 'index');
           } else {
               $this->error('新增失败');
           }
       }
                  
       // 显示添加页面
       return ZBuilder::make('form')
            ->addFormItems([
               ['number', 'user_id', '用户ID', '用户的唯一标识'],      
               ['datetime', 'signin_time', '签到时间', '', 'YYYY-MM-DD HH:mm:ss'],
               ['number', 'signin_amount_cents', '签到金额(分)', '用户签到获得的金额，单位为分'],
               ['number', 'deducted_amount_cents', '扣除金额(分)', '从签到金额中扣除的金额，单位为分'],
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
           
           // 更新时间
           $data['updated_at'] = date('Y-m-d H:i:s');
           
           // 从签到时间提取签到日期
           if (!empty($data['signin_time'])) {
               $data['signin_date'] = date('Y-m-d', strtotime($data['signin_time']));
           }

           $r = DB::connect('translate')->table('ts_signin_records')->where('id', $id)->update($data);
           if ($r !== false) {
               $this->success('编辑成功', 'index');
           } else {
               $this->error('编辑失败');
           }
       }

       $info = DB::connect('translate')->table('ts_signin_records')->where('id', $id)->find();

       return ZBuilder::make('form')
            ->addFormItems([
               ['hidden', 'id'],
               ['number', 'user_id', '用户ID', '用户的唯一标识'],      
               ['datetime', 'signin_time', '签到时间', '', 'YYYY-MM-DD HH:mm:ss'],
               ['number', 'signin_amount_cents', '签到金额(分)', '用户签到获得的金额，单位为分'],
               ['number', 'deducted_amount_cents', '扣除金额(分)', '从签到金额中扣除的金额，单位为分'],
            ])
           ->setFormData($info)
           ->fetch();
   }
   
   public function delete($ids = null)
   {
       if ($ids === null) $this->error('缺少参数');
       
       $r = DB::connect('translate')->table('ts_signin_records')->where('id', 'in', $ids)->delete();
       if ($r !== false) {
           $this->success('删除成功');
       } else {
           $this->error('删除失败');
       }
   }
} 