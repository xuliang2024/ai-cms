<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class BackfillRecord extends Admin {

    public function index() 
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_backfill_record')
        ->alias('br')
        ->leftJoin('ts_user_douyin_account_auth udaa', 'br.auth_id = udaa.id')
        ->field('br.*, udaa.promotion_name')
        ->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_backfill_record_with_auth', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            ->setTableName('video/BackfillRecordModel',2) // 设置数据表名
            ->addColumns([
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['auth_id', '授权ID'],
                    ['promotion_name', '推广名字'],
                    ['backfill_url', '回填链接', 'callback', function($value) {
                        return $value ? '<a href="'.$value.'" target="_blank">查看链接</a>' : '-';
                    }],
                    ['video_gid', '视频GID'],
                    ['status', '状态', 'callback', function($value) {
                        $status_map = [
                            0 => '<span class="label label-default">待处理</span>', 
                            1 => '<span class="label label-info">处理中</span>', 
                            2 => '<span class="label label-success">处理完成</span>',
                            3 => '<span class="label label-danger">处理失败</span>'
                        ];
                        return isset($status_map[$value]) ? $status_map[$value] : '-';
                    }],
                    ['money', '金额', 'callback', function($value) {
                        return '￥' . number_format($value / 100, 2);
                    }],
                    ['time', '创建时间'],
                    ['update_time', '更新时间'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addTopButton('custom', [
                'title' => '导出待处理数据',
                'icon' => 'fa fa-download',
                'class' => 'btn btn-primary',
                'href' => url('export')
            ])
            ->addTopButton('custom', [
                'title' => '一键同意',
                'icon' => 'fa fa-check-circle',
                'class' => 'btn btn-success',
                'href' => url('batchApprove'),
                'target' => '_self',
                'confirm' => '确定要将所有待处理的记录标记为处理完成吗？'
            ])
            ->addRightButtons($contro_right_btn)
            ->addRightButton('custom', [
                'title' => '通过',
                'icon' => 'fa fa-check',
                'class' => 'btn btn-xs btn-success',
                'href' => url('quickStatus', ['id' => '__id__', 'status' => 2]),
                'target' => '_self'
            ])
            ->addRightButton('custom', [
                'title' => '失败',
                'icon' => 'fa fa-times',
                'class' => 'btn btn-xs btn-danger',
                'href' => url('quickStatus', ['id' => '__id__', 'status' => 3]),
                'target' => '_self'
            ])
            ->setSearchArea([
                ['text', 'br.user_id', '用户ID'],
                ['text', 'br.auth_id', '授权ID'],
                ['text', 'udaa.promotion_name', '推广名字'],
                ['text', 'br.video_gid', '视频GID'],
                ['select', 'br.status', '状态', '', ['' => '全部', 0 => '待处理', 1 => '处理中', 2 => '处理完成', 3 => '处理失败']],
            ])
            ->addOrder('id')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    /**
     * 导出待处理数据
     */
    public function export()
    {
        // 查询状态为0（待处理）的数据，关联获取达人昵称、抖音UID和项目名称
        $data = DB::connect('translate')
            ->table('ts_backfill_record')
            ->alias('br')
            ->leftJoin('ts_user_douyin_account_auth udaa', 'br.auth_id = udaa.id')
            ->where('br.status', 0)
            ->field('br.*, udaa.douyin_nickname, udaa.douyin_uid, udaa.promotion_name')
            ->order('br.id desc')
            ->select();

        if (empty($data)) {
            $this->error('暂无待处理数据可导出');
        }

        // 格式化数据
        $exportData = [];
        foreach ($data as $item) {
            // 格式化时间为"2025/5/30"这种格式
            $time = date('Y/n/j', strtotime($item['time']));
            
            $exportData[] = [
                'douyin_nickname' => $item['douyin_nickname'] ?: '-',
                'douyin_uid' => $item['douyin_uid'] ?: '-',
                'promotion_name' => $item['promotion_name'] ?: '-',
                'backfill_url' => $item['backfill_url'] ?: '-',
                'video_gid' => $item['video_gid'] ?: '-',
                'time' => $time
            ];
        }

        // 表头配置
        $header = [
            ['douyin_nickname', 20, '达人昵称'],
            ['douyin_uid', 20, '抖音UID'],
            ['backfill_url', 50, '回填链接'],
            ['video_gid', 25, '视频GID'],
            ['time', 15, '发布日期'],
            ['promotion_name', 20, '项目名称']
        ];

        // 文件名
        $filename = '待处理回填记录_' . date('Y年m月d日');

        // 调用Excel插件导出
        $excel = new \plugins\Excel\controller\Excel();
        $excel->export($filename, $header, $exportData);
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            
            // 金额转换为分
            if (isset($data['money'])) {
                $data['money'] = $data['money'] * 100;
            }
            
            $r = DB::connect('translate')->table('ts_backfill_record')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '必填', '', 'required'],
                ['text', 'auth_id', '授权ID', '必填', '', 'required'],
                ['text', 'backfill_url', '回填链接'],
                ['text', 'video_gid', '视频GID'],
                ['select', 'status', '状态', '', [0 => '待处理', 1 => '处理中', 2 => '处理完成', 3 => '处理失败'], 0],
                ['text', 'money', '金额(元)', '请输入金额，单位为元'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['update_time'] = date('Y-m-d H:i:s');
            
            // 金额转换为分
            if (isset($data['money'])) {
                $data['money'] = $data['money'] * 100;
            }
            
            $r = DB::connect('translate')->table('ts_backfill_record')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_backfill_record')->where('id', $id)->find();
        
        // 金额转换为元
        if (isset($info['money'])) {
            $info['money'] = $info['money'] / 100;
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '必填', '', 'required'],
                ['text', 'auth_id', '授权ID', '必填', '', 'required'],
                ['text', 'backfill_url', '回填链接'],
                ['text', 'video_gid', '视频GID'],
                ['select', 'status', '状态', '', [0 => '待处理', 1 => '处理中', 2 => '处理完成', 3 => '处理失败']],
                ['text', 'money', '金额(元)', '请输入金额，单位为元'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 快速更新状态
     */
    public function quickStatus($id = null, $status = null)
    {
        if ($id === null || $status === null) {
            $this->error('参数错误');
        }

        // 更新数据
        $data = [
            'status' => $status,
            'update_time' => date('Y-m-d H:i:s')
        ];

        $r = DB::connect('translate')->table('ts_backfill_record')->where('id', $id)->update($data);
        if ($r) {
            $this->success('状态更新成功', 'index');
        } else {
            $this->error('状态更新失败');
        }
    }

    /**
     * 一键同意 - 批量将待处理状态改为处理完成
     */
    public function batchApprove()
    {
        // 查询所有待处理的记录数量
        $pendingCount = DB::connect('translate')
            ->table('ts_backfill_record')
            ->where('status', 0)
            ->count();

        if ($pendingCount == 0) {
            $this->error('暂无待处理的记录');
        }

        // 批量更新所有待处理的记录为处理完成
        $data = [
            'status' => 2, // 2表示处理完成
            'update_time' => date('Y-m-d H:i:s')
        ];

        $r = DB::connect('translate')
            ->table('ts_backfill_record')
            ->where('status', 0) // 只更新待处理的记录
            ->update($data);

        if ($r) {
            $this->success("成功处理了 {$pendingCount} 条待处理记录", 'index');
        } else {
            $this->error('批量处理失败');
        }
    }
} 