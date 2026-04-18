<?php
// 官方消息通知
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\OfficialNotificationModel;

class OfficialNotification extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = OfficialNotificationModel::where($map)
        ->order('sort desc, create_time desc')
        ->paginate();

        cookie('ts_official_notification', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/OfficialNotificationModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '消息标题'],
                ['content', '消息内容' , 'callback', function($value = '') {
                    return $value ? mb_strimwidth($value, 0, 50, '...') : '';
                }],
                ['notification_type', '消息类型', 'callback', function($value = '') {
                    $types = [
                        1 => '重要通知',
                        2 => '版本更新', 
                        3 => '新功能发布',
                        4 => '系统维护'
                    ];
                    return isset($types[$value]) ? $types[$value] : '未知';
                }],
                ['status', '状态', 'callback', function($value = '') {
                    $statuses = [
                        0 => '<span class="label label-warning">草稿</span>',
                        1 => '<span class="label label-success">已发布</span>',
                        2 => '<span class="label label-danger">已撤回</span>'
                    ];
                    return isset($statuses[$value]) ? $statuses[$value] : '未知';
                }],
                ['is_top', '是否置顶', 'callback', function($value = '') {
                    return $value == 1 ? '<span class="label label-info">是</span>' : '<span class="label label-default">否</span>';
                }],
                ['sort', '排序权重','text.edit'],
                ['publish_time', '发布时间'],
                ['create_time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'title', '消息标题'],
                ['select', 'notification_type', '消息类型', '', [
                    1 => '重要通知',
                    2 => '版本更新', 
                    3 => '新功能发布',
                    4 => '系统维护'
                ]],
                ['select', 'status', '状态', '', [
                    0 => '草稿',
                    1 => '已发布',
                    2 => '已撤回'
                ]],
                ['select', 'is_top', '是否置顶', '', [
                    0 => '否',
                    1 => '是'
                ]],
                ['daterange', 'publish_time', '发布时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('add', ['title'=>'新增']) // 添加新增按钮
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }

    /**
     * 新增
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['create_time'] = time();
            $data['update_time'] = time();
            
            $r = DB::connect('translate')->table('ts_official_notification')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '消息标题', '', '', 'required'],
                ['select', 'notification_type', '消息类型', '', [
                    1 => '重要通知',
                    2 => '版本更新', 
                    3 => '新功能发布',
                    4 => '系统维护'
                ], 'required'],
                ['textarea', 'content', '消息内容', '', '', 'required'],
                ['select', 'status', '状态', '', [
                    0 => '草稿',
                    1 => '已发布',
                    2 => '已撤回'
                ], 'required'],
                ['radio', 'is_top', '是否置顶', '', [
                    0 => '否',
                    1 => '是'
                ]],
                ['number', 'sort', '排序权重', '数值越大越靠前'],
                ['datetime', 'publish_time', '发布时间'],
            ])
            ->fetch();
    }

    /**
     * 编辑
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['update_time'] = time();
            
            $r = DB::connect('translate')->table('ts_official_notification')->where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_official_notification')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '消息标题', '', '', 'required'],
                ['select', 'notification_type', '消息类型', '', [
                    1 => '重要通知',
                    2 => '版本更新', 
                    3 => '新功能发布',
                    4 => '系统维护'
                ], 'required'],
                ['textarea', 'content', '消息内容', '', '', 'required'],
                ['select', 'status', '状态', '', [
                    0 => '草稿',
                    1 => '已发布',
                    2 => '已撤回'
                ], 'required'],
                ['radio', 'is_top', '是否置顶', '', [
                    0 => '否',
                    1 => '是'
                ]],
                ['number', 'sort', '排序权重', '数值越大越靠前'],
                ['datetime', 'publish_time', '发布时间'],
            ])
            ->setFormData($info)
            ->fetch();
    }
    
    /**
     * 查看消息详情
     * @param int $id 记录ID
     * @return mixed
     */
    public function viewNotificationDetail($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取消息记录信息
        $notification = OfficialNotificationModel::get($id);
        if (!$notification) {
            return $this->error('消息记录不存在');
        }
        
        // 这里可以添加查看消息详情的逻辑
        // 例如记录浏览次数或者其他操作
        
        return $this->success('查询成功', '', $notification->toArray());
    }

    /**
     * 发布消息
     * @param int $id 记录ID
     * @return mixed
     */
    public function publish($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        $data = [
            'status' => 1,
            'publish_time' => time(),
            'update_time' => time()
        ];
        
        $r = DB::connect('translate')->table('ts_official_notification')->where('id', $id)->update($data);
        if ($r) {
            $this->success('发布成功');
        } else {
            $this->error('发布失败');
        }
    }

    /**
     * 撤回消息
     * @param int $id 记录ID
     * @return mixed
     */
    public function withdraw($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        $data = [
            'status' => 2,
            'update_time' => time()
        ];
        
        $r = DB::connect('translate')->table('ts_official_notification')->where('id', $id)->update($data);
        if ($r) {
            $this->success('撤回成功');
        } else {
            $this->error('撤回失败');
        }
    }
} 