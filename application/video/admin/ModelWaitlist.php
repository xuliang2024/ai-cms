<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\ModelWaitlistModel;

class ModelWaitlist extends Admin
{
    public function index()
    {
        $db = Db::connect('translate');

        $keyword = input('param.keyword', '', 'trim');
        $statusFilter = input('param.status_filter', '', 'trim');
        $modelFilter = input('param.model_filter', '', 'trim');

        $query = ModelWaitlistModel::alias('w');

        if (!empty($keyword)) {
            if (is_numeric($keyword)) {
                $query = $query->where('w.user_id', $keyword);
            } else {
                $userRow = $db->query("SELECT `id` FROM `ts_users` WHERE `name` LIKE '%" . addslashes($keyword) . "%' LIMIT 50");
                $userIds = array_column($userRow, 'id');
                if (!empty($userIds)) {
                    $query = $query->whereIn('w.user_id', $userIds);
                } else {
                    $query = $query->where('w.user_id', -1);
                }
            }
        }

        if ($statusFilter !== '') {
            $query = $query->where('w.status', $statusFilter);
        }

        if (!empty($modelFilter)) {
            $query = $query->where('w.model_id', $modelFilter);
        }

        $data_list = $query->order('w.id desc')->paginate();
        $pages = $data_list->render();

        $userIds = [];
        foreach ($data_list as $item) {
            $userIds[] = $item['user_id'];
        }

        $userNames = [];
        $paidCountMap = [];
        if (!empty($userIds)) {
            $idStr = implode(',', array_map('intval', array_unique($userIds)));
            $nameRows = $db->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` IN ({$idStr})");
            foreach ($nameRows as $r) {
                $userNames[$r['id']] = $r;
            }
            $paidRows = $db->query("SELECT `user_id`, COUNT(*) as cnt FROM `ts_pay_order_info` WHERE `user_id` IN ({$idStr}) AND `status` = 2 GROUP BY `user_id`");
            foreach ($paidRows as $r) {
                $paidCountMap[$r['user_id']] = $r['cnt'];
            }
        }

        $totalApproved = ModelWaitlistModel::where('status', 'approved')->count();
        $totalPending = ModelWaitlistModel::where('status', 'pending')->count();
        $totalRejected = ModelWaitlistModel::where('status', 'rejected')->count();

        $modelList = $db->query("SELECT DISTINCT `model_id` FROM `ts_model_waitlist` ORDER BY `model_id`");
        $models = array_column($modelList, 'model_id');

        $rows = [];
        foreach ($data_list as $item) {
            $uid = $item['user_id'];
            $user = $userNames[$uid] ?? [];

            $rows[] = [
                'id'             => $item['id'],
                'user_id'        => $uid,
                'user_name'      => $user['name'] ?? '-',
                'user_phone'     => $user['phone'] ?? '-',
                'paid_count'     => $paidCountMap[$uid] ?? 0,
                'model_id'       => $item['model_id'],
                'email'          => $item['email'] ?: '-',
                'expected_usage' => $item['expected_usage'] ?: '-',
                'use_case'       => $item['use_case'] ?: '-',
                'status'         => $item['status'],
                'admin_note'     => $item['admin_note'] ?: '-',
                'daily_limit'    => $item['daily_limit'],
                'created_at'     => $item['created_at'],
                'updated_at'     => $item['updated_at'],
            ];
        }

        $filterHtml = $this->buildFilterHtml($keyword, $statusFilter, $modelFilter, $models);

        return ZBuilder::make('table')
            ->setPageTitle('模型白名单管理')
            ->setPageTips(
                "已通过：<b style='color:#67c23a'>{$totalApproved}</b> | " .
                "待审核：<b style='color:#e6a23c'>{$totalPending}</b> | " .
                "已拒绝：<b style='color:#f56c6c'>{$totalRejected}</b>",
                'info'
            )
            ->setExtraHtml($filterHtml, 'toolbar_top')
            ->addColumns([
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['user_name', '用户名'],
                ['paid_count', '支付笔数', 'callback', function ($value) {
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['model_id', '模型'],
                ['status', '状态', 'callback', function ($value) {
                    $map = [
                        'approved' => '<span style="color:#67c23a;font-weight:bold;">已通过</span>',
                        'pending'  => '<span style="color:#e6a23c;font-weight:bold;">待审核</span>',
                        'rejected' => '<span style="color:#f56c6c;font-weight:bold;">已拒绝</span>',
                    ];
                    return $map[$value] ?? "<span style='color:#909399;'>{$value}</span>";
                }],
                ['email', '邮箱'],
                ['expected_usage', '预计用量'],
                ['use_case', '使用场景', 'callback', function ($value) {
                    if ($value === '-' || empty($value)) return '-';
                    $short = mb_strlen($value) > 30 ? mb_substr($value, 0, 30) . '...' : $value;
                    return "<span title='" . htmlspecialchars($value) . "'>{$short}</span>";
                }],
                ['admin_note', '管理备注', 'callback', function ($value) {
                    if ($value === '-' || empty($value)) return '-';
                    $short = mb_strlen($value) > 30 ? mb_substr($value, 0, 30) . '...' : $value;
                    return "<span title='" . htmlspecialchars($value) . "'>{$short}</span>";
                }],
                ['daily_limit', '每日调用量', 'callback', function ($value) {
                    if (empty($value) || $value == 0) {
                        return "<span style='color:#909399;'>不限制</span>";
                    }
                    return "<span style='color:#409eff;font-weight:bold;'>{$value}</span>";
                }],
                ['created_at', '申请时间'],
                ['right_button', '操作', 'btn'],
            ])
            ->addRightButton('custom', [
                'title' => '编辑',
                'icon'  => 'fa fa-pencil',
                'href'  => url('edit', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-default',
            ])
            ->addRightButton('custom', [
                'title' => '通过',
                'icon'  => 'fa fa-check',
                'href'  => url('approve', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-success ajax-get confirm',
                'data-title' => '确认审核通过？',
            ])
            ->addRightButton('custom', [
                'title' => '拒绝',
                'icon'  => 'fa fa-times',
                'href'  => url('reject', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-danger ajax-get confirm',
                'data-title' => '确认拒绝？',
            ])
            ->addTopButton('custom', [
                'title' => '添加白名单用户',
                'icon'  => 'fa fa-plus',
                'href'  => url('addUser'),
                'class' => 'btn btn-primary',
            ])
            ->setRowList($rows)
            ->setPages($pages)
            ->setHeight('auto')
            ->fetch();
    }

    public function edit($id = null)
    {
        $info = ModelWaitlistModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $db = Db::connect('translate');
        $userId = $info['user_id'];

        if (request()->isPost()) {
            $status = input('post.status', '', 'trim');
            $adminNote = input('post.admin_note', '', 'trim');
            $dailyLimit = input('post.daily_limit', 0, 'intval');

            if (!in_array($status, ['approved', 'pending', 'rejected'])) {
                $this->error('无效的状态值');
            }

            $now = date('Y-m-d H:i:s');
            ModelWaitlistModel::where('id', $id)->update([
                'status'      => $status,
                'admin_note'  => $adminNote,
                'daily_limit' => $dailyLimit,
                'updated_at'  => $now,
            ]);

            $this->success('更新成功', url('index'));
        }

        $userRow = $db->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` = {$userId} LIMIT 1");
        $userName = !empty($userRow) ? ($userRow[0]['name'] ?: $userRow[0]['phone']) : '未知';

        return ZBuilder::make('form')
            ->setPageTitle('编辑白名单 - 用户#' . $userId . ' ' . $userName)
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'user_id_display', '用户ID', '', $userId],
                ['static', 'user_name_display', '用户名', '', $userName],
                ['static', 'model_id_display', '模型', '', $info['model_id']],
                ['static', 'email_display', '邮箱', '', $info['email'] ?: '-'],
                ['static', 'expected_usage_display', '预期用量', '', $info['expected_usage'] ?: '-'],
                ['static', 'use_case_display', '使用场景', '', $info['use_case'] ?: '-'],
                ['radio', 'status', '审核状态', '', [
                    'pending'  => '待审核',
                    'approved' => '通过',
                    'rejected' => '拒绝',
                ]],
                ['number', 'daily_limit', '每日调用量限制', '0 表示不限制，填写正整数设置每日最大调用次数'],
                ['textarea', 'admin_note', '管理备注', '审核备注信息'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function addUser()
    {
        if (request()->isPost()) {
            $userId = input('post.user_id', 0, 'intval');
            $modelId = input('post.model_id', '', 'trim');
            $status = input('post.status', 'approved', 'trim');
            $adminNote = input('post.admin_note', '', 'trim');
            $dailyLimit = input('post.daily_limit', 0, 'intval');

            if ($userId <= 0) {
                $this->error('请输入有效的用户ID');
            }
            if (empty($modelId)) {
                $this->error('请输入模型ID');
            }
            if (!in_array($status, ['approved', 'pending', 'rejected'])) {
                $this->error('无效的状态值');
            }

            $db = Db::connect('translate');
            $userRow = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` = {$userId} LIMIT 1");
            if (empty($userRow)) {
                $this->error('用户不存在，请检查用户ID');
            }

            $existing = ModelWaitlistModel::where('user_id', $userId)->where('model_id', $modelId)->find();
            if ($existing) {
                $this->error('该用户已在此模型的白名单中（ID:' . $existing['id'] . '），请勿重复添加');
            }

            $now = date('Y-m-d H:i:s');
            ModelWaitlistModel::create([
                'user_id'        => $userId,
                'model_id'       => $modelId,
                'email'          => '',
                'expected_usage' => '',
                'use_case'       => '',
                'status'         => $status,
                'admin_note'     => $adminNote ?: '后台手动添加',
                'daily_limit'    => $dailyLimit,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $this->success('添加成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('添加白名单用户')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '请输入要加入白名单的用户ID', '', 'required'],
                ['text', 'model_id', '模型ID', '例如: st-ai/super-seed2', '', 'required'],
                ['radio', 'status', '审核状态', '选择加入后的状态', ['approved' => '通过', 'pending' => '待审核', 'rejected' => '拒绝'], 'approved'],
                ['number', 'daily_limit', '每日调用量限制', '0 表示不限制，填写正整数设置每日最大调用次数', 0],
                ['textarea', 'admin_note', '管理备注', '可选，备注信息'],
            ])
            ->fetch();
    }

    public function approve($id = null)
    {
        if (!$id) {
            $this->error('参数错误');
        }

        $info = ModelWaitlistModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $now = date('Y-m-d H:i:s');
        ModelWaitlistModel::where('id', $id)->update([
            'status'     => 'approved',
            'admin_note' => $info['admin_note'] ? $info['admin_note'] . ' | 手动审批通过' : '手动审批通过',
            'updated_at' => $now,
        ]);

        $this->success('审核通过成功');
    }

    public function reject($id = null)
    {
        if (!$id) {
            $this->error('参数错误');
        }

        $info = ModelWaitlistModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $now = date('Y-m-d H:i:s');
        ModelWaitlistModel::where('id', $id)->update([
            'status'     => 'rejected',
            'admin_note' => $info['admin_note'] ? $info['admin_note'] . ' | 手动拒绝' : '手动拒绝',
            'updated_at' => $now,
        ]);

        $this->success('已拒绝');
    }

    private function buildFilterHtml($keyword, $statusFilter, $modelFilter, $models)
    {
        $baseUrl = url('index');
        $kwVal = htmlspecialchars($keyword);

        $allSel = ($statusFilter === '') ? 'background:#409eff;color:#fff;border-color:#409eff;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $pendSel = ($statusFilter === 'pending') ? 'background:#e6a23c;color:#fff;border-color:#e6a23c;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $appSel = ($statusFilter === 'approved') ? 'background:#67c23a;color:#fff;border-color:#67c23a;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $rejSel = ($statusFilter === 'rejected') ? 'background:#f56c6c;color:#fff;border-color:#f56c6c;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';

        $modelOptions = '<option value="">全部模型</option>';
        foreach ($models as $m) {
            $sel = ($modelFilter === $m) ? 'selected' : '';
            $modelOptions .= "<option value=\"{$m}\" {$sel}>" . htmlspecialchars($m) . "</option>";
        }

        return <<<HTML
<div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
    <span style="font-weight:bold;font-size:13px;">搜索：</span>
    <input type="text" id="wl-keyword" value="{$kwVal}" placeholder="用户ID 或 用户名" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:180px;"/>
    <select id="wl-model" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;">
        {$modelOptions}
    </select>
    <a href="javascript:void(0)" onclick="window.location.href='{$baseUrl}?keyword='+encodeURIComponent(document.getElementById('wl-keyword').value)+'&status_filter={$statusFilter}&model_filter='+encodeURIComponent(document.getElementById('wl-model').value)" style="display:inline-block;padding:5px 15px;border:1px solid #409eff;border-radius:4px;text-decoration:none;font-size:13px;background:#409eff;color:#fff;">搜索</a>
    <span style="color:#909399;margin:0 4px;">|</span>
    <span style="font-weight:bold;font-size:13px;">状态：</span>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=&model_filter={$modelFilter}" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$allSel}">全部</a>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=pending&model_filter={$modelFilter}" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$pendSel}">待审核</a>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=approved&model_filter={$modelFilter}" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$appSel}">已通过</a>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=rejected&model_filter={$modelFilter}" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$rejSel}">已拒绝</a>
</div>
HTML;
    }
}
