<?php
namespace app\admin\controller;

use think\Controller;
use app\common\model\Comment as CommentModel;

class Comment extends Base
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

    // 评论列表
    public function index()
    {
        $keyword = input('keyword', '', 'trim');
        $status = input('status', '');

        $where = [];
        if ($keyword) {
            $where['content'] = ['like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where['status'] = $status;
        }

        $list = CommentModel::with(['user', 'article'])
            ->where($where)
            ->order('id desc')
            ->paginate(15);

        $this->assign('list', $list);
        $this->assign('keyword', $keyword);
        $this->assign('status', $status);

        return $this->fetch();
    }

    // 审核评论
    public function setStatus()
    {
        $id = input('post.id', 0, 'intval');
        $status = input('post.status', 1, 'intval');

        $result = CommentModel::where('id', $id)->update(['status' => $status]);

        if ($result !== false) {
            return json(['code' => 1, 'msg' => '更新成功']);
        } else {
            return json(['code' => 0, 'msg' => '更新失败']);
        }
    }

    // 删除评论
    public function delete()
    {
        $id = request()->param('id', 0, 'intval');

        // 获取评论信息，用于更新文章评论数
        $comment = CommentModel::get($id);
        if ($comment) {
            model('Article')->where('id', $comment['article_id'])->setDec('comments');
        }

        $result = CommentModel::destroy($id);

        if ($result) {
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }
}
