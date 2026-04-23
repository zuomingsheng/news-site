<?php
namespace app\admin\controller;

use think\Controller;
use app\common\model\Category as CategoryModel;

class Category extends Base
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

    // 分类列表
    public function index()
    {
        $list = CategoryModel::order('sort asc, id asc')->select();

        // 预查询每分类的文章数
        $articleModel = model('Article');
        foreach ($list as $item) {
            $item['article_count'] = $articleModel->where('category_id', $item['id'])->count();
        }

        // 转换为树形结构
        $tree = $this->buildTree($list);
        $this->assign('tree', $tree);

        return $this->fetch();
    }

    // 构建树形结构
    protected function buildTree($list, $parentId = 0)
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($list, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    // 添加分类
    public function add()
    {
        if (request()->isPost()) {
            $data = [
                'name' => input('post.name', '', 'trim'),
                'parent_id' => input('post.parent_id', 0, 'intval'),
                'sort' => input('post.sort', 0, 'intval'),
                'status' => input('post.status', 1, 'intval'),
            ];

            if (empty($data['name'])) {
                return json(['code' => 0, 'msg' => '分类名称不能为空']);
            }

            $result = CategoryModel::create($data);

            if ($result) {
                return json(['code' => 1, 'msg' => '添加成功']);
            } else {
                return json(['code' => 0, 'msg' => '添加失败']);
            }
        }

        $categories = CategoryModel::getTree(0);
        $this->assign('categories', $categories);
        return $this->fetch();
    }

    // 编辑分类
    public function edit()
    {
        $id = request()->param('id', 0, 'intval');

        if (request()->isPost()) {
            $data = [
                'name' => input('post.name', '', 'trim'),
                'parent_id' => input('post.parent_id', 0, 'intval'),
                'sort' => input('post.sort', 0, 'intval'),
                'status' => input('post.status', 1, 'intval'),
            ];

            if (empty($data['name'])) {
                return json(['code' => 0, 'msg' => '分类名称不能为空']);
            }

            $result = CategoryModel::where('id', $id)->update($data);

            if ($result !== false) {
                return json(['code' => 1, 'msg' => '更新成功']);
            } else {
                return json(['code' => 0, 'msg' => '更新失败']);
            }
        }

        $category = CategoryModel::get($id);
        if (!$category) {
            $this->error('分类不存在');
        }

        $categories = CategoryModel::getTree(0, $id);
        $this->assign('category', $category);
        $this->assign('categories', $categories);
        return $this->fetch();
    }

    // 删除分类
    public function delete()
    {
        $id = input('post.id', 0, 'intval');
        if (!$id) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 检查是否有子分类
        $hasChildren = CategoryModel::where('parent_id', $id)->count();
        if ($hasChildren > 0) {
            return json(['code' => 0, 'msg' => '请先删除子分类']);
        }

        // 检查是否有文章
        $articleCount = model('Article')->where('category_id', $id)->count();
        if ($articleCount > 0) {
            return json(['code' => 0, 'msg' => '该分类下有文章，无法删除']);
        }

        $result = CategoryModel::destroy($id);

        if ($result) {
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }
}
