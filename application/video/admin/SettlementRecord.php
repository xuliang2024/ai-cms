<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\SettlementRecordModel;
use app\video\model\FinancialTransactionsModel; // Added import for FinancialTransactionsModel

class SettlementRecord extends Admin {

    public function index()
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();

        // 手动处理日期范围
        if (isset($map['settle_date']) && $map['settle_date'] != '') {
            $dates = explode(' - ', $map['settle_date']);
            if (isset($dates[0]) && isset($dates[1])) {
                $map[] = ['settle_date', 'between time', [$dates[0], $dates[1]]];
            }
            unset($map['settle_date']);
        }

        // 计算筛选后的总金额
        $total_money = SettlementRecordModel::where($map)->sum('money');
        $total_money_formatted = number_format($total_money / 100, 2, '.', '');

        $data_list = SettlementRecordModel::where($map)
        ->order($order)
        ->paginate();

        cookie('ts_settlement_record', $map);
        $contro_top_btn = ['add' , 'delete'];
        $contro_right_btn = ['edit', 'delete'];
        return ZBuilder::make('table')
            ->setPageTips('当前筛选条件下，总金额为：' . $total_money_formatted . ' 元')
            ->setTableName('video/SettlementRecordModel', 2) // 设置数据表名
            ->setPrimaryKey('id') // 设置主键
            ->addColumns([
                    ['id', 'ID'],
                    ['settle_date', '结算日期'],
                    ['user_type', '用户类型'],
                    ['douyin_video_url', '抖音视频', 'callback', function($value) {
                        return $value ? '<a href="'.$value.'" target="_blank">查看视频</a>' : '-';
                    }],
                    ['douyin_uid', '抖音UID'],
                    ['gid', 'GID'],
                    ['promotion_name', '推广名字' , 'text.edit'],
                    ['user_scale', '用户量级'],
                    ['money', '金额', 'callback', function($value) { return number_format($value / 100, 2, '.', ''); }],
                    ['is_wallet' , '打款状态' , 'callback' , function($value) {
                        $status = $value ? '<span class="label label-success">已打款</span>' : '<span class="label label-warning">未打款</span>';
                        // 添加data属性用于JavaScript判断
                        return '<span data-is-wallet="'.$value.'">'.$status.'</span>';
                    }],
                    ['create_time', '创建时间'],
                    ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addTopButton('custom', [
                'title' => '批量同意',
                'icon' => 'fa fa-check-square-o',
                'class' => 'btn btn-success ajax-post confirm',
                'target-form' => 'ids',
                'href' => url('agree'),
                'data-confirm' => '确定要批量同意选中的记录吗？'
            ])
            ->addTopButton('custom', [
                'title' => '导入数据',
                'icon' => 'fa fa-upload',
                'class' => 'btn btn-success',
                'href' => url('import')
            ])
            ->addRightButtons($contro_right_btn)
            ->addRightButton('custom', [
                'title' => '同意',
                'icon' => 'fa fa-check',
                'class' => 'btn btn-xs btn-success ajax-get',
                'href' => url('agree', ['ids' => '__id__']),
                'target' => '_self'
            ],
            ['is_wallet' => 0]
            )
            ->setSearchArea([
                ['text', 'douyin_uid', '抖音UID'],
                ['text', 'gid', 'GID'],
                ['text', 'promotion_name', '推广名字'],
                ['text', 'user_type', '用户类型'],
                ['daterange', 'settle_date', '结算日期'],
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
            $data['create_time'] = date('Y-m-d H:i:s');

            $r = SettlementRecordModel::insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['date', 'settle_date', '结算日期', '必填', '', 'required'],
                ['select', 'user_type', '用户类型', '必填', ['失活用户' => '失活用户', '新用户' => '新用户'], '', 'required'],
                ['text', 'douyin_video_url', '抖音视频URL'],
                ['text', 'douyin_uid', '抖音UID'],
                ['text', 'gid', 'GID'],
                ['text', 'promotion_name', '推广名字'],
                ['number', 'user_scale', '用户量级', '', 0],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();

            $r = SettlementRecordModel::where('id', $id)->update($data);
            if ($r !== false) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = SettlementRecordModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['date', 'settle_date', '结算日期', '必填', '', 'required'],
                ['select', 'user_type', '用户类型', '必填', ['失活用户' => '失活用户', '新用户' => '新用户'], '', 'required'],
                ['text', 'douyin_video_url', '抖音视频URL'],
                ['text', 'douyin_uid', '抖音UID'],
                ['text', 'gid', 'GID'],
                ['text', 'promotion_name', '推广名字'],
                ['number', 'user_scale', '用户量级'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 导出数据
     */
    public function export()
    {
        // 查询所有数据
        $data = SettlementRecordModel::order('id desc')
            ->order('id desc')
            ->select();

        if (empty($data)) {
            $this->error('暂无数据可导出');
        }

        // 格式化数据
        $exportData = [];
        foreach ($data as $item) {
            $exportData[] = [
                'id' => $item['id'],
                'settle_date' => $item['settle_date'],
                'user_type' => $item['user_type'] ?: '-',
                'douyin_video_url' => $item['douyin_video_url'] ?: '-',
                'douyin_uid' => $item['douyin_uid'] ?: '-',
                'gid' => $item['gid'] ?: '-',
                'promotion_name' => $item['promotion_name'] ?: '-',
                'user_scale' => $item['user_scale'],
                'create_time' => $item['create_time']
            ];
        }

        // 表头配置
        $header = [
            ['id', 8, 'ID'],
            ['settle_date', 12, '结算日期'],
            ['user_type', 12, '用户类型'],
            ['douyin_video_url', 40, '抖音视频URL'],
            ['douyin_uid', 15, '抖音UID'],
            ['gid', 15, 'GID'],
            ['promotion_name', 20, '推广名字'],
            ['user_scale', 12, '用户量级'],
            ['create_time', 20, '创建时间']
        ];

        // 文件名
        $filename = '结算记录数据_' . date('Y年m月d日');

        // 调用Excel插件导出
        $excel = new \plugins\Excel\controller\Excel();
        $excel->export($filename, $header, $exportData);
    }

    /**
     * 导入数据
     */
    public function import()
    {
        if ($this->request->isPost()) {
            $file_id = $this->request->post('excel_file');
            if (!$file_id) {
                $this->error('请选择要导入的Excel文件');
            }

            // 根据文件ID获取文件信息
            $file_info = Db::name('admin_attachment')->where('id', $file_id)->find();
            if (!$file_info) {
                $this->error('文件不存在');
            }

            // 构建完整文件路径
            if ($file_info['driver'] == 'local') {
                // 本地文件，path是相对于public目录的路径
                $file_path = realpath('.' . DIRECTORY_SEPARATOR . $file_info['path']);
            } else {
                // 远程文件，直接使用path
                $file_path = $file_info['path'];
            }

            if (!$file_path || !file_exists($file_path)) {
                $this->error('文件不存在或已被删除，文件路径：' . $file_info['path']);
            }

            try {
                // 获取文件扩展名
                $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

                // 根据文件类型选择不同的读取方式
                if ($file_extension == 'csv') {
                    // 直接读取CSV文件
                    $data = $this->readCsvFile($file_path);
                } else if ($file_extension == 'xlsx' || $file_extension == 'xls') {
                    // 尝试将Excel文件转换为CSV读取
                    $data = $this->readExcelAsCSV($file_path);
                } else {
                    $this->error('不支持的文件格式，请上传CSV、XLS或XLSX文件');
                }

                if (empty($data)) {
                    $this->error('文件为空或格式错误');
                }

                $success_count = 0;
                $error_count = 0;
                $error_messages = [];

                // 跳过第一行表头，从第二行开始处理数据
                foreach ($data as $row_index => $row) {
                    if ($row_index == 1) {
                        continue; // 跳过表头
                    }

                    if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                        continue; // 跳过空行
                    }

                    try {
                        // 映射Excel列到数据库字段
                        $user_type_raw = $row[1] ?: '';
                        $promotion_name_raw = $row[6] ?: '';
                        $user_scale = isset($row[5]) ? intval($row[5]) : 0;
                        $money = 0;

                        // 清理数据用于计算，处理值前面可能存在的数字和空格
                        $user_type_for_calc = '';
                        if (strpos($user_type_raw, '新用户') !== false) {
                            $user_type_for_calc = '新用户';
                        } elseif (strpos($user_type_raw, '失活用户') !== false) {
                            $user_type_for_calc = '失活用户';
                        }

                        $promotion_name_for_calc = '';
                        if (strpos($promotion_name_raw, '剪映（模版拉新）') !== false) {
                            $promotion_name_for_calc = '剪映（模版拉新）';
                        } elseif (strpos($promotion_name_raw, '剪映（工具拉新）') !== false) {
                            $promotion_name_for_calc = '剪映（工具拉新）';
                        }

                        if ($promotion_name_for_calc == '剪映（模版拉新）') {
                            if ($user_type_for_calc == '新用户') {
                                $money = 200 * $user_scale;
                            } else if ($user_type_for_calc == '失活用户') {
                                $money = 70 * $user_scale;
                            }
                        } else if ($promotion_name_for_calc == '剪映（工具拉新）') {
                            if ($user_type_for_calc == '新用户') {
                                $money = 600 * $user_scale;
                            } else if ($user_type_for_calc == '失活用户') {
                                $money = 70 * $user_scale;
                            }
                        }

                        $insert_data = [
                            'settle_date' => $row[0] ?: '', // 日期
                            'user_type' => $user_type_raw, // 用户类型
                            'douyin_video_url' => $row[2] ?: '', // 抖音视频url
                            'douyin_uid' => $row[3] ?: '', // 抖音UID
                            'gid' => $row[4] ?: '', // GID
                            'promotion_name' => $promotion_name_raw, // 锚点类型（对应推广名字）
                            'user_scale' => $user_scale, // 用户量级
                            'money' => $money, // 金额
                            'create_time' => date('Y-m-d H:i:s')
                        ];

                        // 插入数据库
                        $db_result = SettlementRecordModel::insert($insert_data);
                        if ($db_result) {
                            $success_count++;
                        } else {
                            $error_count++;
                            $error_messages[] = "第" . $row_index . "行：数据插入失败";
                        }
                    } catch (\Exception $e) {
                        $error_count++;
                        $error_messages[] = "第" . $row_index . "行：" . $e->getMessage();
                    }
                }

                // 文件处理完成，无需删除（文件由系统管理）

                if ($success_count > 0) {
                    $message = "导入完成！成功导入 {$success_count} 条记录";
                    if ($error_count > 0) {
                        $message .= "，失败 {$error_count} 条";
                        if (!empty($error_messages)) {
                            $message .= "<br/>错误详情：" . implode('；', array_slice($error_messages, 0, 5));
                            if (count($error_messages) > 5) {
                                $message .= "...（还有" . (count($error_messages) - 5) . "条错误）";
                            }
                        }
                    }

                    // 直接输出结果，避免跳转问题
                    echo json_encode([
                        'code' => 1,
                        'msg' => $message,
                        'data' => [
                            'success_count' => $success_count,
                            'error_count' => $error_count,
                            'redirect' => '/admin.php/video/settlement_record/index'
                        ],
                        'url' => '/admin.php/video/settlement_record/index',
                        'wait' => 3
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    $this->error('导入失败：' . implode('；', $error_messages));
                }

            } catch (\Exception $e) {
                $error_details = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                    'file_path' => isset($file_path) ? $file_path : '未设置',
                    'trace' => $e->getTraceAsString()
                ];
                $this->error('文件解析失败：' . $e->getMessage() . ' 详细信息：' . json_encode($error_details, JSON_UNESCAPED_UNICODE));
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['file', 'excel_file', '数据文件', '请选择要导入的数据文件，支持.csv、.xls和.xlsx格式', '', '5120', 'csv,xls,xlsx'],
                ['static', '', '格式说明', '请确保文件第一行为表头，数据从第二行开始<br/>
                 列顺序：日期 | 用户类型 | 抖音视频url | 抖音UID | GID | 用户量级 | 锚点类型<br/>
                 <strong>建议：</strong>如果Excel文件导入失败，请将文件另存为CSV格式后重新上传']
            ])
            ->setFormData([])
            ->fetch();
    }

    /**
     * 读取CSV文件
     */
    private function readCsvFile($file_path)
    {
        $data = [];
        $row_index = 1;

        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // 处理编码问题
                $row = array_map(function($item) {
                    // 检测编码并转换为UTF-8
                    $encoding = mb_detect_encoding($item, array('UTF-8', 'GBK', 'GB2312'), true);
                    if ($encoding && $encoding != 'UTF-8') {
                        return mb_convert_encoding($item, 'UTF-8', $encoding);
                    }
                    return $item;
                }, $row);

                $data[$row_index] = $row;
                $row_index++;
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * 读取Excel文件并转换为CSV格式
     */
    private function readExcelAsCSV($file_path)
    {
        try {
            // 获取文件扩展名
            $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            // 使用SimpleXLSX库读取Excel文件（如果可用）
            if (class_exists('SimpleXLSX')) {
                if ($xlsx = \SimpleXLSX::parse($file_path)) {
                    $data = [];
                    $rows = $xlsx->rows();
                    $row_index = 1;
                    foreach ($rows as $row) {
                        $data[$row_index] = $row;
                        $row_index++;
                    }
                    return $data;
                }
            }

            // 尝试使用PHPSpreadsheet（如果可用）
            if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
                $spreadsheet = $reader->load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();

                $data = [];
                $row_index = 1;
                foreach ($worksheet->getRowIterator() as $row) {
                    $row_data = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(FALSE);

                    foreach ($cellIterator as $cell) {
                        $row_data[] = $cell->getValue();
                    }
                    $data[$row_index] = $row_data;
                    $row_index++;
                }
                return $data;
            }

            // 尝试使用简单的XML解析方式读取xlsx文件
            if ($file_extension == 'xlsx') {
                $data = $this->readXlsxAsXML($file_path);
                if (!empty($data)) {
                    return $data;
                }
            }

            // 最后尝试使用原有的Excel插件
            $excel = new \plugins\Excel\controller\Excel();
            $result = $excel->import($file_path);

            if ($result['error'] == 0 && isset($result['data']['Content'])) {
                return $result['data']['Content'];
            }

            throw new \Exception('无法读取Excel文件，请将文件保存为CSV格式后重新上传');

        } catch (\Exception $e) {
            throw new \Exception('Excel文件读取失败：' . $e->getMessage());
        }
    }

    /**
     * 使用XML解析方式读取XLSX文件
     */
    private function readXlsxAsXML($file_path)
    {
        try {
            // XLSX文件实际上是一个ZIP压缩包
            $zip = new \ZipArchive();
            if ($zip->open($file_path) !== TRUE) {
                return false;
            }

            // 读取共享字符串
            $shared_strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
                if ($shared_strings_xml) {
                    $xml = simplexml_load_string($shared_strings_xml);
                    if ($xml) {
                        foreach ($xml->si as $si) {
                            $shared_strings[] = (string)$si->t;
                        }
                    }
                }
            }

            // 读取第一个工作表
            $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            if (!$sheet_xml) {
                return false;
            }

            $xml = simplexml_load_string($sheet_xml);
            if (!$xml) {
                return false;
            }

            $data = [];
            $row_index = 1;

            foreach ($xml->sheetData->row as $row) {
                $row_data = [];
                $col_index = 0;

                foreach ($row->c as $cell) {
                    $value = '';

                    // 获取单元格值
                    if (isset($cell->v)) {
                        $value = (string)$cell->v;

                        // 检查是否是共享字符串引用
                        if (isset($cell['t']) && (string)$cell['t'] === 's') {
                            $index = (int)$value;
                            if (isset($shared_strings[$index])) {
                                $value = $shared_strings[$index];
                            }
                        }
                    }

                    $row_data[$col_index] = $value;
                    $col_index++;
                }

                // 如果行不为空，添加到数据中
                if (!empty($row_data)) {
                    $data[$row_index] = $row_data;
                    $row_index++;
                }
            }

            return $data;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 删除操作
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('缺少参数');

        $ids = is_array($ids) ? $ids : [$ids];

        $r = SettlementRecordModel::where('id', 'in', $ids)->delete();
        if ($r) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 同意结算到钱包
     */
    public function agree($ids = null)
    {
        if ($ids === null) {
            $this->error('参数错误');
        }

        $ids = is_array($ids) ? $ids : [$ids];

        // 先获取需要处理的记录信息
        $records = SettlementRecordModel::where('id', 'in', $ids)
            ->where('is_wallet', 0)
            ->field('id, gid, promotion_name, money')
            ->select();

        if ($records->isEmpty()) {
            $this->error('没有需要处理的结算记录或记录已被处理');
        }

        // 更新结算状态
        $updateResult = SettlementRecordModel::where('id', 'in', $ids)
            ->where('is_wallet', 0)
            ->update(['is_wallet' => 1]);

        if ($updateResult === 0) {
            $this->error('更新结算状态失败');
        }

        // 创建财务交易记录
        foreach ($records as $record) {
            if (empty($record['gid'])) {
                continue; // 跳过没有GID的记录
            }

            // 查询回填记录获取用户ID
            $backfillRecord = Db::connect('translate')
                ->table('ts_backfill_record')
                ->where('video_gid', $record['gid'])
                ->order('id desc')
                ->find();

            if ($backfillRecord && !empty($backfillRecord['user_id'])) {
                $transactionData = [
                    'user_id' => $backfillRecord['user_id'],
                    'money' => $record['money'],
                    'title' => $record['promotion_name'],
                    'transaction_type' => '推广结算',
                    'time' => date('Y-m-d H:i:s')
                ];

                FinancialTransactionsModel::insertGetId($transactionData);
            }
        }

        $this->success('状态更新成功', 'index');
    }


}
