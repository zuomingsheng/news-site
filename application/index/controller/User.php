<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\User as UserModel;
use think\Session;

class User extends Controller
{
    // 登录页面
    public function login()
    {
        
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

        $user = UserModel::where('username', $username)
            ->whereOr('email', $username)
            ->whereOr('phone', $username)
            ->find();

        if (!$user) {
            $this->error('用户不存在');
        }

        if ($user['status'] != 1) {
            $this->error('账号已被禁用');
        }

        if (!password_verify($password, $user['password'])) {
            $this->error('密码错误');
        }

        // 保存登录状态
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('nickname', $user['nickname']);
        Session::set('is_admin', $user['is_admin']);

        $this->success('登录成功', '/');
    }

    // 注册页面
    public function register()
    {
        return $this->fetch();
    }

    // 处理注册
    public function doRegister()
    {
        if (!request()->isPost()) {
            $this->error('请求方式错误');
        }

        $username = input('post.username', '', 'trim');
        $password = input('post.password', '', '');
        $email = input('post.email', '', 'trim');
        $phone = input('post.phone', '', 'trim');

        if (empty($username) || empty($password)) {
            $this->error('用户名和密码不能为空');
        }

        if (strlen($password) < 6) {
            $this->error('密码长度不能少于6位');
        }

        // 检查用户名是否已存在
        if (UserModel::where('username', $username)->find()) {
            $this->error('用户名已存在');
        }

        // 检查邮箱是否已存在
        if (!empty($email) && UserModel::where('email', $email)->find()) {
            $this->error('邮箱已被注册');
        }

        // 检查手机号是否已存在
        if (!empty($phone) && UserModel::where('phone', $phone)->find()) {
            $this->error('手机号已被注册');
        }

        $user = UserModel::create([
            'username' => $username,
            'password' => $password,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'nickname' => $username,
            'status' => 1,
        ]);

        // 自动登录
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('nickname', $user['nickname']);
        Session::set('is_admin', 0);

        $this->success('注册成功', '/');
    }

    // 退出登录
    public function logout()
    {
        Session::clear();
        $this->success('已退出登录', '/');
    }

    // 个人中心
    public function center()
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            $this->error('请先登录', url('user/login'));
        }

        $user = UserModel::get($userId);
        $this->assign('user', $user);

        return $this->fetch();
    }

    // 检查是否登录（API）
    public function checkLogin()
    {
        $userId = Session::get('user_id');
        if ($userId) {
            return json([
                'code' => 1,
                'msg' => '已登录',
                'data' => [
                    'user_id' => $userId,
                    'username' => Session::get('username'),
                    'nickname' => Session::get('nickname'),
                ]
            ]);
        }
        return json(['code' => 0, 'msg' => '未登录']);
    }
}
