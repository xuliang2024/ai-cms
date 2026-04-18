<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use app\video\model\JimengApiTokenModel;

class JimengApiToken extends Admin
{
    public function index()
    {
        $map = $this->getMap();
        $data_list = JimengApiTokenModel::where($map)
            ->order('id desc')
            ->paginate();

        cookie('ts_jimeng_api_token', $map);

        return ZBuilder::make('table')
            ->setPageTitle('即梦 API Token 账号池')
            ->setTableName('video/JimengApiTokenModel', 2)
            ->addColumns([
                ['id', 'ID'],
                ['name', '账号标识', 'text.edit'],
                ['token', 'Token', 'callback', function ($value) {
                    $short = mb_substr($value, 0, 8) . '****' . mb_substr($value, -6);
                    return "<span title='{$value}' style='cursor:pointer;'>{$short}</span>";
                }],
                ['region', '区域', 'text.edit'],
                ['balance', '积分余额', 'text.edit'],
                ['use_cnt', '使用次数'],
                ['error_cnt', '错误次数', 'callback', function ($value) {
                    $color = $value > 0 ? '#f56c6c' : '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['active_tasks', '活跃任务', 'callback', function ($value) {
                    $color = $value > 0 ? '#409eff' : '#909399';
                    return "<span style='color:{$color};font-weight:bold;'>{$value}</span>";
                }],
                ['max_concurrency', '最大并发', 'text.edit'],
                ['status', '状态', 'switch'],
                ['created_at', '创建时间'],
                ['updated_at', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->setSearchArea([
                ['text', 'name', '账号标识'],
                ['text', 'token', 'Token'],
                ['select', 'region', '区域', '', ['cn' => 'cn', 'us' => 'us', 'hk' => 'hk', 'jp' => 'jp', 'sg' => 'sg']],
                ['select', 'status', '状态', '', [1 => '正常', 0 => '禁用']],
            ])
            ->setRowList($data_list)
            ->setHeight('auto')
            ->addTopButton('add', ['title' => '新增'])
            ->addTopButton('delete', ['title' => '删除'])
            ->addRightButtons(['edit', 'delete'])
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            if (JimengApiTokenModel::where('token', $data['token'])->find()) {
                $this->error('该 Token 已存在');
            }

            $r = JimengApiTokenModel::create($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '账号标识', '账号名称或备注', '', 'required'],
                ['textarea', 'token', 'Token', '即梦 session token', '', 'required'],
                ['select', 'region', '区域', '', ['cn' => 'cn', 'us' => 'us', 'hk' => 'hk', 'jp' => 'jp', 'sg' => 'sg'], 'cn'],
                ['number', 'balance', '积分余额', '', 0],
                ['number', 'max_concurrency', '最大并发', '单 token 最大并发数', 3],
                ['select', 'status', '状态', '', [1 => '正常', 0 => '禁用'], 1],
                ['textarea', 'remark', '备注', '可选备注信息'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['updated_at'] = date('Y-m-d H:i:s');

            $existing = JimengApiTokenModel::where('token', $data['token'])->where('id', '<>', $id)->find();
            if ($existing) {
                $this->error('该 Token 已被其他账号使用');
            }

            $r = JimengApiTokenModel::where('id', $id)->update($data);
            if ($r !== false) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = JimengApiTokenModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '账号标识', '账号名称或备注', '', 'required'],
                ['textarea', 'token', 'Token', '即梦 session token', '', 'required'],
                ['select', 'region', '区域', '', ['cn' => 'cn', 'us' => 'us', 'hk' => 'hk', 'jp' => 'jp', 'sg' => 'sg']],
                ['number', 'balance', '积分余额'],
                ['number', 'use_cnt', '使用次数'],
                ['number', 'error_cnt', '错误次数'],
                ['number', 'active_tasks', '活跃任务数'],
                ['number', 'max_concurrency', '最大并发', '单 token 最大并发数'],
                ['select', 'status', '状态', '', [1 => '正常', 0 => '禁用']],
                ['textarea', 'remark', '备注'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $result = JimengApiTokenModel::whereIn('id', $ids)->delete();
        if ($result) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}
