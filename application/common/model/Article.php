<?php
namespace app\common\model;

use think\Model;

class Article extends Model
{
    protected $table = 'articles';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取封面图完整URL
    public function getCoverAttr($value)
    {
        if (empty($value)) {
            return '/static/images/default-cover.jpg';
        }
        if (strpos($value, 'http') === 0) {
            return $value;
        }
        if (strpos($value, '/uploads/') === 0) {
            return $value;
        }
        if (strpos($value, '/static/') === 0) {
            return $value;
        }
        return '/uploads/' . $value;
    }

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '草稿', 1 => '已发布', 2 => '已下线'];
        return isset($status[$data['status']]) ? $status[$data['status']] : '未知';
    }

    // 获取格式化发布时间
    public function getPublishTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i', $value) : '';
    }

    // 获取简短发布时间
    public function getShortPublishTimeAttr($value, $data)
    {
        $time = isset($data['publish_time']) ? $data['publish_time'] : 0;
        return $time ? date('Y-m-d', $time) : '';
    }

    // 关联分类
    public function category()
    {
        return $this->belongsTo('Category', 'category_id');
    }

    // 关联作者
    public function author()
    {
        return $this->belongsTo('User', 'author_id');
    }

    // 关联评论
    public function comments()
    {
        return $this->hasMany('Comment', 'article_id');
    }

    // 获取已发布的文章列表
    public function getPublishedList($where = [], $order = 'is_top desc, publish_time desc', $limit = 10)
    {
        $defaultWhere = ['status' => 1];
        $where = array_merge($defaultWhere, $where);

        return $this->with(['category', 'author'])
            ->where($where)
            ->order($order)
            ->paginate($limit);
    }

    // 获取推荐文章
    public static function getRecommend($limit = 5)
    {
        return self::with(['category', 'author'])
            ->where(['status' => 1, 'is_recommend' => 1])
            ->order('publish_time desc')
            ->limit($limit)
            ->select();
    }

    // 获取置顶文章
    public static function getTop($limit = 3)
    {
        return self::with(['category', 'author'])
            ->where(['status' => 1, 'is_top' => 1])
            ->order('publish_time desc')
            ->limit($limit)
            ->select();
    }

    // 获取热门文章
    public static function getHot($limit = 10)
    {
        return self::with(['category', 'author'])
            ->where(['status' => 1])
            ->order('views desc')
            ->limit($limit)
            ->select();
    }

    // 获取最新文章
    public static function getLatest($limit = 10)
    {
        return self::with(['category', 'author'])
            ->where(['status' => 1])
            ->order('publish_time desc')
            ->limit($limit)
            ->select();
    }

    // 搜索文章
    public static function search($keyword, $limit = 20)
    {
        return self::where('status', 1)
            ->where('title', 'like', "%{$keyword}%")
            ->order('publish_time desc')
            ->paginate($limit);
    }

    // 增加浏览量
    public function addView()
    {
        $this->setInc('views');
    }

    // 增加点赞数
    public function addLike()
    {
        $this->setInc('likes');
    }
}
