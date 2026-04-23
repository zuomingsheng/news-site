<?php
namespace app\common\model;

use think\Model;

class Banner extends Model
{
    protected $table = 'banners';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return isset($status[$data['status']]) ? $status[$data['status']] : '未知';
    }

    // 获取启用状态的轮播图
    public static function getActiveBanners()
    {
        return self::where('status', 1)
            ->order('sort asc')
            ->select();
    }

    // 图片地址访问器
    public function getImageAttr($value)
    {
        if (empty($value)) {
            return '/static/images/default-cover.jpg';
        }
        if (strpos($value, 'http') === 0) {
            return $value;
        }
        return $value;
    }
}
