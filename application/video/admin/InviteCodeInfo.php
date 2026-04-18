<?php
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class InviteCodeInfo extends Admin {

    public function index() 
    {
        $order = $this->getOrder('id desc');
        $map = $this->getMap();
        
        $data_list = DB::connect('translate')->table('ts_invite_code_info')->where($map)
        ->order($order)
        ->paginate();

        cookie('ts_invite_code_info', $map);
        $contro_top_btn = ['add'];
        $contro_right_btn = ['edit', 'delete'];
        return ZBuilder::make('table')
            ->setTableName('ts_invite_code_info') // 设置数据表名
            ->setPrimaryKey('id') // 设置主键
            ->addColumns([
                    ['id', 'ID'],
                    ['invite_code', '邀请码'],
                    ['user_id', '用户ID'],
                    ['cnt', '使用次数', 'callback', function($value) {
                        return '<span class="label label-info">' . $value . '</span>';
                    }],
                    ['time', '创建时间'],
                    ['update_time', '更新时间'],
                    ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButtons($contro_top_btn)
            ->addTopButton('custom', [
                'title' => '批量生成',
                'icon' => 'fa fa-plus-circle',
                'class' => 'btn btn-success',
                'href' => url('generateCode')
            ])
            ->addRightButtons($contro_right_btn)
            ->setSearchArea([
                ['text', 'invite_code', '邀请码'],
                ['text', 'user_id', '用户ID'],
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
            $data['time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            
            // 检查邀请码是否已存在
            $exists = DB::connect('translate')->table('ts_invite_code_info')
                ->where('invite_code', $data['invite_code'])
                ->find();
            if ($exists) {
                $this->error('邀请码已存在，请更换');
            }
            
            $r = DB::connect('translate')->table('ts_invite_code_info')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'invite_code', '邀请码', '必填，最长20个字符', '', 'required maxlength="20"'],
                ['number', 'user_id', '用户ID', '默认为0', 0],
                ['number', 'cnt', '使用次数', '默认为0', 0],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['update_time'] = date('Y-m-d H:i:s');
            
            // 检查邀请码是否已存在（排除当前记录）
            $exists = DB::connect('translate')->table('ts_invite_code_info')
                ->where('invite_code', $data['invite_code'])
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                $this->error('邀请码已存在，请更换');
            }
            
            $r = DB::connect('translate')->table('ts_invite_code_info')->where('id', $id)->update($data);
            if ($r !== false) {
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = DB::connect('translate')->table('ts_invite_code_info')->where('id', $id)->find();

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'invite_code', '邀请码', '必填，最长20个字符', '', 'required maxlength="20"'],
                ['number', 'user_id', '用户ID'],
                ['number', 'cnt', '使用次数'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除操作
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('缺少参数');
        
        $ids = is_array($ids) ? $ids : [$ids];
        
        $r = DB::connect('translate')->table('ts_invite_code_info')->where('id', 'in', $ids)->delete();
        if ($r) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 生成随机邀请码
     */
    public function generateCode()
    {
        if ($this->request->isPost()) {
            $count = $this->request->post('count', 1);
            if ($count < 1 || $count > 100) {
                $this->error('生成数量应在1-100之间');
            }
            
            $success_count = 0;
            for ($i = 0; $i < $count; $i++) {
                // 生成8位随机邀请码
                $invite_code = $this->createInviteCode();
                
                // 检查是否已存在
                $exists = DB::connect('translate')->table('ts_invite_code_info')
                    ->where('invite_code', $invite_code)
                    ->find();
                
                if (!$exists) {
                    $data = [
                        'invite_code' => $invite_code,
                        'user_id' => 0,
                        'cnt' => 0,
                        'time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    
                    $r = DB::connect('translate')->table('ts_invite_code_info')->insert($data);
                    if ($r) {
                        $success_count++;
                    }
                } else {
                    $i--; // 重新生成
                }
            }
            
            $this->success("成功生成 {$success_count} 个邀请码", 'index');
        }
        
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'count', '生成数量', '一次最多生成100个', 1, 'required min="1" max="100"'],
            ])
            ->fetch();
    }
    
    /**
     * 生成8位随机邀请码
     */
    private function createInviteCode() 
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }
} 