<?php
// 课程章节视频管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CourseChapterVideoModel;
use app\video\model\CourseListModel;

class CourseChapterVideo extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = CourseChapterVideoModel::where($map)
        ->order('created_at desc ,course_id asc, chapter_index asc')
        ->paginate();

        cookie('ts_course_chapter_video', $map);
        
        // 获取所有课程ID和标题，用于显示课程名称而非ID
        $courses = CourseListModel::column('title', 'id');
        
        return ZBuilder::make('table')
            ->setTableName('video/CourseChapterVideoModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['course_id', '所属课程', 'select', $courses],
                ['chapter_index', '章节序号', 'number'],
                ['title', '章节标题', 'text.edit'],
                ['video_url', '视频URL', 'text.edit'],
                ['duration', '视频时长(秒)', 'number'],
                ['cover_image', '封面图片', 'img_url'],
                ['is_free', '是否免费试看', 'switch'],
                ['status', '状态', 'select', [0 => '未发布', 1 => '已发布']],
                ['sort_order', '排序权重', 'number'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'title', '章节标题'],
                ['select', 'course_id', '所属课程', '', $courses],
                ['select', 'status', '状态', '', [0 => '未发布', 1 => '已发布']]
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->addTopButton('add', ['title' => '新增']) // 添加新增按钮
            ->addTopButton('delete', ['title' => '删除']) // 添加删除按钮
            ->addRightButtons(['edit', 'delete']) // 添加编辑和删除按钮
            ->fetch(); // 渲染页面
    }

    public function add() 
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $r = CourseChapterVideoModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
        
        // 获取所有课程的列表
        $courses = CourseListModel::column('title', 'id');
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('cover_image', '封面图片', '')
            ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                ['text', 'video_url', 'oss视频链接', '同上视频链接，2选1用一个就行'],
                ['select', 'course_id', '所属课程', '请选择所属课程', $courses, '', 'required'],
                ['number', 'chapter_index', '章节序号', '请输入章节序号', '', 'required'],
                ['text', 'title', '章节标题', '请输入章节标题', '', 'required'],
                ['textarea', 'description', '章节描述'],
                ['number', 'duration', '视频时长(秒)', '请输入视频时长，单位为秒'],
                ['switch', 'is_free', '是否免费试看', '', ['0' => '否', '1' => '是'], 0],
                ['select', 'status', '状态', '', [0 => '未发布', 1 => '已发布'], 0],
                ['number', 'sort_order', '排序权重', '数值越大排序越靠前']
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
            $data['updated_at'] = date('Y-m-d H:i:s');

            $r = CourseChapterVideoModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CourseChapterVideoModel::where('id', $id)->find();
        
        // 获取所有课程的列表
        $courses = CourseListModel::column('title', 'id');

        return ZBuilder::make('form')
            ->addOssImage('cover_image', '封面图片', '')
            ->addOssVideo('video_url','视频链接','')
            ->addFormItems([
                ['text', 'video_url', 'oss视频链接', '同上视频链接，2选1用一个就行'],
                ['select', 'course_id', '所属课程', '请选择所属课程', $courses, '', 'required'],
                ['number', 'chapter_index', '章节序号', '请输入章节序号', '', 'required'],
                ['text', 'title', '章节标题', '请输入章节标题', '', 'required'],
                ['textarea', 'description', '章节描述'],
                ['number', 'duration', '视频时长(秒)', '请输入视频时长，单位为秒'],
                ['switch', 'is_free', '是否免费试看', '', ['0' => '否', '1' => '是']],
                ['select', 'status', '状态', '', [0 => '未发布', 1 => '已发布']],
                ['number', 'sort_order', '排序权重', '数值越大排序越靠前']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CourseChapterVideoModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 