<?php
namespace app\admin\controller;

use think\Controller;
use app\common\model\User as UserModel;

class User extends Base
{
    protected $beforeActionList = [
        'checkLogin' => ['']
    ];

    protected function checkLogin()
    {
        if (!\think\Session::get('admin_id')) {
            $this->redirect('/admin/login');
        }
    }

    // 用户列表
    public function index()
    {
        $keyword = input('keyword', '', 'trim');
        $isAdmin = input('is_admin', '');

        $where = [];
        if ($keyword) {
            $where['username|nickname|email|phone'] = ['like', "%{$keyword}%"];
        }
        if ($isAdmin !== '') {
            $where['is_admin'] = $isAdmin;
        }

        $list = UserModel::where($where)
            ->order('id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('is_admin', $isAdmin);

        return $this->fetch();
    }

    // 添加用户
    public function add()
    {
        if (request()->isPost()) {
            $data = [
                'username' => input('post.username', '', 'trim'),
                'password' => input('post.password', ''),
                'nickname' => input('post.nickname', '', 'trim'),
                'email' => input('post.email', '', 'trim'),
                'phone' => input('post.phone', '', 'trim'),
                'status' => input('post.status', 1, 'intval'),
                'is_admin' => input('post.is_admin', 0, 'intval'),
            ];

            if (empty($data['username']) || empty($data['password'])) {
                return json(['code' => 0, 'msg' => '用户名和密码不能为空']);
            }

            if (strlen($data['password']) < 6) {
                return json(['code' => 0, 'msg' => '密码长度不能少于6位']);
            }

            // 检查用户名是否已存在
            if (UserModel::where('username', $data['username'])->find()) {
                return json(['code' => 0, 'msg' => '用户名已存在']);
            }

            // 空字符串转为 NULL，避免唯一键冲突
            if ($data['email'] === '') {
                $data['email'] = null;
            }
            if ($data['phone'] === '') {
                $data['phone'] = null;
            }

            $result = UserModel::create($data);

            if ($result) {
                return json(['code' => 1, 'msg' => '添加成功']);
            } else {
                return json(['code' => 0, 'msg' => '添加失败']);
            }
        }

        return $this->fetch();
    }

    // 编辑用户
    public function edit()
    {
        $id = request()->param('id', 0, 'intval');

        if (request()->isPost()) {
            $data = [
                'nickname' => input('post.nickname', '', 'trim'),
                'email' => input('post.email', '', 'trim'),
                'phone' => input('post.phone', '', 'trim'),
                'status' => input('post.status', 1, 'intval'),
                'is_admin' => input('post.is_admin', 0, 'intval'),
            ];

            // 空字符串转为 NULL，避免唯一键冲突
            if ($data['email'] === '') {
                $data['email'] = null;
            }
            if ($data['phone'] === '') {
                $data['phone'] = null;
            }

            $password = input('post.password', '', '');
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    return json(['code' => 0, 'msg' => '密码长度不能少于6位']);
                }
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $result = \think\Db::name('users')->where('id', $id)->update($data);

            if ($result !== false) {
                return json(['code' => 1, 'msg' => '更新成功']);
            } else {
                return json(['code' => 0, 'msg' => '更新失败']);
            }
        }

        $user = UserModel::get($id);
        if (!$user) {
            $this->error('用户不存在');
        }

        $this->assign('user', $user);
        return $this->fetch();
    }

    // 删除用户
    public function delete()
    {
        $id = input('post.id', 0, 'intval');
        if (!$id) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 不能删除自己
        if ($id == \think\Session::get('admin_id')) {
            return json(['code' => 0, 'msg' => '不能删除自己']);
        }

        $result = UserModel::destroy($id);

        if ($result) {
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }

    // 修改状态
    public function setStatus()
    {
        $id = input('post.id', 0, 'intval');
        $status = input('post.status', 1, 'intval');

        $result = UserModel::where('id', $id)->update(['status' => $status]);

        if ($result !== false) {
            return json(['code' => 1, 'msg' => '更新成功']);
        } else {
            return json(['code' => 0, 'msg' => '更新失败']);
        }
    }
}
