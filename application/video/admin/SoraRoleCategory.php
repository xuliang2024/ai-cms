<?php
// sora2 角色风格分类管理
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class SoraRoleCategory extends Admin {
	
	public function index()
	{
        $map = $this->getMap();
        $data_list = Db::connect('translate')->table('ts_sora_role_category')->where($map)
            ->order('sort desc, id desc')
            ->paginate();

		cookie('ts_sora_role_category', $map);

		return ZBuilder::make('table')
            ->setTableName('video/SoraRoleCategoryModel', 2)
			->addColumns([
				['id', 'ID'],
				['name', '风格名称', 'text.edit'],
				['sort', '排序', 'text.edit'],
				['status', '状态', 'switch'],
				['created_at', '创建时间'],
				['updated_at', '更新时间'],
				['right_button', '操作', 'btn']
			])
			->setSearchArea([
				['text', 'name', '风格名称'],
				['select', 'status', '状态', '', '', [0 => '禁用', 1 => '启用']],
				['daterange', 'created_at', '创建时间', '', '', ['format' => 'YYYY-MM-DD']]
			])
			->addTopButton('add', ['title' => '新增风格'])
			->addTopButton('delete', ['title' => '删除风格'])
			->addRightButtons(['edit','delete'])
			->setRowList($data_list)
			->setHeight('auto')
			->fetch();
	}

	public function add()
	{
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = Db::connect('translate')->table('ts_sora_role_category')->insert($data);
            if ($res) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

		return ZBuilder::make('form')
			->setPageTitle('新增风格')
			->addFormItems([
				['text', 'name', '风格名称', '', '', 'required'],
				['number', 'sort', '排序', '', 0],
				['select', 'status', '状态', '', [0 => '禁用', 1 => '启用'], 1],
			])
			->fetch();
	}

	public function edit($id = null)
	{
		if ($id === null) $this->error('缺少参数');

        $info = Db::connect('translate')->table('ts_sora_role_category')->where('id', $id)->find();
		if (!$info) $this->error('记录不存在');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = Db::connect('translate')->table('ts_sora_role_category')->where('id', $id)->update($data);
            if ($res !== false) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

		return ZBuilder::make('form')
			->setPageTitle('编辑风格')
			->addFormItems([
				['hidden', 'id'],
				['text', 'name', '风格名称', '', '', 'required'],
				['number', 'sort', '排序'],
				['select', 'status', '状态', '', [0 => '禁用', 1 => '启用']]
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

		// 仅删除分类
		$res = Db::connect('translate')->table('ts_sora_role_category')->where('id', 'in', $ids)->delete();
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

		if ($type === 'switch') {
			$value = $value === 'true' ? 1 : 0;
		}

		$res = Db::connect('translate')->table('ts_sora_role_category')->where('id', intval($id))->setField($field, $value);
		if ($res !== false) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
}


