<?php
// 用户任务合集(场景对列表)
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class MjCollectionTask extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_user_mj_collection_task')->where($map)
        ->order('time desc')
        ->paginate();

    
        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        
        return ZBuilder::make('table')
            ->setTableName('video/MjCollectionTaskModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', 'user_id'],
                ['user_type', '用户类型','status','',[0=>'速推',1=>'网页']],
                ['action', '任务类型'],
                ['collection_id', '合集ID'],
                ['book_image_id', '客户端关联ID'],
                ['status', '状态','text.edit'],
                // ['prompt', '提示词'],
                ['prompt', 'prompt', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['image_url', '垫图'],
                ['cmd', '作画命令'],
                ['fail_reason', 'failReason'],
                ['btn_mode', '自动放大','text.edit'],
                ['up_type', '放大标识','text.edit'],
                ['skin_cnt', '跳过次数'],
                ['retry_cnt', '重试'],
                ['start_time', '开始时间'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', 'id'],
                ['text', 'user_id', 'user_id'],
                ['text', 'up_type', 'up_type'],
                ['select', 'user_type', '用户类型', '', '', ['0'=>'速推','1'=>'网页']],
                ['text', 'collection_id', '合集ID'],
                ['text', 'book_image_id', '客户端关联ID'],
                ['text', 'status', '状态'],
                ['text', 'fail_reason', 'fail_reason'],
              
            ])

            ->addRightButton('info',[
                'title'=>'查看合集详情',
                'icon'  => 'fa fa-fw fa-location-arrow',
                
                'href'=>'/admin.php/video/mj_jobs/index.html?_s=time=|time=|user_type=|id=|task_id=|from_task_id=|collection_id=__collection_id__|discordInstanceId=|action=|status=&_o=time=between%20time|time=between%20time|user_type=eq|id=eq|task_id=eq|from_task_id=eq|collection_id=eq|discordInstanceId=eq|action=eq|status=eq',
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ])




            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setExtraHtml($content_html, 'toolbar_top')
            ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
            ->setExtraJs($js)
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }


    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::connect('translate')->table('ts_user_mj_collection_task')->where('id',$id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::connect('translate')->table('ts_user_mj_collection_task')->where('id',$id)->find();

        return ZBuilder::make('form')
             ->addFormItems([
                
                ['textarea', 'prompt', 'prompt'],
            ])
          
            ->setFormData($info)
            ->fetch();
    }



    public function getChartjs() {
        // 1. 获取参数
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
    
        // 2. 解析开始时间和结束时间
        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            // 如果没有提供日期范围，则使用当前日期
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }
    
        // 判断是否为同一天
        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
    
        // 3. 数据库查询并按日期或小时分组计数
        $groupBy = $isSameDay ? 'HOUR(time)' : 'DATE(time)';
        $xAxisType = $isSameDay ? '小时' : '日期';
    
        $data_list = DB::connect('translate')
            ->table('ts_user_mj_collection_task')
            ->whereTime('time', 'between', [$startDate, $endDate])
            ->field([
                "{$groupBy} as axisValue",
                'COUNT(*) as count'
            ])
            ->group('axisValue')
            ->select();
    
        // 初始化数组
        $x_data = array();
        $y_data = array();
    
        // 遍历查询结果
        foreach ($data_list as $value) {
            // 添加到 x 轴和 y 轴数据
            array_push($x_data, $value['axisValue']);
            array_push($y_data, $value['count']);
        }
    
        // JSON 编码数据以用于 JavaScript
        $x_data_json = json_encode($x_data);
        $y_data_json = json_encode($y_data);
    
        // 创建 ECharts JavaScript 代码
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
                    data: ['任务量']
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
                        name: '任务量',
                        type: 'line',
                        data: {$y_data_json},
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
    



}
