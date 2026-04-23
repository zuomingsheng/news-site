<?php
namespace app\common\model;

use think\Model;

class Comment extends Model
{
    protected $table = 'comments';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取格式化的创建时间
    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i', $value) : '';
    }

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '隐藏', 1 => '显示'];
        return isset($status[$data['status']]) ? $status[$data['status']] : '未知';
    }

    // 关联文章
    public function article()
    {
        return $this->belongsTo('Article', 'article_id');
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    // 关联父评论
    public function parent()
    {
        return $this->belongsTo('Comment', 'parent_id');
    }

    // 关联子评论（回复）
    public function children()
    {
        return $this->hasMany('Comment', 'parent_id');
    }

    // 获取文章的顶级评论
    public static function getArticleComments($articleId, $limit = 50)
    {
        return self::with(['user', 'children' => ['user']])
            ->where(['article_id' => $articleId, 'parent_id' => 0, 'status' => 1])
            ->order('create_time desc')
            ->limit($limit)
            ->select();
    }

    // 添加评论
    public static function addComment($articleId, $userId, $content, $parentId = 0)
    {
        $comment = self::create([
            'article_id' => $articleId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => $content,
            'status' => 1,
        ]);

        // 更新文章的评论数
        model('Article')->where('id', $articleId)->setInc('comments');

        return $comment;
    }
}
