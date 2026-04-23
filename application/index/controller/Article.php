<?php
// 命名空间声明
namespace app\index\controller;

// 引入 ThinkPHP 控制器基类
use think\Controller;
// 引入文章模型
use app\common\model\Article as ArticleModel;
// 引入评论模型
use app\common\model\Comment as CommentModel;
// 引入分类模型
use app\common\model\Category as CategoryModel;

// 文章控制器类
define Article extends Controller
{
    // 文章详情
    // 文章详情方法
    public function detail($id)
    {
        // 将文章 ID 转为整数类型
        $id = intval($id);

        // 根据 ID 获取文章数据
        $article = ArticleModel::get($id);
        // 判断文章是否存在且状态为已发布
        if (!$article || $article['status'] != 1) {
            // 文章不存在或已下架时提示错误
            $this->error('文章不存在或已下架');
        }

        // 浏览量+1
        // 文章浏览量加 1
        $article->setInc('views');

        // 上一篇
        // 查找上一篇文章（ID 小于当前）
        $prev = ArticleModel::where('id', '<', $id)
            // 状态为已发布
            ->where('status', 1)
            // 按 ID 降序排列
            ->order('id desc')
            // 获取一条记录
            ->find();
        // 将上一篇数据赋值到模板
        $this->assign('prev', $prev);

        // 下一篇
        // 查找下一篇文章（ID 大于当前）
        $next = ArticleModel::where('id', '>', $id)
            // 状态为已发布
            ->where('status', 1)
            // 按 ID 升序排列
            ->order('id asc')
            // 获取一条记录
            ->find();
        // 将下一篇数据赋值到模板
        $this->assign('next', $next);

        // 相关文章（同分类）
        // 查找同分类下的相关文章
        $related = ArticleModel::where('category_id', $article['category_id'])
            // 排除当前文章
            ->where('id', '<>', $id)
            // 状态为已发布
            ->where('status', 1)
            // 限制返回 5 条
            ->limit(5)
            // 查询结果集
            ->select();
        // 将相关文章赋值到模板
        $this->assign('related', $related);

        // 文章评论
        // 获取文章评论列表（最多 50 条）
        $comments = CommentModel::getArticleComments($id, 50);
        // 将评论数据赋值到模板
        $this->assign('comments', $comments);
        // 统计评论数量并赋值到模板
        $this->assign('commentCount', count($comments));

        // 热门文章
        // 获取热门文章（10 条）
        $hotArticles = ArticleModel::getHot(10);
        // 将热门文章赋值到模板
        $this->assign('hotArticles', $hotArticles);

        // 将文章数据赋值到模板
        $this->assign('article', $article);

        // 渲染模板并返回
        return $this->fetch();
    }

    // 文章列表
    // 文章列表方法
    public function lists($cid = 0)
    {
        // 将分类 ID 转为整数类型
        $cid = intval($cid);

        // 实例化文章模型
        $articleModel = new ArticleModel();
        // 初始化查询条件数组
        $where = [];
        // 判断分类 ID 是否大于 0
        if ($cid > 0) {
            // 设置分类查询条件
            $where['category_id'] = $cid;
        }

        // 获取已发布文章列表（按置顶和时间排序，每页 15 条）
        $list = $articleModel->getPublishedList($where, 'is_top desc, publish_time desc', 15);
        // 将文章列表赋值到模板
        $this->assign('list', $list);
        // 将分类 ID 赋值到模板
        $this->assign('cid', $cid);

        // 获取所有分类
        // 获取分类树形结构
        $categories = CategoryModel::getTree(0);
        // 将分类数据赋值到模板
        $this->assign('categories', $categories);

        // 获取热门文章
        // 获取热门文章（10 条）
        $hotArticles = ArticleModel::getHot(10);
        // 将热门文章赋值到模板
        $this->assign('hotArticles', $hotArticles);

        // 渲染模板并返回
        return $this->fetch();
    }

    // 点赞
    // 点赞方法
    public function like($id)
    {
        // 判断是否为 AJAX 请求
        if (request()->isAjax()) {
            // 根据 ID 获取文章
            $article = ArticleModel::get($id);
            // 判断文章是否存在
            if (!$article) {
                // 返回文章不存在提示
                return json(['code' => 0, 'msg' => '文章不存在']);
            }

            // 文章点赞数加 1
            $article->setInc('likes');
            // 返回点赞成功结果
            return json(['code' => 1, 'msg' => '点赞成功', 'likes' => $article->likes]);
        }

        // 返回请求方式错误提示
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }
}
