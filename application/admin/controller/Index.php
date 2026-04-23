<?php
namespace app\admin\controller;

use think\Session;
use app\common\model\Article as ArticleModel;
use app\common\model\User as UserModel;
use app\common\model\Comment as CommentModel;
use app\common\model\Category as CategoryModel;

class Index extends Base
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

    // 后台首页
    public function index()
    {
        // 统计信息
        $this->assign('articleCount', ArticleModel::count());
        $this->assign('userCount', UserModel::count());
        $this->assign('commentCount', CommentModel::count());
        $this->assign('categoryCount', CategoryModel::count());

        // 最新文章（预加载关联）
        $latestArticles = ArticleModel::with(['category', 'author'])->order('create_time desc')->limit(10)->select();
        $this->assign('latestArticles', $latestArticles);

        // 待审核评论
        $pendingComments = CommentModel::where('status', 1)->count();
        $this->assign('pendingComments', $pendingComments);

        return $this->fetch();
    }
}
