<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserDouyinAccountAuth extends Admin {

    public function index() 
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_user_douyin_account_auth')->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_user_douyin_account_auth', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit'];
        return ZBuilder::make('table')
            ->setTableName('ts_user_douyin_account_auth') // 设置数据表名
            ->setPrimaryKey('id') // 设置主键
            ->addColumns([
                    ['id', 'ID'],
                    ['user_id', '用户ID'],
                    ['from_user_id', '来源','text.edit'],
                    ['douyin_nickname', '抖音昵称'],
                    ['promotion_name', '推广名字'],
                    ['douyin_uid', '抖音UID'],
                    ['homepage_screenshot_url', '主页截图','img_url' ],
                    ['douyin_homepage_url', '抖音主页', 'callback', function($value) {
                        return $value ? '<a href="'.$value.'" target="_blank">访问主页</a>' : '-';
                    }],
                    ['jianying_uid', '剪映UID'],
                    ['audit_status', '审核状态', 'callback', function($value) {
                        $status_map = [0 => '<span class="label label-warning">待审核</span>', 
                                     1 => '<span class="label label-info">处理中</span>', 
                                     2 => '<span class="label label-success">审核通过</span>', 
                                     3 => '<span class="label label-danger">审核失败</span>'];
                        return isset($status_map[$value]) ? $status_map[$value] : '-';
                    }],
                    ['error_msg', '错误信息', 'callback', function($value) {
                        return $value ? mb_strimwidth($value, 0, 30, '...') : '-';
                    }],
                    ['time', '创建时间'],
                    ['update_time', '更新时间'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addTopButton('custom', [
                'title' => '导出待审核数据',
                'icon' => 'fa fa-download',
                'class' => 'btn btn-primary',
                'href' => url('export')
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
                ['text', 'user_id', '用户ID'],
                ['text', 'douyin_nickname', '抖音昵称'],
                ['text', 'douyin_uid', '抖音UID'],
                ['select', 'audit_status', '审核状态', '', ['' => '全部', 0 => '待审核', 1 => '处理中', 2 => '审核通过', 3 => '审核失败']],
            ])
            ->addOrder('id')
            ->setRowList($data_list)
            ->setHeight('auto')
            ->fetch();
    }

    public function add() 
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            
            $r = DB::connect('translate')->table('ts_user_douyin_account_auth')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '必填', '', 'required'],
                ['text', 'douyin_nickname', '抖音昵称'],
                ['text', 'douyin_uid', '抖音UID'],
                ['text', 'homepage_screenshot_url', '主页截图URL'],
                ['text', 'douyin_homepage_url', '抖音主页链接'],
                ['text', 'jianying_uid', '剪映UID'],
                ['select', 'audit_status', '审核状态', '', [0 => '待审核', 1 => '处理中', 2 => '审核通过', 3 => '审核失败'], 0],
                ['textarea', 'error_msg', '错误信息'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['update_time'] = date('Y-m-d H:i:s');
            
            $r = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '必填', '', 'required'],
                ['text', 'douyin_nickname', '抖音昵称'],
                ['text', 'douyin_uid', '抖音UID'],
                ['text', 'homepage_screenshot_url', '主页截图URL'],
                ['text', 'douyin_homepage_url', '抖音主页链接'],
                ['text', 'jianying_uid', '剪映UID'],
                ['select', 'audit_status', '审核状态', '', [0 => '待审核', 1 => '处理中', 2 => '审核通过', 3 => '审核失败']],
                ['textarea', 'error_msg', '错误信息'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 审核操作
     */
    public function audit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['update_time'] = date('Y-m-d H:i:s');
            
            $r = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', $id)->update($data);
            if ($r) {
                $this->success('审核成功', 'index');
            } else {
                $this->error('审核失败');
            }
        }

        $info = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['static', 'user_id', '用户ID'],
                ['static', 'douyin_nickname', '抖音昵称'],
                ['static', 'douyin_uid', '抖音UID'],
                ['static', 'douyin_homepage_url', '抖音主页链接'],
                ['static', 'jianying_uid', '剪映UID'],
                ['select', 'audit_status', '审核状态', '必选', [0 => '待审核', 1 => '处理中', 2 => '审核通过', 3 => '审核失败'], '', 'required'],
                ['textarea', 'error_msg', '错误信息', '审核失败时请填写失败原因'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 导出待审核数据
     */
    public function export()
    {
        // 查询状态为0（待审核）的数据
        $data = DB::connect('translate')->table('ts_user_douyin_account_auth')
            ->where('audit_status', 0)
            ->order('id desc')
            ->select();

        if (empty($data)) {
            $this->error('暂无待审核数据可导出');
        }

        // 格式化数据
        $exportData = [];
        foreach ($data as $item) {
            // 格式化时间为"6月20号"这种格式
            $time = date('n月j号', strtotime($item['time']));
            
            $exportData[] = [
                'jianying_uid' => $item['jianying_uid'] ?: '-',
                'douyin_uid' => $item['douyin_uid'] ?: '-', 
                'douyin_nickname' => $item['douyin_nickname'] ?: '-',
                'promotion_name' => $item['promotion_name'] ?: '-',
                'douyin_homepage_url' => $item['douyin_homepage_url'] ?: '-',
                'audit_status' => '待审核',
                'time' => $time
            ];
        }

        // 表头配置
        $header = [
            ['time', 15, '录入时间'],
            ['jianying_uid', 15, '剪映UID'],
            ['douyin_uid', 15, '抖音UID'],
            ['douyin_homepage_url', 40, '抖音主页'],
            ['douyin_nickname', 20, '账号昵称'],
            ['promotion_name', 20, '项目名称'],
            ['audit_status', 12, '开白情况']
        ];

        // 文件名
        $filename = '待审核抖音账号认证数据_' . date('Y年m月d日');

        // 调用Excel插件导出
        $excel = new \plugins\Excel\controller\Excel();
        $excel->export($filename, $header, $exportData);
    }

    /**
     * 快速修改审核状态
     */
    public function quickStatus($id = null, $status = null)
    {
        if ($id === null || $status === null) $this->error('缺少参数');
        
        // 验证状态值
        if (!in_array($status, [0, 1, 2, 3])) {
            $this->error('状态值无效');
        }
        
        $data = [
            'audit_status' => $status,
            'update_time' => date('Y-m-d H:i:s')
        ];
        
        $r = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', $id)->update($data);
        if ($r !== false) {
            $status_text = [0 => '待审核', 1 => '处理中', 2 => '审核通过', 3 => '审核失败'];
            $this->success('状态已更新为：' . $status_text[$status]);
        } else {
            $this->error('状态更新失败');
        }
    }

    /**
     * 删除操作
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('缺少参数');
        
        $ids = is_array($ids) ? $ids : [$ids];
        
        $r = DB::connect('translate')->table('ts_user_douyin_account_auth')->where('id', 'in', $ids)->delete();
        if ($r) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 