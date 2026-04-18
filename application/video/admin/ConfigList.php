<?php
// 配置列表
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\admin\BaseAdmin;
use app\video\model\ConfigListModel;

class ConfigList extends Admin {
    
    public function index() 
    {
        $map = $this->getMap();
        // 使用动态指定的数据库连接进行查询
        $data_list = ConfigListModel::where($map)
        ->order('id desc')
        ->paginate();

        cookie('ts_config_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/ConfigListModel', 2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['config_key', '配置键'],
                ['config_value', '配置值', 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['description', '描述', 'callback', function($source_text) {
                    return mb_strimwidth($source_text, 0, 20, '...');
                }],
                ['create_time', '创建时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([  
                ['text', 'config_key', '配置键'],
                ['daterange', 'create_time', '创建时间', '', '', ['format' => 'YYYY-MM-DD']],
            ])
            ->addTopButton('add', ['title'=>'添加']) // 添加顶部按钮
            ->addTopButton('delete', ['title'=>'删除']) // 批量添加顶部按钮
            ->addRightButton('edit', ['title'=>'编辑']) // 添加右侧编辑按钮
            ->addRightButton('delete', ['title'=>'删除']) // 添加右侧删除按钮
            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
            ->fetch(); // 渲染页面
    }
    
    /**
     * 添加配置
     * @return mixed
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            $validate = validate('ConfigList');
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            // 添加数据
            $result = ConfigListModel::create($data);
            if ($result) {
                return $this->success('添加成功', 'index');
            } else {
                return $this->error('添加失败');
            }
        }
        
        return ZBuilder::make('form')
            ->setPageTitle('添加配置')
            ->addFormItems([
                ['text', 'config_key', '配置键', '请输入配置键'],
                ['text', 'config_value', '配置值', '请输入配置值'],
                ['textarea', 'description', '描述', '请输入配置描述'],
            ])
            ->fetch();
    }
    
    /**
     * 编辑配置
     * @param int $id 记录ID
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            return $this->error('参数错误');
        }
        
        // 获取配置记录信息
        $config = ConfigListModel::get($id);
        if (!$config) {
            return $this->error('配置记录不存在');
        }
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            // 验证数据
            $validate = validate('ConfigList');
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            
            // 更新数据
            $result = $config->save($data);
            if ($result) {
                return $this->success('更新成功', 'index');
            } else {
                return $this->error('更新失败');
            }
        }
        
        return ZBuilder::make('form')
            ->setPageTitle('编辑配置')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'config_key', '配置键', '请输入配置键'],
                ['text', 'config_value', '配置值', '请输入配置值'],
                ['textarea', 'description', '描述', '请输入配置描述'],
            ])
            ->setFormData($config)
            ->fetch();
    }
    
    /**
     * 删除配置
     * @param array $ids 记录ID
     * @return mixed
     */
    public function delete($ids = [])
    {
        if (empty($ids)) {
            return $this->error('请选择要删除的记录');
        }
        
        // 删除数据
        $result = ConfigListModel::destroy($ids);
        if ($result) {
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }
} 