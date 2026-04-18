<?php
// 课程列表管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\CourseListModel;

class CourseList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用数据库连接进行查询
        $data_list = CourseListModel::where($map)
        ->order('created_at desc')
        ->paginate();

        cookie('ts_course_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/CourseListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['user_id', '创建者ID', 'text.edit'],
                ['title', '课程标题', 'text.edit'],
                ['subtitle', '课程副标题', 'text.edit'],
                // ['description', '课程描述', 'textarea.edit'],
                ['cover_image', '封面图片', 'img_url'],
                ['category_id', '分类ID', 'text.edit'],
                ['price', '课程价格', 'text.edit'],
                ['status', '状态', 'select', [0 => '草稿', 1 => '已发布', 2 => '下架']],
                ['view_count', '浏览次数', 'number'],
                ['vip_level', '会员等级', 'number'],
                ['purchase_count', '购买次数', 'number'],
                ['is_recommended', '是否推荐', 'switch'],
                ['tags', '标签', 'text.edit'],
                ['sort_order', '排序权重', 'number'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'title', '课程标题'],
                ['select', 'status', '状态', '', [0 => '草稿', 1 => '已发布', 2 => '下架']],
                ['text', 'user_id', '创建者ID']
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
            
            $r = CourseListModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
                  
        // 显示添加页面
        return ZBuilder::make('form')
            ->addOssImage('cover_image', '封面图片', '')
            ->addCkeditor('description', '课程描述')
            ->addFormItems([
                ['text', 'title', '课程标题', '请输入课程标题', '', 'required'],
                ['text', 'subtitle', '课程副标题'],
                // ['textarea', 'description', '课程描述'],
                ['number', 'category_id', '分类ID', '请选择课程分类'],
                ['text', 'price', '课程价格', '请输入课程价格，0为免费'],
                ['select', 'status', '状态', '', [0 => '草稿', 1 => '已发布', 2 => '下架'], 0],
                ['number', 'vip_level', '会员等级', '0表示无需会员'],
                ['switch', 'is_recommended', '是否推荐', '', ['0' => '否', '1' => '是'], 0],
                ['text', 'tags', '标签', '多个标签请用逗号分隔'],
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

            $r = CourseListModel::where('id', $id)->update($data);
            if ($r) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = CourseListModel::where('id', $id)->find();

        return ZBuilder::make('form')
        ->addOssImage('cover_image', '封面图片', '')
        ->addCkeditor('description', '课程描述')
            ->addFormItems([
                ['text', 'title', '课程标题', '请输入课程标题', '', 'required'],
                ['text', 'subtitle', '课程副标题'],
           
                
                ['number', 'category_id', '分类ID', '请选择课程分类'],
                ['text', 'price', '课程价格', '请输入课程价格，0为免费'],
                ['select', 'status', '状态', '', [0 => '草稿', 1 => '已发布', 2 => '下架']],
                ['number', 'view_count', '浏览次数'],
                ['number', 'vip_level', '会员等级', '0表示无需会员'],
                ['number', 'purchase_count', '购买次数'],
                ['switch', 'is_recommended', '是否推荐', '', ['0' => '否', '1' => '是']],
                ['text', 'tags', '标签', '多个标签请用逗号分隔'],
                ['number', 'sort_order', '排序权重', '数值越大排序越靠前']
                
            ])
            // ->addUeditor('description', '课程描述')
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $result = CourseListModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
} 