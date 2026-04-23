<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\Comment as CommentModel;
use think\Session;

class Comment extends Controller
{
    // 添加评论
    public function add()
    {
        if (!request()->isAjax()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        // 检查登录
        $userId = Session::get('user_id');
        if (!$userId) {
            return json(['code' => 2, 'msg' => '请先登录']);
        }

        $articleId = input('post.article_id', 0, 'intval');
        $content = input('post.content', '', 'trim');
        $parentId = input('post.parent_id', 0, 'intval');

        if ($articleId <= 0) {
            return json(['code' => 0, 'msg' => '文章ID无效']);
        }

        if (empty($content)) {
            return json(['code' => 0, 'msg' => '评论内容不能为空']);
        }

        if (mb_strlen($content) > 500) {
            return json(['code' => 0, 'msg' => '评论内容不能超过500字']);
        }

        try {
            $comment = CommentModel::addComment($articleId, $userId, $content, $parentId);

            return json([
                'code' => 1,
                'msg' => '评论成功',
                'data' => [
                    'id' => $comment['id'],
                    'content' => $comment['content'],
                    'user_nickname' => Session::get('nickname'),
                    'create_time' => time(),  // 返回整数时间戳
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '评论失败']);
        }
    }

    // 获取评论列表
    public function lists()
    {
        $articleId = input('get.article_id', 0, 'intval');

        if ($articleId <= 0) {
            return json(['code' => 0, 'msg' => '文章ID无效']);
        }

        $comments = CommentModel::getArticleComments($articleId, 50);

        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $comments
        ]);
    }
}
