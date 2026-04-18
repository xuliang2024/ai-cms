<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class UserTicket extends Admin
{
    protected $ticketTypes = [
        1 => 'Bug反馈',
        2 => '功能建议',
        3 => '使用问题',
        4 => '账户问题',
        5 => '其他',
        6 => '工作流定制',
    ];

    protected $priorities = [
        1 => '紧急',
        2 => '高',
        3 => '中',
        4 => '低',
    ];

    protected $priorityColors = [
        1 => '#f56c6c',
        2 => '#e6a23c',
        3 => '#409eff',
        4 => '#909399',
    ];

    protected $statuses = [
        1 => '待处理',
        2 => '处理中',
        3 => '已解决',
        4 => '已关闭',
        5 => '已拒绝',
    ];

    protected $statusColors = [
        1 => '#e6a23c',
        2 => '#409eff',
        3 => '#67c23a',
        4 => '#909399',
        5 => '#f56c6c',
    ];

    protected $contactTypes = [
        1 => '手机号',
        2 => '邮箱',
        3 => '微信',
    ];

    protected function db()
    {
        return Db::connect('translate');
    }

    public function index()
    {
        $keyword = input('param.keyword', '', 'trim');
        $statusFilter = input('param.status', '', 'trim');
        $typeFilter = input('param.ticket_type', '', 'trim');
        $priorityFilter = input('param.priority', '', 'trim');

        $query = $this->db()->table('ts_user_ticket');

        if (!empty($keyword)) {
            if (is_numeric($keyword)) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->where('user_id', $keyword)->whereOr('ticket_no', $keyword);
                });
            } else {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->where('ticket_no', 'like', "%{$keyword}%")
                      ->whereOr('title', 'like', "%{$keyword}%");
                });
            }
        }

        if ($statusFilter !== '') {
            $query = $query->where('status', $statusFilter);
        }

        if ($typeFilter !== '') {
            $query = $query->where('ticket_type', $typeFilter);
        }

        if ($priorityFilter !== '') {
            $query = $query->where('priority', $priorityFilter);
        }

        $data_list = $query->order('id desc')->paginate();
        $pages = $data_list->render();

        $userIds = [];
        foreach ($data_list as $item) {
            if ($item['user_id'] > 0) {
                $userIds[] = $item['user_id'];
            }
        }

        $userNames = [];
        if (!empty($userIds)) {
            $idStr = implode(',', array_map('intval', array_unique($userIds)));
            $nameRows = $this->db()->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` IN ({$idStr})");
            foreach ($nameRows as $r) {
                $userNames[$r['id']] = $r['name'] ?: $r['phone'] ?: '-';
            }
        }

        $stats = $this->db()->query("SELECT COUNT(*) as total, SUM(status=1) as pending, SUM(status=2) as processing, SUM(status=3) as resolved, SUM(status=4) as closed, SUM(status=5) as rejected FROM ts_user_ticket")[0];

        $ticketTypes = $this->ticketTypes;
        $priorities = $this->priorities;
        $priorityColors = $this->priorityColors;
        $statuses = $this->statuses;
        $statusColors = $this->statusColors;

        $rows = [];
        foreach ($data_list as $item) {
            $rows[] = [
                'id'            => $item['id'],
                'ticket_no'     => $item['ticket_no'],
                'user_id'       => $item['user_id'],
                'user_name'     => $userNames[$item['user_id']] ?? '-',
                'title'         => $item['title'],
                'ticket_type'   => $item['ticket_type'],
                'priority'      => $item['priority'],
                'status'        => $item['status'],
                'reply_count'   => $item['reply_count'],
                'last_reply_time' => $item['last_reply_time'] ?: '-',
                'create_time'   => $item['create_time'],
            ];
        }

        $filterHtml = $this->buildFilterHtml($keyword, $statusFilter, $typeFilter, $priorityFilter);

        return ZBuilder::make('table')
            ->setPageTitle('工单管理')
            ->setPageTips(
                "总计：<b>{$stats['total']}</b> | " .
                "待处理：<b style='color:#e6a23c'>{$stats['pending']}</b> | " .
                "处理中：<b style='color:#409eff'>{$stats['processing']}</b> | " .
                "已解决：<b style='color:#67c23a'>{$stats['resolved']}</b> | " .
                "已关闭：<b style='color:#909399'>{$stats['closed']}</b> | " .
                "已拒绝：<b style='color:#f56c6c'>{$stats['rejected']}</b>",
                'info'
            )
            ->setExtraHtml($filterHtml, 'toolbar_top')
            ->addColumns([
                ['id', 'ID'],
                ['ticket_no', '工单编号', 'callback', function ($value) {
                    return "<span style='font-family:monospace;font-size:12px;'>{$value}</span>";
                }],
                ['user_id', '用户ID', 'callback', function ($value) {
                    return $value > 0 ? "<span style='color:#409eff;font-weight:bold;'>{$value}</span>" : '-';
                }],
                ['user_name', '用户名'],
                ['title', '标题', 'callback', function ($value) {
                    $short = mb_strlen($value) > 30 ? mb_substr($value, 0, 30) . '...' : $value;
                    return "<span title='" . htmlspecialchars($value) . "'>{$short}</span>";
                }],
                ['ticket_type', '类型', 'callback', function ($value) use ($ticketTypes) {
                    return $ticketTypes[$value] ?? '未知';
                }],
                ['priority', '优先级', 'callback', function ($value) use ($priorities, $priorityColors) {
                    $label = $priorities[$value] ?? '未知';
                    $color = $priorityColors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$label}</span>";
                }],
                ['status', '状态', 'callback', function ($value) use ($statuses, $statusColors) {
                    $label = $statuses[$value] ?? '未知';
                    $color = $statusColors[$value] ?? '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$label}</span>";
                }],
                ['reply_count', '回复数', 'callback', function ($value) {
                    $color = $value > 0 ? '#67c23a' : '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['last_reply_time', '最后回复'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn'],
            ])
            ->addRightButton('custom', [
                'title' => '详情',
                'icon'  => 'fa fa-eye',
                'href'  => url('detail', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-info',
            ])
            ->addRightButton('custom', [
                'title' => '回复',
                'icon'  => 'fa fa-reply',
                'href'  => url('reply', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-success',
            ])
            ->addRightButton('custom', [
                'title' => '状态',
                'icon'  => 'fa fa-exchange',
                'href'  => url('changestatus', ['id' => '__id__']),
                'class' => 'btn btn-xs btn-warning',
            ])
            ->setRowList($rows)
            ->setPages($pages)
            ->setHeight('auto')
            ->fetch();
    }

    public function detail()
    {
        $id = input('param.id', 0, 'intval');
        if (!$id) $this->error('参数错误');

        $ticket = $this->db()->table('ts_user_ticket')->where('id', $id)->find();
        if (!$ticket) $this->error('工单不存在');

        $userName = '-';
        if ($ticket['user_id'] > 0) {
            $userRow = $this->db()->query("SELECT `name`, `phone` FROM `ts_users` WHERE `id` = {$ticket['user_id']} LIMIT 1");
            if (!empty($userRow)) {
                $userName = $userRow[0]['name'] ?: $userRow[0]['phone'] ?: '-';
            }
        }

        $replies = $this->db()->table('ts_ticket_reply')
            ->where('ticket_id', $id)
            ->order('create_time asc')
            ->select();

        $replyUserIds = [];
        $replyAdminIds = [];
        foreach ($replies as $r) {
            if ($r['user_id'] > 0) $replyUserIds[] = $r['user_id'];
            if ($r['admin_id'] > 0) $replyAdminIds[] = $r['admin_id'];
        }

        $replyUserNames = [];
        if (!empty($replyUserIds)) {
            $idStr = implode(',', array_map('intval', array_unique($replyUserIds)));
            $rows = $this->db()->query("SELECT `id`, `name`, `phone` FROM `ts_users` WHERE `id` IN ({$idStr})");
            foreach ($rows as $r) {
                $replyUserNames[$r['id']] = $r['name'] ?: $r['phone'] ?: '用户#' . $r['id'];
            }
        }

        $replyAdminNames = [];
        if (!empty($replyAdminIds)) {
            $idStr = implode(',', array_map('intval', array_unique($replyAdminIds)));
            $rows = Db::table('ai_admin_user')->whereIn('id', $idStr)->field('id,username,nickname')->select();
            foreach ($rows as $r) {
                $replyAdminNames[$r['id']] = $r['nickname'] ?: $r['username'] ?: '管理员#' . $r['id'];
            }
        }

        $ticketTypes = $this->ticketTypes;
        $priorities = $this->priorities;
        $priorityColors = $this->priorityColors;
        $statuses = $this->statuses;
        $statusColors = $this->statusColors;
        $contactTypes = $this->contactTypes;

        $statusLabel = $statuses[$ticket['status']] ?? '未知';
        $statusColor = $statusColors[$ticket['status']] ?? '#909399';
        $priorityLabel = $priorities[$ticket['priority']] ?? '未知';
        $priorityColor = $priorityColors[$ticket['priority']] ?? '#909399';

        $html = '<div style="padding:15px;max-width:960px;">';

        $html .= '<div style="background:#fff;border:1px solid #ebeef5;border-radius:6px;padding:20px;margin-bottom:15px;">';
        $html .= '<h4 style="margin:0 0 15px 0;font-size:16px;color:#303133;">' . htmlspecialchars($ticket['title']) . '</h4>';
        $html .= '<table class="table table-bordered" style="margin-bottom:0;">';

        $infoItems = [
            ['工单编号', $ticket['ticket_no']],
            ['用户', "ID: {$ticket['user_id']} / {$userName}"],
            ['类型', $ticketTypes[$ticket['ticket_type']] ?? '未知'],
            ['优先级', "<span style='color:{$priorityColor};font-weight:bold;'>{$priorityLabel}</span>"],
            ['状态', "<span style='color:{$statusColor};font-weight:bold;'>{$statusLabel}</span>"],
            ['联系方式', ($contactTypes[$ticket['contact_type']] ?? '') . ': ' . ($ticket['contact_info'] ?: '-')],
            ['回复数', $ticket['reply_count']],
            ['创建时间', $ticket['create_time']],
            ['更新时间', $ticket['update_time']],
            ['最后回复', $ticket['last_reply_time'] ?: '-'],
        ];

        foreach ($infoItems as $item) {
            $html .= "<tr><td style='background:#f5f7fa;font-weight:bold;width:120px;'>{$item[0]}</td><td>{$item[1]}</td></tr>";
        }
        $html .= '</table></div>';

        $html .= '<div style="background:#fff;border:1px solid #ebeef5;border-radius:6px;padding:20px;margin-bottom:15px;">';
        $html .= '<h5 style="margin:0 0 10px 0;color:#303133;">工单内容</h5>';
        $html .= '<div style="padding:10px;background:#f5f7fa;border-radius:4px;line-height:1.8;white-space:pre-wrap;">' . htmlspecialchars($ticket['content']) . '</div>';

        if (!empty($ticket['attachment_urls'])) {
            $attachments = json_decode($ticket['attachment_urls'], true);
            if (!empty($attachments) && is_array($attachments)) {
                $html .= '<div style="margin-top:10px;">';
                $html .= '<span style="font-weight:bold;color:#606266;">附件：</span>';
                foreach ($attachments as $i => $url) {
                    $html .= "<a href='" . htmlspecialchars($url) . "' target='_blank' style='color:#409eff;margin-right:10px;'>附件" . ($i + 1) . "</a>";
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>';

        $html .= '<div style="background:#fff;border:1px solid #ebeef5;border-radius:6px;padding:20px;margin-bottom:15px;">';
        $html .= '<h5 style="margin:0 0 15px 0;color:#303133;">对话记录 (' . count($replies) . '条)</h5>';

        if (empty($replies)) {
            $html .= '<div style="text-align:center;color:#909399;padding:20px;">暂无回复</div>';
        } else {
            foreach ($replies as $r) {
                if ($r['reply_type'] == 1) {
                    $senderName = $replyUserNames[$r['user_id']] ?? ('用户#' . $r['user_id']);
                    $bgColor = '#f0f9ff';
                    $borderColor = '#b3d8ff';
                    $labelColor = '#409eff';
                    $label = '用户';
                } elseif ($r['reply_type'] == 2) {
                    $senderName = $replyAdminNames[$r['admin_id']] ?? ('管理员#' . $r['admin_id']);
                    $bgColor = '#f0f9eb';
                    $borderColor = '#c2e7b0';
                    $labelColor = '#67c23a';
                    $label = '管理员';
                } else {
                    $senderName = '系统';
                    $bgColor = '#f4f4f5';
                    $borderColor = '#d3d4d6';
                    $labelColor = '#909399';
                    $label = '系统';
                }

                if ($r['is_internal']) {
                    $bgColor = '#fdf6ec';
                    $borderColor = '#f5dab1';
                    $label .= ' [内部备注]';
                    $labelColor = '#e6a23c';
                }

                $html .= "<div style='border:1px solid {$borderColor};background:{$bgColor};border-radius:6px;padding:12px;margin-bottom:10px;'>";
                $html .= "<div style='display:flex;justify-content:space-between;margin-bottom:8px;'>";
                $html .= "<span><span style='color:{$labelColor};font-weight:bold;font-size:13px;padding:2px 8px;border:1px solid {$labelColor};border-radius:3px;'>{$label}</span> <span style='color:#606266;margin-left:8px;'>{$senderName}</span></span>";
                $html .= "<span style='color:#909399;font-size:12px;'>{$r['create_time']}</span>";
                $html .= "</div>";
                $html .= "<div style='line-height:1.8;white-space:pre-wrap;color:#303133;'>" . htmlspecialchars($r['content']) . "</div>";

                if (!empty($r['attachment_urls'])) {
                    $attachments = json_decode($r['attachment_urls'], true);
                    if (!empty($attachments) && is_array($attachments)) {
                        $html .= '<div style="margin-top:8px;">';
                        foreach ($attachments as $i => $url) {
                            $html .= "<a href='" . htmlspecialchars($url) . "' target='_blank' style='color:#409eff;margin-right:10px;font-size:12px;'>附件" . ($i + 1) . "</a>";
                        }
                        $html .= '</div>';
                    }
                }

                $html .= "</div>";
            }
        }
        $html .= '</div>';

        $html .= '<div style="text-align:center;margin-top:10px;">';
        $html .= "<a href='" . url('reply', ['id' => $id]) . "' class='btn btn-success' style='margin-right:10px;'><i class='fa fa-reply'></i> 回复工单</a>";
        $html .= "<a href='" . url('changestatus', ['id' => $id]) . "' class='btn btn-warning' style='margin-right:10px;'><i class='fa fa-exchange'></i> 修改状态</a>";
        $html .= "<a href='" . url('index') . "' class='btn btn-default'><i class='fa fa-arrow-left'></i> 返回列表</a>";
        $html .= '</div>';

        $html .= '</div>';

        return ZBuilder::make('form')
            ->setPageTitle('工单详情 - ' . $ticket['ticket_no'])
            ->setExtraHtml($html, 'form_top')
            ->hideBtn('submit')
            ->fetch();
    }

    public function reply()
    {
        $id = input('param.id', 0, 'intval');
        if (!$id) $this->error('参数错误');

        $ticket = $this->db()->table('ts_user_ticket')->where('id', $id)->find();
        if (!$ticket) $this->error('工单不存在');

        if (request()->isPost()) {
            $content = input('post.content', '', 'trim');
            $isInternal = input('post.is_internal', 0, 'intval');

            if (empty($content)) {
                $this->error('回复内容不能为空');
            }

            $now = date('Y-m-d H:i:s');
            $adminId = defined('UID') ? UID : 0;

            $this->db()->table('ts_ticket_reply')->insert([
                'ticket_id'   => $id,
                'user_id'     => 0,
                'admin_id'    => $adminId,
                'reply_type'  => 2,
                'content'     => $content,
                'is_internal' => $isInternal,
                'create_time' => $now,
            ]);

            $this->db()->table('ts_user_ticket')->where('id', $id)->update([
                'last_reply_time' => $now,
                'reply_count'     => $ticket['reply_count'] + 1,
                'update_time'     => $now,
            ]);

            if ($ticket['status'] == 1) {
                $this->db()->table('ts_user_ticket')->where('id', $id)->update([
                    'status'          => 2,
                    'assigned_admin_id' => $adminId,
                ]);
            }

            $this->success('回复成功', url('detail', ['id' => $id]));
        }

        return ZBuilder::make('form')
            ->setPageTitle('回复工单 - ' . $ticket['ticket_no'] . ' - ' . $ticket['title'])
            ->addFormItems([
                ['hidden', 'id', '', '', $id],
                ['static', 'ticket_info', '工单信息', '',
                    "编号: {$ticket['ticket_no']} | 标题: {$ticket['title']} | 状态: " . ($this->statuses[$ticket['status']] ?? '未知')
                ],
                ['textarea', 'content', '回复内容', '请输入回复内容', '', 'required'],
                ['radio', 'is_internal', '内部备注', '标记为内部备注后仅管理员可见', ['0' => '否（用户可见）', '1' => '是（仅管理员可见）'], 0],
            ])
            ->fetch();
    }

    public function changestatus()
    {
        $id = input('param.id', 0, 'intval');
        if (!$id) $this->error('参数错误');

        $ticket = $this->db()->table('ts_user_ticket')->where('id', $id)->find();
        if (!$ticket) $this->error('工单不存在');

        if (request()->isPost()) {
            $newStatus = input('post.status', 0, 'intval');
            $remark = input('post.remark', '', 'trim');

            if (!isset($this->statuses[$newStatus])) {
                $this->error('无效的状态');
            }

            if ($newStatus == $ticket['status']) {
                $this->error('状态未变化');
            }

            $now = date('Y-m-d H:i:s');
            $adminId = defined('UID') ? UID : 0;
            $oldLabel = $this->statuses[$ticket['status']] ?? '未知';
            $newLabel = $this->statuses[$newStatus] ?? '未知';

            $this->db()->table('ts_user_ticket')->where('id', $id)->update([
                'status'      => $newStatus,
                'update_time' => $now,
            ]);

            $sysContent = "系统通知：工单状态从【{$oldLabel}】变更为【{$newLabel}】";
            if (!empty($remark)) {
                $sysContent .= "\n备注：{$remark}";
            }

            $this->db()->table('ts_ticket_reply')->insert([
                'ticket_id'   => $id,
                'user_id'     => 0,
                'admin_id'    => $adminId,
                'reply_type'  => 3,
                'content'     => $sysContent,
                'is_internal' => 0,
                'create_time' => $now,
            ]);

            $this->db()->table('ts_user_ticket')->where('id', $id)->update([
                'reply_count'     => $ticket['reply_count'] + 1,
                'last_reply_time' => $now,
            ]);

            $this->success('状态修改成功', url('detail', ['id' => $id]));
        }

        return ZBuilder::make('form')
            ->setPageTitle('修改工单状态 - ' . $ticket['ticket_no'])
            ->addFormItems([
                ['hidden', 'id', '', '', $id],
                ['static', 'ticket_info', '工单信息', '',
                    "编号: {$ticket['ticket_no']} | 标题: {$ticket['title']}"
                ],
                ['static', 'current_status', '当前状态', '',
                    "<span style='color:" . ($this->statusColors[$ticket['status']] ?? '#909399') . ";font-weight:bold;'>" . ($this->statuses[$ticket['status']] ?? '未知') . "</span>"
                ],
                ['radio', 'status', '新状态', '选择要变更的状态', [
                    1 => '待处理',
                    2 => '处理中',
                    3 => '已解决',
                    4 => '已关闭',
                    5 => '已拒绝',
                ], $ticket['status']],
                ['textarea', 'remark', '变更备注', '可选，填写状态变更原因'],
            ])
            ->fetch();
    }

    private function buildFilterHtml($keyword, $statusFilter, $typeFilter, $priorityFilter)
    {
        $baseUrl = url('index');
        $kwVal = htmlspecialchars($keyword);

        $statusBtns = '';
        $allSel = ($statusFilter === '') ? 'background:#409eff;color:#fff;border-color:#409eff;' : 'background:#fff;color:#606266;border-color:#dcdfe6;';
        $statusBtns .= "<a href='{$baseUrl}?keyword={$kwVal}&status=&ticket_type={$typeFilter}&priority={$priorityFilter}' style='display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$allSel}'>全部</a> ";

        foreach ($this->statuses as $k => $v) {
            $color = $this->statusColors[$k] ?? '#409eff';
            $sel = ($statusFilter !== '' && intval($statusFilter) === $k)
                ? "background:{$color};color:#fff;border-color:{$color};"
                : 'background:#fff;color:#606266;border-color:#dcdfe6;';
            $statusBtns .= "<a href='{$baseUrl}?keyword={$kwVal}&status={$k}&ticket_type={$typeFilter}&priority={$priorityFilter}' style='display:inline-block;padding:5px 12px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;{$sel}'>{$v}</a> ";
        }

        $typeOptions = '<option value="">全部类型</option>';
        foreach ($this->ticketTypes as $k => $v) {
            $sel = ($typeFilter !== '' && intval($typeFilter) === $k) ? 'selected' : '';
            $typeOptions .= "<option value='{$k}' {$sel}>{$v}</option>";
        }

        $prioOptions = '<option value="">全部优先级</option>';
        foreach ($this->priorities as $k => $v) {
            $sel = ($priorityFilter !== '' && intval($priorityFilter) === $k) ? 'selected' : '';
            $prioOptions .= "<option value='{$k}' {$sel}>{$v}</option>";
        }

        return <<<HTML
<div style="margin-bottom:12px;padding:12px 14px;background:#f5f7fa;border-radius:6px;">
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px;">
        <span style="font-weight:bold;font-size:13px;">搜索：</span>
        <input type="text" id="tk-keyword" value="{$kwVal}" placeholder="工单编号 / 用户ID / 标题" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;width:220px;" />
        <select id="tk-type" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;">{$typeOptions}</select>
        <select id="tk-prio" style="padding:5px 10px;border:1px solid #dcdfe6;border-radius:4px;font-size:13px;">{$prioOptions}</select>
        <a href="javascript:void(0)" onclick="var kw=document.getElementById('tk-keyword').value;var tp=document.getElementById('tk-type').value;var pr=document.getElementById('tk-prio').value;window.location.href='{$baseUrl}?keyword='+encodeURIComponent(kw)+'&status={$statusFilter}&ticket_type='+tp+'&priority='+pr" style="display:inline-block;padding:5px 15px;border:1px solid #409eff;border-radius:4px;text-decoration:none;font-size:13px;background:#409eff;color:#fff;">搜索</a>
        <a href="{$baseUrl}" style="display:inline-block;padding:5px 15px;border:1px solid #dcdfe6;border-radius:4px;text-decoration:none;font-size:13px;background:#fff;color:#606266;">重置</a>
    </div>
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
        <span style="font-weight:bold;font-size:13px;">状态：</span>
        {$statusBtns}
    </div>
</div>
HTML;
    }
}
