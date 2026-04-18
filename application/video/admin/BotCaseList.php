<?php
// 机器人案例列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\BotCaseListModel;

class BotCaseList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = BotCaseListModel::where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_bot_case', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BotCaseListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['bot_id', '机器人ID'],
                ['image_url', '图片', 'img_url'],
                ['video_url', '视频链接','image_video'],
                ['video_url', '视频链接','text.edit'],
                ['status', '状态', 'switch'],
                ['time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'bot_id', '机器人ID'],
                ['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']],
                ['daterange', 'time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addTopButton('add', ['title'=>'新增']) // 添加新增按钮
            ->addTopButton('extract', ['title'=>'提取视频中的图片', 'href'=>'https://www.coze.cn/store/agent/7494533486033371163?bot_id=true', 'target'=>'_blank']) // 添加提取视频图片按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
    
    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            // 添加创建时间
            $data['time'] = date('Y-m-d H:i:s');
            
            // 验证数据
            if (empty($data['bot_id'])) {
                $this->error('机器人ID不能为空');
            }
            
            $r = DB::connect('translate')->table('ts_bot_case')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('新增案例')
            ->addOssImage('image_url', '图片', '', '', '', '', '', ['size' => '50,50'])
            ->addOssVideo('video_url', '视频链接', '')
            ->addFormItems([
                ['text', 'bot_id', '机器人ID', '请输入机器人ID'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }
    
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证数据
            if (empty($data['bot_id'])) {
                $this->error('机器人ID不能为空');
            }

            // 更新数据
            $r = BotCaseListModel::where('id', $id)->update($data);
            
            if ($r !== false) { // ThinkPHP update 返回影响的行数，0 也可能是成功但无变化
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = BotCaseListModel::where('id', $id)->find();
        if (!$info) {
            $this->error('未找到记录');
        }

        // 使用ZBuilder构建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑案例') // 设置页面标题
            ->addFormItems([ // 添加表单项
                ['hidden', 'id'],
                ['text', 'bot_id', '机器人ID', '请输入机器人ID'],
                ['image', 'image_url', '图片'],
                ['text', 'video_url', '视频链接', '请输入视频链接'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}
