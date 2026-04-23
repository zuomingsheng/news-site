<?php
namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;

class Upload extends Base
{
    protected $beforeActionList = [
        'checkLogin' => ['']
    ];

    protected function checkLogin()
    {
        if (!Session::get('admin_id')) {
            $this->redirect('/admin/login');
        }
    }

    // 图片上传
    public function image(Request $request)
    {
        if (!Session::get('admin_id')) {
            return json(['code' => 0, 'msg' => '未登录']);
        }

        $file = $request->file('file');
        if (!$file) {
            $file = $request->file('imgFile');
        }

        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择要上传的图片']);
        }

        // 验证图片
        $validate = [
            'size' => 1024 * 1024 * 5, // 5MB
            'ext'  => 'jpg,jpeg,png,gif,webp,bmp',
        ];

        $info = $file->validate($validate)->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $path = '/uploads/' . str_replace('\\', '/', $info->getSaveName());
            return json(['code' => 1, 'msg' => '上传成功', 'data' => ['url' => $path]]);
        } else {
            return json(['code' => 0, 'msg' => $file->getError()]);
        }
    }
}
