<?php
// Fal 任务审查
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\video\model\FalTasksModel;

class FalTaskReview extends Admin
{
    public function index()
    {
        $appName = input('param.app_name', '', 'trim');
        $status = input('param.status', '', 'trim');

        $query = FalTasksModel::where([]);
        if ($appName !== '') {
            $query = $query->where('app_name', $appName);
        }
        if ($status !== '') {
            $query = $query->where('status', $status);
        }

        $dataList = $query
            ->order('created_at desc')
            ->paginate(100, false, [
                'query' => [
                    'app_name' => $appName,
                    'status' => $status,
                ],
            ]);

        $filterHtml = $this->buildFilterHtml($appName, $status);
        $tips = $appName === ''
            ? '默认展示 Fal 任务最新记录；输入模型名称后按模型精确筛选。'
            : '当前模型：<b style="color:#409eff">' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</b>';

        return ZBuilder::make('table')
            ->setPageTitle('任务审查')
            ->setPageTips($tips, 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->setExtraHtml($filterHtml, 'toolbar_top')
            ->hideCheckbox()
            ->addColumns([
                ['id', 'ID'],
                ['user_id', '用户ID'],
                ['task_id', '系统任务ID', 'callback', function ($value) {
                    return $this->renderCopyText($value);
                }],
                ['online_task_id', 'Fal任务ID', 'callback', function ($value) {
                    return $this->renderCopyText($value ?: '-');
                }],
                ['app_name', '模型名称', 'callback', function ($value) {
                    $value = $value ?: '(未知)';
                    return '<span style="font-weight:bold;color:#409eff;">' .
                        htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</span>';
                }],
                ['money', '金额(分)', 'callback', function ($value) {
                    $color = floatval($value) > 0 ? '#67c23a' : '#909399';
                    return "<span style=\"color:{$color}\">{$value}</span>";
                }],
                ['status', '状态', 'callback', function ($value) {
                    $colors = [
                        'completed' => '#67c23a',
                        'failed' => '#f56c6c',
                        'pending' => '#e6a23c',
                        'processing' => '#409eff',
                        'generating' => '#409eff',
                        'submitting' => '#409eff',
                        'transferring' => '#909399',
                    ];
                    $color = $colors[$value] ?? '#909399';
                    return "<span style=\"color:{$color};font-weight:bold;\">{$value}</span>";
                }],
                ['is_refund', '退款', 'callback', function ($value) {
                    return intval($value) === 1 ? '<span style="color:#f56c6c">已退款</span>' : '-';
                }],
                ['created_at', '创建时间'],
                ['completed_at', '完成时间'],
                ['input_params', '输入参数', 'callback', function ($value) {
                    return $this->renderJsonPreview($value);
                }],
                ['output_params', '输出参数', 'callback', function ($value) {
                    return $this->renderJsonPreview($value);
                }],
            ])
            ->setRowList($dataList)
            ->setHeight('auto')
            ->fetch();
    }

    private function buildFilterHtml($appName, $status)
    {
        $appNameEsc = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $statusOptions = [
            '' => '全部状态',
            'completed' => 'completed',
            'failed' => 'failed',
            'pending' => 'pending',
            'processing' => 'processing',
            'generating' => 'generating',
            'submitting' => 'submitting',
            'transferring' => 'transferring',
        ];

        $optionsHtml = '';
        foreach ($statusOptions as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $optionsHtml .= "<option value=\"{$valueEsc}\"{$selected}>{$labelEsc}</option>";
        }

        $action = url('index');

        return <<<HTML
<style>
.fal-review-filter { display:flex; align-items:center; gap:8px; margin-bottom:12px; padding:12px; background:#f8f9fb; border:1px solid #ebeef5; border-radius:4px; }
.fal-review-filter label { margin:0 4px 0 0; font-weight:600; color:#606266; }
.fal-review-filter .form-control { width:280px; height:32px; }
.fal-review-filter select.form-control { width:150px; }
.fal-review-json { display:block; max-width:360px; max-height:56px; overflow:hidden; white-space:pre-wrap; word-break:break-all; color:#606266; line-height:18px; }
.fal-review-copy { display:inline-block; max-width:220px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; vertical-align:middle; }
</style>
<form class="fal-review-filter" method="get" action="{$action}">
    <label>模型名称</label>
    <input class="form-control" type="text" name="app_name" value="{$appNameEsc}" placeholder="例如 st-ai/super-seed2-lite">
    <label>状态</label>
    <select class="form-control" name="status">{$optionsHtml}</select>
    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> 加载</button>
    <a class="btn btn-default" href="{$action}">清空</a>
</form>
HTML;
    }

    private function renderJsonPreview($value)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $pretty = $this->prettyJson($value);
        $short = mb_strlen($pretty, 'UTF-8') > 180
            ? mb_substr($pretty, 0, 180, 'UTF-8') . '...'
            : $pretty;
        $title = mb_strlen($pretty, 'UTF-8') > 500
            ? mb_substr($pretty, 0, 500, 'UTF-8') . '...'
            : $pretty;

        return '<span class="fal-review-json" title="' .
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' .
            htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    private function prettyJson($value)
    {
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function renderCopyText($value)
    {
        $value = strval($value);
        $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return "<span class=\"fal-review-copy\" title=\"{$safeValue}\">{$safeValue}</span>";
    }
}
