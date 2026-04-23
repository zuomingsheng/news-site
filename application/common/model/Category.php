<?php
namespace app\common\model;

use think\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return isset($status[$data['status']]) ? $status[$data['status']] : '未知';
    }

    // 获取父级分类
    public function parent()
    {
        return $this->belongsTo('Category', 'parent_id');
    }

    // 获取子分类
    public function children()
    {
        return $this->hasMany('Category', 'parent_id');
    }

    // 获取文章数
    public function getArticleCountAttr($value, $data)
    {
        return model('Article')->where('category_id', $data['id'])->where('status', 1)->count();
    }

    // 获取所有启用的分类（树形结构）
    public static function getTree($parentId = 0, $exceptId = null)
    {
        $where = ['status' => 1];
        if ($parentId !== null) {
            $where['parent_id'] = $parentId;
        }

        $list = self::where($where)
            ->order('sort asc, id asc')
            ->select();

        if ($exceptId) {
            $list = array_filter($list, function($item) use ($exceptId) {
                return $item['id'] != $exceptId;
            });
        }

        return $list;
    }
}
