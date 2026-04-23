<?php
namespace app\admin\controller;

use think\Controller;
use think\Session;

class Base extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
        $adminId = Session::get('admin_id');
        if ($adminId) {
            $user = \app\common\model\User::get($adminId);
            $this->assign('adminNickname', $user && $user->nickname ? $user->nickname : '管理员');
        }
    }
}
