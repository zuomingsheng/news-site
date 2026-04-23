<?php
namespace app\admin\controller;

use think\Controller;
use app\common\model\Config as ConfigModel;

class System extends Base
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

    // 系统设置
    public function index()
    {
        if (request()->isPost()) {
            $configs = input('post.configs/a', []);

            foreach ($configs as $name => $value) {
                ConfigModel::setValue($name, $value);
            }

            $this->success('保存成功');
        }

        $configs = ConfigModel::getAllByGroup();
        $this->assign('configs', $configs);
        return $this->fetch();
    }
}
