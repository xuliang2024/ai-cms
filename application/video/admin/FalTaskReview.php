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
        $reviewId = input('param.review_id', 0, 'intval');

        $query = $this->applyTaskFilters(FalTasksModel::where([]), $appName, $status);

        $queryParams = [
            'app_name' => $appName,
            'status' => $status,
        ];
        if ($reviewId > 0) {
            $queryParams['review_id'] = $reviewId;
        }

        $dataList = $query
            ->order('created_at desc, id desc')
            ->paginate(100, false, [
                'query' => $queryParams,
            ]);

        $filterHtml = $this->buildFilterHtml($appName, $status);
        $reviewTask = $reviewId > 0 ? FalTasksModel::where('id', $reviewId)->find() : null;
        $detailHtml = $reviewId > 0 ? $this->buildDetailPanel($reviewTask, $appName, $status) : '';
        $reviewHref = $this->buildUrl(['app_name' => $appName, 'status' => $status, 'review_id' => '__id__']);
        $tips = $appName === ''
            ? '默认展示 Fal 任务最新记录；输入模型名称后按模型精确筛选。'
            : '当前模型：<b style="color:#409eff">' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</b>';

        return ZBuilder::make('table')
            ->setPageTitle('任务审查')
            ->setPageTips($tips, 'info')
            ->setTableName('video/FalTasksModel', 2)
            ->setExtraHtml($filterHtml . $detailHtml, 'toolbar_top')
            ->hideCheckbox()
            ->addColumns([
                ['id', 'ID'],
                ['right_button', '查看', 'btn'],
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
            ->addRightButton('custom', [
                'title' => '查看',
                'icon' => 'fa fa-search',
                'class' => 'btn btn-xs btn-primary',
                'href' => $reviewHref,
                'target' => '_self',
            ])
            ->setColumnWidth('right_button', 78)
            ->setRowList($dataList)
            ->setHeight('auto')
            ->fetch();
    }

    private function applyTaskFilters($query, $appName, $status)
    {
        if ($appName !== '') {
            $query = $query->where('app_name', $appName);
        }
        if ($status !== '') {
            $query = $query->where('status', $status);
        }

        return $query;
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
.fal-review-detail { margin:0 0 14px; border:1px solid #dbe5f1; background:#f6f8fb; border-radius:4px; overflow:hidden; box-shadow:0 1px 2px rgba(15, 23, 42, .06); }
.fal-review-detail-head { display:flex; justify-content:space-between; gap:16px; padding:12px 14px; background:#fff; color:#303133; border-bottom:1px solid #ebeef5; }
.fal-review-detail-title { display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700; color:#1f2937; }
.fal-review-status-dot { width:8px; height:8px; border-radius:50%; background:#60a5fa; box-shadow:0 0 0 4px rgba(96, 165, 250, .16); }
.fal-review-detail-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:6px; }
.fal-review-console-btn { display:inline-flex; align-items:center; gap:5px; min-height:30px; padding:5px 10px; border-radius:4px; background:#fff; border:1px solid #d1d5db; color:#374151; line-height:18px; }
.fal-review-console-btn:hover { color:#fff; background:#2563eb; border-color:#2563eb; }
.fal-review-console-btn.is-disabled, .fal-review-console-btn.is-disabled:hover { color:#9ca3af; background:#f9fafb; border-color:#e5e7eb; cursor:not-allowed; pointer-events:none; }
.fal-review-detail-close { color:#374151; }
.fal-review-meta { display:flex; flex-wrap:wrap; gap:8px; padding:10px 14px; background:#fff; border-top:1px solid #ebeef5; border-bottom:1px solid #ebeef5; color:#303133; }
.fal-review-meta > span { display:inline-flex; align-items:center; gap:6px; min-height:28px; padding:4px 9px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:4px; }
.fal-review-meta b { color:#6b7280; font-weight:600; }
.fal-review-status-badge { display:inline-flex; align-items:center; min-height:20px; padding:1px 8px; border-radius:999px; font-weight:700; line-height:18px; border:1px solid transparent; }
.fal-review-status-completed { color:#15803d; background:#dcfce7; border-color:#bbf7d0; }
.fal-review-status-failed { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
.fal-review-status-pending { color:#a16207; background:#fef3c7; border-color:#fde68a; }
.fal-review-status-processing, .fal-review-status-generating, .fal-review-status-submitting { color:#1d4ed8; background:#dbeafe; border-color:#bfdbfe; }
.fal-review-status-transferring { color:#4338ca; background:#e0e7ff; border-color:#c7d2fe; }
.fal-review-status-default { color:#4b5563; background:#f3f4f6; border-color:#e5e7eb; }
.fal-review-detail-body { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; padding:12px; }
.fal-review-section { min-width:0; padding:12px; background:#fff; border:1px solid #ebeef5; border-radius:4px; }
.fal-review-section-full { grid-column:1 / -1; }
.fal-review-section h4 { margin:0 0 10px; font-size:14px; font-weight:700; color:#303133; }
.fal-review-empty { color:#909399; }
.fal-review-pre { max-height:360px; overflow:auto; margin:0; padding:10px; background:#f5f7fa; border:1px solid #ebeef5; border-radius:4px; white-space:pre-wrap; word-break:break-word; color:#303133; line-height:1.55; }
.fal-review-error h4 { color:#f56c6c; }
.fal-review-error .fal-review-pre { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.fal-review-prompt-label { margin:8px 0 6px; color:#409eff; font-weight:600; }
.fal-review-prompt-label:first-of-type { margin-top:0; }
.fal-review-media-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:10px; }
.fal-review-media-item { min-width:0; padding:8px; border:1px solid #ebeef5; border-radius:4px; background:#fafafa; }
.fal-review-media-item img, .fal-review-media-item video { width:100%; max-height:220px; object-fit:contain; background:#111; border-radius:3px; }
.fal-review-image-preview { display:block; cursor:zoom-in; }
.fal-review-image-preview:focus { outline:2px solid #409eff; outline-offset:2px; }
.fal-review-media-item audio { width:100%; }
.fal-review-media-link { display:block; margin-top:6px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.fal-review-lightbox { display:none; position:fixed; z-index:99999; inset:0; padding:36px; background:rgba(15, 23, 42, .86); align-items:center; justify-content:center; }
.fal-review-lightbox.is-open { display:flex; }
.fal-review-lightbox-inner { position:relative; max-width:96vw; max-height:92vh; }
.fal-review-lightbox img { display:block; max-width:96vw; max-height:92vh; object-fit:contain; background:#111; border-radius:4px; box-shadow:0 20px 60px rgba(0,0,0,.45); }
.fal-review-lightbox-close { position:absolute; top:-34px; right:0; width:30px; height:30px; border:0; border-radius:4px; color:#fff; background:rgba(255,255,255,.18); font-size:22px; line-height:30px; text-align:center; cursor:pointer; }
.fal-review-lightbox-close:hover { background:rgba(255,255,255,.28); }
.fal-review-output-video { background:#f8fbff; border-color:#bfdbfe; }
.fal-review-output-video h4 { font-size:15px; color:#111827; }
.fal-review-output-video .fal-review-media-grid { grid-template-columns:repeat(auto-fit, minmax(640px, 1fr)); }
.fal-review-output-video .fal-review-media-item { padding:10px; background:#0b1220; border-color:#1f2937; }
.fal-review-output-video .fal-review-media-item video { min-height:360px; max-height:560px; background:#000; }
.fal-review-output-video .fal-review-media-link { color:#93c5fd; }
.fal-review-raw details { margin-top:8px; }
.fal-review-raw summary { cursor:pointer; color:#409eff; font-weight:600; }
@media (max-width: 1200px) {
    .fal-review-detail-body { grid-template-columns:1fr; }
    .fal-review-output-video .fal-review-media-grid { grid-template-columns:1fr; }
    .fal-review-output-video .fal-review-media-item video { min-height:260px; max-height:420px; }
}
</style>
<script>
(function () {
    if (window.__falReviewLightboxReady) {
        return;
    }
    window.__falReviewLightboxReady = true;

    function ensureLightbox() {
        var lightbox = document.getElementById('fal-review-lightbox');
        if (lightbox) {
            return lightbox;
        }

        lightbox = document.createElement('div');
        lightbox.id = 'fal-review-lightbox';
        lightbox.className = 'fal-review-lightbox';
        lightbox.innerHTML = '<div class="fal-review-lightbox-inner"><button type="button" class="fal-review-lightbox-close" aria-label="关闭">&times;</button><img alt=""></div>';
        document.body.appendChild(lightbox);
        return lightbox;
    }

    function closeLightbox() {
        var lightbox = document.getElementById('fal-review-lightbox');
        if (lightbox) {
            lightbox.className = 'fal-review-lightbox';
            lightbox.querySelector('img').removeAttribute('src');
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest && event.target.closest('.fal-review-image-preview');
        if (trigger) {
            event.preventDefault();
            var lightbox = ensureLightbox();
            var image = lightbox.querySelector('img');
            image.src = trigger.getAttribute('data-image-url') || trigger.getAttribute('href');
            image.alt = trigger.getAttribute('data-image-title') || '';
            lightbox.className = 'fal-review-lightbox is-open';
            return;
        }

        if (event.target.id === 'fal-review-lightbox' ||
            (event.target.classList && event.target.classList.contains('fal-review-lightbox-close'))) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });
})();
</script>
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

    private function buildDetailPanel($task, $appName, $status)
    {
        $closeUrl = $this->buildUrl(['app_name' => $appName, 'status' => $status]);
        if (empty($task)) {
            return <<<HTML
<div class="fal-review-detail">
    <div class="fal-review-detail-head">
        <div>
            <div class="fal-review-detail-title"><span class="fal-review-status-dot"></span>任务审查控制台</div>
        </div>
        <div class="fal-review-detail-actions">
            <a class="fal-review-console-btn" href="{$closeUrl}"><i class="fa fa-times"></i> 关闭</a>
        </div>
    </div>
    <div class="fal-review-meta"><span>未找到这条任务记录</span></div>
</div>
HTML;
        }

        $inputRaw = isset($task['input_params']) ? $task['input_params'] : '';
        $outputRaw = isset($task['output_params']) ? $task['output_params'] : '';
        $input = $this->decodeJsonValue($inputRaw);
        $output = $this->decodeJsonValue($outputRaw);
        $inputMedia = $this->extractMedia($input);
        $outputMedia = $this->extractMedia($output);
        $prompts = $this->extractPromptBlocks($input);
        $errorText = $this->extractErrorText($output);

        if ($errorText === '' && isset($task['status']) && $task['status'] === 'failed') {
            $errorText = $this->prettyJson($outputRaw);
        }

        $id = $this->safeText($task['id']);
        $userId = $this->safeText($task['user_id']);
        $statusText = $this->renderStatusBadge($task['status']);
        $appNameText = $this->safeText($task['app_name']);
        $money = $this->safeText($task['money']);
        $createdAt = $this->safeText($task['created_at']);
        $completedAt = $this->safeText($task['completed_at'] ?: '-');
        $taskId = $this->safeText($task['task_id']);
        $onlineTaskId = $this->safeText($task['online_task_id'] ?: '-');
        $neighbors = $this->getNeighborTasks($task, $appName, $status);
        $previousButton = $this->renderNavButton($neighbors['previous'], '上一条', 'fa fa-chevron-left', $appName, $status);
        $nextButton = $this->renderNavButton($neighbors['next'], '下一条', 'fa fa-chevron-right', $appName, $status);

        $promptHtml = $this->renderPromptSection($prompts);
        $imageHtml = $this->renderMediaSection('参考图', $inputMedia['image'], 'image');
        $videoHtml = $this->renderMediaSection('参考视频', $inputMedia['video'], 'video');
        $audioHtml = $this->renderMediaSection('参考音频', $inputMedia['audio'], 'audio');
        $outputVideoHtml = $this->renderMediaSection('输出视频结果', $outputMedia['video'], 'video', 'fal-review-section-full fal-review-output-video');
        $outputMediaHtml = '';
        if (!empty($outputMedia['image']) || !empty($outputMedia['audio'])) {
            $outputMediaHtml .= $this->renderMediaSection('输出图片', $outputMedia['image'], 'image');
            $outputMediaHtml .= $this->renderMediaSection('输出音频', $outputMedia['audio'], 'audio');
        }
        $errorHtml = $this->renderErrorSection($errorText);
        $rawJsonHtml = $this->renderRawJsonSection($inputRaw, $outputRaw);

        return <<<HTML
<div class="fal-review-detail" id="fal-review-detail">
    <div class="fal-review-detail-head">
        <div>
            <div class="fal-review-detail-title"><span class="fal-review-status-dot"></span>任务审查控制台</div>
        </div>
        <div class="fal-review-detail-actions">
            {$previousButton}
            {$nextButton}
            <a class="fal-review-console-btn" href="{$closeUrl}"><i class="fa fa-times"></i> 关闭</a>
        </div>
    </div>
    <div class="fal-review-meta">
        <span><b>ID</b> {$id}</span>
        <span><b>用户ID</b> {$userId}</span>
        <span><b>模型</b> {$appNameText}</span>
        <span><b>状态</b> {$statusText}</span>
        <span><b>金额(分)</b> {$money}</span>
        <span><b>创建</b> {$createdAt}</span>
        <span><b>完成</b> {$completedAt}</span>
        <span><b>系统任务ID</b> {$taskId}</span>
        <span><b>FAL任务ID</b> {$onlineTaskId}</span>
    </div>
    <div class="fal-review-detail-body">
        {$outputVideoHtml}
        {$promptHtml}
        {$errorHtml}
        {$imageHtml}
        {$videoHtml}
        {$audioHtml}
        {$outputMediaHtml}
        {$rawJsonHtml}
    </div>
</div>
HTML;
    }

    private function renderNavButton($task, $title, $icon, $appName, $status)
    {
        $title = $this->safeText($title);
        $icon = $this->safeText($icon);
        if (empty($task)) {
            return '<span class="fal-review-console-btn is-disabled"><i class="' . $icon . '"></i> ' . $title . '</span>';
        }

        $href = $this->buildUrl([
            'app_name' => $appName,
            'status' => $status,
            'review_id' => $task['id'],
        ]);

        return '<a class="fal-review-console-btn" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') .
            '"><i class="' . $icon . '"></i> ' . $title . '</a>';
    }

    private function renderStatusBadge($status)
    {
        $status = strval($status ?: '-');
        $key = strtolower($status);
        $classes = [
            'completed' => 'completed',
            'failed' => 'failed',
            'pending' => 'pending',
            'processing' => 'processing',
            'generating' => 'generating',
            'submitting' => 'submitting',
            'transferring' => 'transferring',
        ];
        $class = isset($classes[$key]) ? $classes[$key] : 'default';

        return '<span class="fal-review-status-badge fal-review-status-' . $class . '">' .
            $this->safeText($status) . '</span>';
    }

    private function getNeighborTasks($task, $appName, $status)
    {
        $createdAt = $task['created_at'];
        $id = intval($task['id']);

        $previousQuery = $this->applyTaskFilters(FalTasksModel::where([]), $appName, $status);
        $previous = $previousQuery
            ->where(function ($query) use ($createdAt, $id) {
                $query->where('created_at', '>', $createdAt)
                    ->whereOr(function ($query) use ($createdAt, $id) {
                        $query->where('created_at', '=', $createdAt)->where('id', '>', $id);
                    });
            })
            ->field('id')
            ->order('created_at asc, id asc')
            ->find();

        $nextQuery = $this->applyTaskFilters(FalTasksModel::where([]), $appName, $status);
        $next = $nextQuery
            ->where(function ($query) use ($createdAt, $id) {
                $query->where('created_at', '<', $createdAt)
                    ->whereOr(function ($query) use ($createdAt, $id) {
                        $query->where('created_at', '=', $createdAt)->where('id', '<', $id);
                    });
            })
            ->field('id')
            ->order('created_at desc, id desc')
            ->find();

        return [
            'previous' => $previous,
            'next' => $next,
        ];
    }

    private function renderPromptSection($prompts)
    {
        $html = '<div class="fal-review-section fal-review-section-full"><h4>提示词</h4>';
        if (empty($prompts)) {
            return $html . '<div class="fal-review-empty">未发现 prompt/text/script 字段</div></div>';
        }

        foreach ($prompts as $prompt) {
            $label = $this->safeText($prompt['label']);
            $text = $this->limitText($prompt['text'], 20000);
            $html .= '<div class="fal-review-prompt-label">' . $label . '</div>';
            $html .= '<pre class="fal-review-pre">' . $this->safeText($text) . '</pre>';
        }

        return $html . '</div>';
    }

    private function renderMediaSection($title, $items, $type, $extraClass = '')
    {
        $title = $this->safeText($title);
        $class = trim('fal-review-section ' . $extraClass);
        $html = '<div class="' . $class . '"><h4>' . $title . '</h4>';
        if (empty($items)) {
            return $html . '<div class="fal-review-empty">未发现</div></div>';
        }

        $html .= '<div class="fal-review-media-grid">';
        foreach (array_slice($items, 0, 12) as $url) {
            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="fal-review-media-item">';
            if ($type === 'image') {
                $html .= '<a class="fal-review-image-preview" href="' . $safeUrl . '" data-image-url="' . $safeUrl . '" data-image-title="' . $title . '"><img src="' . $safeUrl . '" loading="lazy"></a>';
            } elseif ($type === 'video') {
                $html .= '<video controls preload="metadata" src="' . $safeUrl . '"></video>';
            } elseif ($type === 'audio') {
                $html .= '<audio controls preload="metadata" src="' . $safeUrl . '"></audio>';
            }
            $html .= '<a class="fal-review-media-link" href="' . $safeUrl . '" target="_blank" title="' . $safeUrl . '">打开链接</a>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html . '</div>';
    }

    private function renderErrorSection($errorText)
    {
        $html = '<div class="fal-review-section fal-review-section-full fal-review-error"><h4>失败信息</h4>';
        if ($errorText === '') {
            return $html . '<div class="fal-review-empty">当前没有失败信息</div></div>';
        }

        return $html . '<pre class="fal-review-pre">' . $this->safeText($this->limitText($errorText, 20000)) . '</pre></div>';
    }

    private function renderRawJsonSection($inputRaw, $outputRaw)
    {
        $input = $this->safeText($this->limitText($this->prettyJson($inputRaw), 30000));
        $output = $this->safeText($this->limitText($this->prettyJson($outputRaw), 30000));

        return <<<HTML
<div class="fal-review-section fal-review-section-full fal-review-raw">
    <h4>原始参数</h4>
    <details>
        <summary>输入参数 JSON</summary>
        <pre class="fal-review-pre">{$input}</pre>
    </details>
    <details>
        <summary>输出参数 JSON</summary>
        <pre class="fal-review-pre">{$output}</pre>
    </details>
</div>
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

    private function decodeJsonValue($value, $depth = 0)
    {
        if ($depth > 5) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->decodeJsonValue($item, $depth + 1);
            }
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $first = substr($trimmed, 0, 1);
        $last = substr($trimmed, -1);
        if (($first === '{' && $last === '}') || ($first === '[' && $last === ']')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->decodeJsonValue($decoded, $depth + 1);
            }
        }

        return $value;
    }

    private function extractMedia($value)
    {
        $media = [
            'image' => [],
            'video' => [],
            'audio' => [],
        ];
        $this->collectMedia($value, '', $media, 0);
        return $media;
    }

    private function collectMedia($value, $keyPath, &$media, $depth)
    {
        if ($depth > 8) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $nextPath = trim($keyPath . ' ' . $key);
                $this->collectMedia($item, $nextPath, $media, $depth + 1);
            }
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $url = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
        if (!$this->isHttpUrl($url)) {
            return;
        }

        $type = $this->detectMediaType($url, $keyPath);
        if ($type === '') {
            return;
        }

        if (!in_array($url, $media[$type], true)) {
            $media[$type][] = $url;
        }
    }

    private function detectMediaType($url, $keyPath)
    {
        $lowerKey = strtolower($keyPath);
        $path = parse_url($url, PHP_URL_PATH);
        $lowerPath = strtolower($path ?: $url);

        if (preg_match('/\.(jpg|jpeg|png|webp|gif|bmp|svg)$/i', $lowerPath) ||
            preg_match('/(image|img|picture|photo|cover|thumbnail|thumb|frame|reference|avatar)/i', $lowerKey)) {
            return 'image';
        }

        if (preg_match('/\.(mp4|mov|webm|m4v|m3u8)$/i', $lowerPath) ||
            preg_match('/(video|movie|clip|mp4|webm|mov)/i', $lowerKey)) {
            return 'video';
        }

        if (preg_match('/\.(mp3|wav|m4a|aac|ogg|flac)$/i', $lowerPath) ||
            preg_match('/(audio|voice|music|sound|speech|tts)/i', $lowerKey)) {
            return 'audio';
        }

        return '';
    }

    private function extractPromptBlocks($value)
    {
        $prompts = [];
        $this->collectPromptBlocks($value, '', $prompts, 0);

        $unique = [];
        $result = [];
        foreach ($prompts as $prompt) {
            $fingerprint = md5($prompt['label'] . "\n" . $prompt['text']);
            if (isset($unique[$fingerprint])) {
                continue;
            }
            $unique[$fingerprint] = true;
            $result[] = $prompt;
            if (count($result) >= 8) {
                break;
            }
        }

        return $result;
    }

    private function collectPromptBlocks($value, $keyPath, &$prompts, $depth)
    {
        if ($depth > 8) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $nextPath = trim($keyPath . ' ' . $key);
                $this->collectPromptBlocks($item, $nextPath, $prompts, $depth + 1);
            }
            return;
        }

        if (!is_string($value) || trim($value) === '' || $this->isHttpUrl(trim($value))) {
            return;
        }

        $lowerKey = strtolower($keyPath);
        if (strpos($lowerKey, 'prompt') === false &&
            !preg_match('/(^| )(text|script|story|caption|description|negative)$/i', $keyPath)) {
            return;
        }

        $prompts[] = [
            'label' => $keyPath ?: 'prompt',
            'text' => $value,
        ];
    }

    private function extractErrorText($value)
    {
        $errors = [];
        $this->collectErrors($value, '', $errors, 0);

        if (empty($errors)) {
            return '';
        }

        return implode("\n\n", array_unique($errors));
    }

    private function collectErrors($value, $keyPath, &$errors, $depth)
    {
        if ($depth > 8) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $nextPath = trim($keyPath . ' ' . $key);
                $this->collectErrors($item, $nextPath, $errors, $depth + 1);
            }
            return;
        }

        if (!is_string($value) || trim($value) === '') {
            return;
        }

        if (!preg_match('/(error|message|reason|detail|code)/i', $keyPath)) {
            return;
        }

        $decoded = $this->decodeJsonValue($value);
        if (is_array($decoded)) {
            $errors[] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            $errors[] = $value;
        }
    }

    private function buildUrl($params)
    {
        $query = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $query[$key] = $value;
        }

        $url = url('index');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function isHttpUrl($value)
    {
        return preg_match('/^https?:\/\//i', $value) === 1;
    }

    private function limitText($text, $length)
    {
        $text = strval($text);
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, 'UTF-8') . "\n...（内容过长，已截断）";
    }

    private function safeText($value)
    {
        return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
    }

    private function renderCopyText($value)
    {
        $value = strval($value);
        $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return "<span class=\"fal-review-copy\" title=\"{$safeValue}\">{$safeValue}</span>";
    }
}
