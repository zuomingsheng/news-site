<?php
namespace app\admin\controller;

use think\Controller;
use think\Session;

class Auth extends Controller
{
    protected $beforeActionList = [
        'checkLogin' => ['except' => 'login,doLogin']
    ];

    // 检查登录
    protected function checkLogin()
    {
        if (!Session::get('admin_id')) {
            $this->redirect('/admin/login');
        }
    }

    // 登录页面
    public function login()
    {
        if (request()->isPost()) {
            return $this->doLogin();
        }
        return $this->fetch();
    }

    // 处理登录
    public function doLogin()
    {
        if (!request()->isPost()) {
            $this->error('请求方式错误');
        }

        $username = input('post.username', '', 'trim');
        $password = input('post.password', '', '');

        if (empty($username) || empty($password)) {
            $this->error('用户名和密码不能为空');
        }

        $user = \app\common\model\User::where(function($query) use ($username) {
            $query->where('username', $username)
                ->whereOr('email', $username)
                ->whereOr('phone', $username);
        })->where('is_admin', 1)->find();

        if (!$user) {
            $this->error('管理员不存在');
        }

        if ($user['status'] != 1) {
            $this->error('账号已被禁用');
        }

        if (!password_verify($password, $user['password'])) {
            $this->error('密码错误');
        }

        // 保存登录状态
        Session::set('admin_id', $user['id']);
        Session::set('admin_name', $user['username']);
        Session::set('admin_nickname', $user['nickname']);

        $this->success('登录成功', '/admin');
    }

    // 退出登录
    public function logout()
    {
        Session::clear();
        $this->success('已退出登录', '/admin/login');
    }
}
