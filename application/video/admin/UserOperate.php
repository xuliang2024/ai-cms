<?php
// 用户表运营使用页面
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;

class UserOperate extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list =  DB::connect('translate')->table('ts_users')->where($map)
        ->order('time desc')
        ->paginate();

        $js = $this->getChartjs();
        $content_html =  "";
        if($js != ""){
            $content_html = '<div id="main" style="width: auto;height:300px;"></div>';    
        }

        cookie('ts_users_operate', $map);
        return ZBuilder::make('table')
            
            
            // ->setConnection('translate')
             // ->setTableName('users') // 设置数据表名
             ->setTableName('video/UserModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', 'name'],
                
                ['phone', 'phone' ],
                ['open_id', 'open_id' ],
                ['token', 'token'],
                ['vip_level', '会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                ['from_user_id', '邀请' ],
                
                ['vip_time', 'vip到期时间' ],
                
                ['commission_rate', '佣金比例'],
                ['pay_cnt', '支付次数'],
                
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
                
            ])

           


            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', 'user_id'],
                ['text', 'name', 'name'],
                ['text', 'phone', 'phone'],
                // ['text', 'token', 'token'],
                ['text', 'commission_rate', '佣金比例'],
                ['text', 'from_user_id', '一级渠道'],
                 ['select', 'vip_level', '会员级别', '', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
              
            ])
            
            
            // ->addRightButtons(['edit']) // 批量添加右侧按钮//,'delete'
             ->addRightButton('edit',[
                'title'=>'编辑',
                'class'=>'btn btn-success btn-square',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

             ->addRightButton('user_change_svip',[
                'title' => '年sv拼团',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-warning btn-square',
                'href'  => url('video/user_operate/user_change_svip',['user_id'=>'__id__','vip_level'=>'__vip_level__','vip_time'=>'__vip_time__','commission_rate'=>'__commission_rate__'])
            ],false,['style'=>'primary','title' => true,'icon'=>false])

             ->addRightButton('user_change_logs',[
                'title'=>'查看日志',
                'icon'=>'fa fa-fw fa-bus',
                'class'=>'btn btn-info btn-square',
                'href'=>url('video/user_operate/user_change_logs',['user_id'=>'__id__']),
            ],false,['style'=>'primary','title' => true,'icon'=>false])
 


            ->setRowList($data_list) // 设置表格数据
            ->js("libs/echart/echarts.min")
            ->setHeight('auto')
            ->setExtraHtml($content_html, 'toolbar_top')
            ->setExtraJs($js)
            ->fetch(); // 渲染页面
    }

    public function user_change_logs($user_id = 0) 
    {
        $map = $this->getMap();
        if ($user_id!=0) {
            // code...
            $map[]=["user_id","eq", $user_id];
        } 

        $data_list = DB::connect('translate')->table('ts_user_change_logs')->where($map)

        ->order('time desc')
        ->paginate();

        
        return ZBuilder::make('table')
            ->setTableName('user_change_logs') // 设置数据表名
            // ->setTableName('operation/TagsLinkGoods',2) // 设置数据表名
            // ->setTableName(true) // 设置数据表名
            ->addColumns([ // 批量添加列
                    ['user_id','用户ID'],
                    ['vip_level_start', '记录前会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['vip_level_end', '记录后会员等级','status','',[0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                    ['vip_time_start','记录前vip时间'],
                    ['vip_time_end','记录后vip时间'],
                    ['commission_rate_start','记录后佣金比例'],
                    ['commission_rate_end','记录后佣金比例'],
                    ['admin_userid','运营ID'],
                    ['admin_username','运营昵称'],
                    ['time','创建时间'],
      
            ])
            ->hideCheckbox()
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('back')
            ->fetch(); // 渲染页面

    }




    #点击直接开通年svip拼团套餐： svip会员+6个月的使用时间
    public function user_change_svip($user_id=0,$vip_level=0,$vip_time=null,$commission_rate=0){

        $admin_user_id = is_signin();
        $admin_user_name = get_user_nickname();


        $vip_time = urldecode($vip_time);
        $date = new \DateTime($vip_time);
        $date->modify('+18 months');
        $new_vip_time = $date->format('Y-m-d H:i:s');
        $vip_level_end = 2;
        $commission_rate_end = 50;

        $data['vip_time'] = $new_vip_time;#增加18个月的VIP时间
        $data['vip_level'] = $vip_level_end;#svip
        $data['commission_rate'] = $commission_rate_end;#分佣率
        DB::connect('translate')->table('ts_users')->where('id',$user_id)->update($data);

        $data_log['user_id'] = $user_id;#
        $data_log['vip_level_start'] = $vip_level;#
        $data_log['vip_level_end'] = $vip_level_end;#
        $data_log['vip_time_start'] = $vip_time;#
        $data_log['vip_time_end'] = $new_vip_time;#
        $data_log['commission_rate_start'] = $commission_rate;#
        $data_log['commission_rate_end'] = $commission_rate_end;#
        $data_log['admin_userid'] = $admin_user_id;#运营人员id
        $data_log['admin_username'] = $admin_user_name;#运营人员名称
        DB::connect('translate')->table('ts_user_change_logs')->insert($data_log);
        $this->success('成功开通年svip拼团并额外赠送6个月使用时间');
    }    




    public function getChartjs() {
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

        $startDate = $endDate = null;
        if ($daterangeValue) {
            list($startDate, $endDate) = explode(' - ', $daterangeValue);
            $endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
        } else {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 day'));
        }

        $isSameDay = (date('Y-m-d', strtotime($startDate)) == date('Y-m-d', strtotime($endDate) - 1));
        $groupBy = $isSameDay ? 'HOUR(time)' : 'DATE(time)';
        $xAxisType = $isSameDay ? '小时' : '日期';

        $data_list_time = DB::connect('translate')->table('ts_users')
        ->whereTime('time', 'between', [$startDate, $endDate])
        ->field([
            "{$groupBy} as axisValue",
            'COUNT(*) as count'
        ])
        ->group('axisValue')
        ->select();

        $x_data = array();
        $y_data_time = array();

        foreach ($data_list_time as $value) {
            array_push($x_data, $value['axisValue']);
            array_push($y_data_time, $value['count']);
        }

        $x_data_json = json_encode($x_data);
        $y_data_time_json = json_encode($y_data_time);

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
                data: ['用户记录数']
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
                    name: '用户记录数',
                    type: 'line',
                    data: {$y_data_time_json},
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



    

      public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $info = DB::connect('translate')->table('ts_users')->where('id',$id)->find();
        $admin_user_id = is_signin();
        $admin_user_name = get_user_nickname();

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            
            #操作日志记录
            $data_log['user_id'] = $id;#
            $data_log['vip_level_start'] = $info['vip_level'];#
            $data_log['vip_level_end'] = $data['vip_level'];#

            $data_log['vip_time_start'] =  $info['vip_time'];#
            $data_log['vip_time_end'] = $data['vip_time'];#
            $data_log['admin_userid'] = $admin_user_id;#运营人员id
            $data_log['admin_username'] = $admin_user_name;#运营人员名称
            DB::connect('translate')->table('ts_user_change_logs')->insert($data_log);


            $r = DB::connect('translate')->table('ts_users')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        // ->addDatetime('create_time', '发布时间')

        return ZBuilder::make('form')
             ->addFormItems([
                ['static', 'id', '用户ID'],
                ['static', 'name', '用户名称'],
                ['static', 'phone', '用户手机'],

                ['datetime', 'vip_time', '会员时间'],
                ['select', 'vip_level', '会员级别', '', [0=>'非会员',1=>'普通会员',2=>'高级会员',3=>'铜牌会员']],
                // ['select', 'status', '状态','',[0=>'下架',2=>'上架'],0],

            ])
          
            ->setFormData($info)
            ->fetch();
    }



    // 自定义编辑方法
    public function custom_edit($id, $field, $value) {
        
        // 使用指定的数据库连接来更新数据
        $result = $this->getConnection()
                    ->table('ts_users')
                    ->where('id', $id)
                    ->update([$field => $value]);

        // 根据操作结果返回响应
        if ($result) {
            return json(['code' => 1, 'msg' => '更新成功']);
        } else {
            return json(['code' => 0, 'msg' => '更新失败']);
        }
    }





}
