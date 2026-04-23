<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;

class Search extends Controller
{
    // 搜索结果页
    public function index()
    {
        $keyword = input('keyword', '', 'trim');

        if (empty($keyword)) {
            $this->assign('keyword', '');
            $this->assign('list', []);
            $this->assign('count', 0);
        } else {
            $articleModel = new ArticleModel();
            $list = $articleModel->search($keyword, 20);

            $this->assign('keyword', $keyword);
            $this->assign('list', $list);
            $this->assign('count', $list->total());
        }

        // 获取所有分类
        $categories = CategoryModel::getTree(0);
        $this->assign('categories', $categories);

        // 获取热门文章
        $hotArticles = ArticleModel::getHot(10);
        $this->assign('hotArticles', $hotArticles);

        return $this->fetch();
    }
}
