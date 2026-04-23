<?php
namespace app\admin\controller;

use think\Controller;
use app\common\model\Banner as BannerModel;

class Banner extends Base
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

    // 轮播列表
    public function index()
    {
        $list = BannerModel::order('sort asc, id desc')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 添加轮播
    public function add()
    {
        if (request()->isPost()) {
            $data = [
                'title' => input('post.title', '', 'trim'),
                'image' => input('post.image', '', 'trim'),
                'url' => input('post.url', '', 'trim'),
                'sort' => input('post.sort', 0, 'intval'),
                'status' => input('post.status', 1, 'intval'),
            ];

            if (empty($data['image'])) {
                return json(['code' => 0, 'msg' => '请上传轮播图片']);
            }

            $result = BannerModel::create($data);

            if ($result) {
                return json(['code' => 1, 'msg' => '添加成功']);
            } else {
                return json(['code' => 0, 'msg' => '添加失败']);
            }
        }

        return $this->fetch();
    }

    // 编辑轮播
    public function edit()
    {
        $id = input('post.id', 0, 'intval');
        if (!$id) {
            $id = input('get.id', 0, 'intval');
        }

        if (request()->isPost()) {
            $data = [
                'title' => input('post.title', '', 'trim'),
                'image' => input('post.image', '', 'trim'),
                'url' => input('post.url', '', 'trim'),
                'sort' => input('post.sort', 0, 'intval'),
                'status' => input('post.status', 1, 'intval'),
            ];

            if (empty($data['image'])) {
                return json(['code' => 0, 'msg' => '请上传轮播图片']);
            }

            $result = BannerModel::where('id', $id)->update($data);

            if ($result !== false) {
                return json(['code' => 1, 'msg' => '更新成功']);
            } else {
                return json(['code' => 0, 'msg' => '更新失败']);
            }
        }

        $banner = BannerModel::get($id);
        if (!$banner) {
            $this->error('轮播不存在');
        }

        $this->assign('banner', $banner);
        return $this->fetch();
    }

    // 删除轮播
    public function delete()
    {
        $id = input('post.id', 0, 'intval');
        if (!$id) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        $result = BannerModel::destroy($id);

        if ($result) {
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }
}
