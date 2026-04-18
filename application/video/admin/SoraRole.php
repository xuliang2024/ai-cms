<?php
// sora2 角色管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SoraRole extends Admin {
	
	public function index()
	{
        $map = $this->getMap();
        $data_list = Db::connect('translate')->table('ts_sora_role')->where($map)
            ->order('id desc')
            ->paginate();

		cookie('ts_sora_role', $map);

		// 分类选项
        $categories = Db::connect('translate')->table('ts_sora_role_category')->order('sort desc, id desc')->column('name','id');

		return ZBuilder::make('table')
            ->setTableName('video/SoraRoleModel', 2)
			->addColumns([
				['id', 'ID'],
				['user_id', '创建者用户ID'],
				['category_id', '风格分类', 'select', $categories],
				['name', '角色名', 'text.edit'],
				['pen_name', '角色笔名', 'text.edit'],
				['reference_image', '参考图URL', 'callback', function($v){ return mb_strimwidth((string)$v, 0, 30, '...'); }],
				['reference_video', '参考视频URL'],
				['is_public', '是否公开', 'switch'],
				['created_at', '创建时间'],
				['updated_at', '更新时间'],
				['right_button', '操作', 'btn']
			])
			->setSearchArea([
				['select', 'category_id', '风格分类', '', '', $categories],
				['text', 'name', '角色名'],
				['text', 'pen_name', '角色笔名'],
				['select', 'is_public', '是否公开', '', '', [0=>'否',1=>'是']],
				['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']]
			])
			->addTopButton('add', ['title' => '新增角色'])
			->addTopButton('delete', ['title' => '删除角色'])
			->addRightButtons(['edit','delete'])
			->setRowList($data_list)
			->setHeight('auto')
			->fetch();
	}

	public function add()
	{
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = Db::connect('translate')->table('ts_sora_role')->insert($data);
            if ($res) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $categories = Db::connect('translate')->table('ts_sora_role_category')->order('sort desc, id desc')->column('name','id');

		return ZBuilder::make('form')
			->setPageTitle('新增角色')
			->addFormItems([
				['number', 'user_id', '创建者用户ID', '', 0, 'required'],
				['select', 'category_id', '风格分类', '', $categories, 0],
				['text', 'name', '角色名', '', '', 'required'],
				['text', 'pen_name', '角色笔名'],
				['textarea', 'reference_image', '参考图URL'],
				['textarea', 'reference_video', '参考视频URL'],
				['select', 'is_public', '是否公开', '', [0=>'否',1=>'是'], 1],
			])
			->fetch();
	}

	public function edit($id = null)
	{
		if ($id === null) $this->error('缺少参数');

        $info = Db::connect('translate')->table('ts_sora_role')->where('id', $id)->find();
		if (!$info) $this->error('记录不存在');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = Db::connect('translate')->table('ts_sora_role')->where('id', $id)->update($data);
            if ($res !== false) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

		$categories = Db::connect('translate')->table('ts_sora_role_category')->order('sort desc, id desc')->column('name','id');

		return ZBuilder::make('form')
			->setPageTitle('编辑角色')
			->addFormItems([
				['hidden', 'id'],
				['number', 'user_id', '创建者用户ID'],
				['select', 'category_id', '风格分类', '', $categories],
				['text', 'name', '角色名'],
				['text', 'pen_name', '角色笔名'],
				['textarea', 'reference_image', '参考图URL'],
				['textarea', 'reference_video', '参考视频URL'],
				['select', 'is_public', '是否公开', '', [0=>'否',1=>'是']],
			])
			->setFormData($info)
			->fetch();
	}

	public function delete($ids = null)
	{
		$ids = $ids ?: $this->request->param('ids', $this->request->param('id'));
		if (empty($ids)) $this->error('缺少参数');

		if (!is_array($ids)) {
			$ids = explode(',', (string) $ids);
		}

		$res = Db::connect('translate')->table('ts_sora_role')->where('id', 'in', $ids)->delete();
		if ($res !== false) {
			$this->success('删除成功', url('index'));
		} else {
			$this->error('删除失败');
		}
	}

	// 覆盖快速编辑，直接走 translate 连接，修复行内开关/文本编辑失败
	public function quickEdit($record = [])
	{
		$field    = input('post.name', '');
		$value    = input('post.value', '');
		$type     = input('post.type', '');
		$id       = input('post.pk', '');

		if ($field === '' || $id === '') $this->error('缺少参数');

		// 处理开关类型
		if ($type === 'switch') {
			$value = $value === 'true' ? 1 : 0;
		}

		$res = Db::connect('translate')->table('ts_sora_role')->where('id', intval($id))->setField($field, $value);
		if ($res !== false) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
}


