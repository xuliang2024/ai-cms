<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\ReferralPromoterModel;
use app\video\model\ReferralCodeModel;

class ReferralPromoter extends Admin
{
    public function index()
    {
        $map = $this->getMap();
        $db = Db::connect('translate');

        $keyword = input('param.keyword', '', 'trim');

        $query = ReferralPromoterModel::alias('p');

        if (!empty($keyword)) {
            if (is_numeric($keyword)) {
                $query = $query->where('p.user_id', $keyword);
            } else {
                $userRow = $db->query("SELECT `id` FROM `ts_users` WHERE `name` LIKE '%" . addslashes($keyword) . "%' LIMIT 50");
                $userIds = array_column($userRow, 'id');
                if (!empty($userIds)) {
                    $query = $query->whereIn('p.user_id', $userIds);
                } else {
                    $query = $query->where('p.user_id', -1);
                }
            }
        }

        $statusFilter = input('param.status_filter', '', 'trim');
        if ($statusFilter !== '') {
            $query = $query->where('p.status', intval($statusFilter));
        }

        $data_list = $query->order('p.id desc')->paginate();

        $userIds = [];
        foreach ($data_list as $item) {
            $userIds[] = $item['user_id'];
        }

        $userNames = [];
        if (!empty($userIds)) {
            $idStr = implode(',', array_map('intval', $userIds));
            $nameRows = $db->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` IN ({$idStr})");
            foreach ($nameRows as $r) {
                $userNames[$r['id']] = $r;
            }
        }

        $codeMap = [];
        if (!empty($userIds)) {
            $codes = ReferralCodeModel::whereIn('promoter_user_id', $userIds)->select();
            foreach ($codes as $c) {
                $codeMap[$c['promoter_user_id']][] = $c;
            }
        }

        $totalActive = ReferralPromoterModel::where('status', 1)->count();
        $totalInactive = ReferralPromoterModel::where('status', 0)->count();
        $totalCodes = ReferralCodeModel::where('status', 1)->count();

        $rows = [];
        foreach ($data_list as $item) {
            $uid = $item['user_id'];
            $user = $userNames[$uid] ?? [];
            $codes = $codeMap[$uid] ?? [];
            $codeStrs = [];
            foreach ($codes as $c) {
                $statusTag = $c['status'] == 1
                    ? '<span style="color:#67c23a;">[有效]</span>'
                    : '<span style="color:#f56c6c;">[停用]</span>';
                $codeStrs[] = $statusTag . ' ' . $c['code'] . ' (用' . $c['used_count'] . '次)';
            }

            $rows[] = [
                'id' => $item['id'],
                'user_id' => $uid,
                'user_name' => $user['name'] ?? '-',
                'user_phone' => $user['phone'] ?? '-',
                'status' => $item['status'],
                'commission_rate' => $item['commission_rate'],
                'codes_display' => !empty($codeStrs) ? implode('<br>', $codeStrs) : '<span style="color:#909399;">无推广码</span>',
                'enabled_at' => $item['enabled_at'] ?: '-',
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];
        }

        $filterHtml = $this->buildFilterHtml($keyword, $statusFilter);

        return ZBuilder::make('table')
            ->setPageTitle('推广权限管理')
            ->setPageTips("活跃推广员：<b style='color:#67c23a'>{$totalActive}</b> | 已停用：<b style='color:#f56c6c'>{$totalInactive}</b> | 有效推广码：<b style='color:#409eff'>{$totalCodes}</b>", 'info')
            ->setExtraHtml($filterHtml, 'toolbar_top')
            ->hideCheckbox()
            ->addColumns([
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['user_name', '用户名'],
                ['user_phone', '手机号'],
                ['status', '推广状态', 'callback', function ($value) {
                    return $value == 1
                        ? '<span style="color:#67c23a;font-weight:bold;">已开通</span>'
                        : '<span style="color:#f56c6c;font-weight:bold;">已关闭</span>';
                }],
                ['commission_rate', '佣金比例', 'callback', function ($value) {
                    return "<span style='color:#409eff;font-weight:bold;'>{$value}%</span>";
                }],
                ['codes_display', '推广码'],
                ['enabled_at', '开通时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn'],
            ])
            ->setRowList($rows)
            ->addTopButton('custom', [
                'title' => '开通推广权限',
                'icon'  => 'fa fa-plus',
                'href'  => url('enablePromoter'),
                'class' => 'btn btn-primary',
            ])
            ->addRightButton('custom', [
                'title' => '编辑',
                'icon'  => 'fa fa-pencil',
                'href'  => url('edit', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-default',
            ])
            ->addRightButton('custom', [
                'title' => '添加推广码',
                'icon'  => 'fa fa-plus-circle',
                'href'  => url('addCode', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-info',
            ])
            ->setHeight('auto')
            ->fetch();
    }

    public function enablePromoter()
    {
        if (request()->isPost()) {
            $userId = input('post.user_id', 0, 'intval');
            $commissionRate = input('post.commission_rate', 10, 'intval');
            $refCode = input('post.ref_code', '', 'trim');

            if ($userId <= 0) {
                $this->error('请输入有效的用户ID');
            }

            $db = Db::connect('translate');
            $userRow = $db->query("SELECT `id`, `name` FROM `ts_users` WHERE `id` = {$userId} LIMIT 1");
            if (empty($userRow)) {
                $this->error('用户不存在，请检查用户ID');
            }

            $existing = ReferralPromoterModel::where('user_id', $userId)->find();
            $now = date('Y-m-d H:i:s');

            if ($existing) {
                ReferralPromoterModel::where('user_id', $userId)->update([
                    'status' => 1,
                    'commission_rate' => $commissionRate,
                    'enabled_at' => $now,
                    'updated_at' => $now,
                ]);
                ReferralCodeModel::where('promoter_user_id', $userId)->update([
                    'status' => 1,
                    'updated_at' => $now,
                ]);
            } else {
                ReferralPromoterModel::create([
                    'user_id' => $userId,
                    'status' => 1,
                    'commission_rate' => $commissionRate,
                    'enabled_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $hasCode = ReferralCodeModel::where('promoter_user_id', $userId)->find();
            if (!$hasCode) {
                $code = !empty($refCode) ? $refCode : $this->generateCode();
                $existCode = ReferralCodeModel::where('code', $code)->find();
                if ($existCode) {
                    $this->error('推广码已存在，请更换');
                }
                ReferralCodeModel::create([
                    'promoter_user_id' => $userId,
                    'code' => $code,
                    'status' => 1,
                    'remark' => '',
                    'new_user_reward_points' => 100,
                    'used_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->success('推广权限开通成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('开通推广权限')
            ->addFormItems([
                ['text', 'user_id', '用户ID', '请输入要开通推广权限的用户ID', '', 'required'],
                ['number', 'commission_rate', '佣金比例(%)', '佣金比例，默认10表示10%', 10],
                ['text', 'ref_code', '自定义推广码', '可选，留空则自动生成随机推广码（最多16位字母数字）'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        $info = ReferralPromoterModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $userId = $info['user_id'];
        $now = date('Y-m-d H:i:s');

        if (request()->isPost()) {
            $status = input('post.status', 0, 'intval');
            $commissionRate = input('post.commission_rate', 10, 'intval');

            ReferralPromoterModel::where('id', $id)->update([
                'status' => $status,
                'commission_rate' => $commissionRate,
                'enabled_at' => $status == 1 ? $now : $info['enabled_at'],
                'updated_at' => $now,
            ]);

            ReferralCodeModel::where('promoter_user_id', $userId)->update([
                'status' => $status,
                'updated_at' => $now,
            ]);

            $this->success('更新成功', url('index'));
        }

        $db = Db::connect('translate');
        $userRow = $db->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` = {$userId} LIMIT 1");
        $userName = !empty($userRow) ? ($userRow[0]['name'] ?: $userRow[0]['phone']) : '未知';

        $codes = ReferralCodeModel::where('promoter_user_id', $userId)->select();
        $codeInfo = [];
        foreach ($codes as $c) {
            $st = $c['status'] == 1 ? '有效' : '停用';
            $codeInfo[] = "{$c['code']} [{$st}] (已使用{$c['used_count']}次)";
        }
        $codeStr = !empty($codeInfo) ? implode("\n", $codeInfo) : '无推广码';

        return ZBuilder::make('form')
            ->setPageTitle('编辑推广权限 - 用户#' . $userId . ' ' . $userName)
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'user_id_display', '用户ID', '', $userId],
                ['static', 'user_name_display', '用户名', '', $userName],
                ['static', 'codes_display', '推广码', '', nl2br($codeStr)],
                ['radio', 'status', '推广状态', '开通或关闭推广权限（同步影响推广码状态）', ['0' => '关闭', '1' => '开通']],
                ['number', 'commission_rate', '佣金比例(%)', '佣金比例，10表示10%'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function addCode($id = null)
    {
        $info = ReferralPromoterModel::where('id', $id)->find();
        if (!$info) {
            $this->error('推广员记录不存在');
        }

        $userId = $info['user_id'];

        if (request()->isPost()) {
            $code = input('post.code', '', 'trim');
            $remark = input('post.remark', '', 'trim');
            $newUserRewardPoints = input('post.new_user_reward_points', 100, 'intval');

            if (empty($code)) {
                $code = $this->generateCode();
            }

            if (strlen($code) > 16) {
                $this->error('推广码长度不能超过16位');
            }

            $existCode = ReferralCodeModel::where('code', $code)->find();
            if ($existCode) {
                $this->error('推广码已存在，请更换');
            }

            $now = date('Y-m-d H:i:s');
            ReferralCodeModel::create([
                'promoter_user_id' => $userId,
                'code' => $code,
                'status' => $info['status'],
                'remark' => $remark,
                'new_user_reward_points' => $newUserRewardPoints,
                'used_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->success('推广码添加成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('添加推广码 - 用户#' . $userId)
            ->addFormItems([
                ['text', 'code', '推广码', '可选，留空则自动生成（最多16位字母数字）'],
                ['text', 'remark', '备注', '可选，如渠道标记：reddit、twitter 等'],
                ['number', 'new_user_reward_points', '新用户奖励积分', '新用户通过此码注册获得的积分', 100],
            ])
            ->fetch();
    }

    public function toggleStatus()
    {
        $val = input('post.value', 0, 'intval');
        $id = input('post.id', 0, 'intval');

        if (!$id) {
            $this->error('参数错误');
        }

        $info = ReferralPromoterModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $now = date('Y-m-d H:i:s');
        ReferralPromoterModel::where('id', $id)->update([
            'status' => $val,
            'updated_at' => $now,
        ]);

        ReferralCodeModel::where('promoter_user_id', $info['user_id'])->update([
            'status' => $val,
            'updated_at' => $now,
        ]);

        $this->success('操作成功');
    }

    private function generateCode($length = 8)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    private function buildFilterHtml($keyword, $statusFilter)
    {
        $baseUrl = url('index');
        $kwVal = htmlspecialchars($keyword);
        $allSel = ($statusFilter === '') ? 'background:#409eff;color:#fff;border-color:#409eff;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $onSel = ($statusFilter === '1') ? 'background:#409eff;color:#fff;border-color:#409eff;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $offSel = ($statusFilter === '0') ? 'background:#409eff;color:#fff;border-color:#409eff;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';

        return <<<HTML
<div style="margin-bottom:12px;padding:10px 14px;background:#f5f7fa;border-radius:6px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
    <span style="font-weight:bold;font-size:13px;">搜索：</span>
    <input type="text" id="rp-keyword" value="{$kwVal}" placeholder="用户ID 或 用户名" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:180px;"/>
    <a href="javascript:void(0)" onclick="window.location.href='{$baseUrl}?keyword='+encodeURIComponent(document.getElementById('rp-keyword').value)+'&status_filter={$statusFilter}'" style="display:inline-block;padding:5px 15px;border:1px solid #409eff;border-radius:4px;text-decoration:none;font-size:13px;background:#409eff;color:#fff;">搜索</a>
    <span style="color:#909399;margin:0 4px;">|</span>
    <span style="font-weight:bold;font-size:13px;">状态：</span>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$allSel}">全部</a>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=1" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$onSel}">已开通</a>
    <a href="{$baseUrl}?keyword={$kwVal}&status_filter=0" style="display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$offSel}">已关闭</a>
</div>
HTML;
    }
}
