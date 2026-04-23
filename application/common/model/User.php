<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
    protected $table = 'users';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取格式化的创建时间
    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d', $value) : '';
    }

    // 获取头像完整URL
    public function getAvatarAttr($value)
    {
        if (empty($value)) {
            return '/static/images/default-avatar.png';
        }
        return strpos($value, 'http') === 0 ? $value : '/uploads/' . $value;
    }

    // 获取状态文本
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '正常'];
        return isset($status[$data['status']]) ? $status[$data['status']] : '未知';
    }

    // 设置密码（加密）
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    // 验证密码
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    // 关联文章
    public function articles()
    {
        return $this->hasMany('Article', 'author_id');
    }

    // 关联评论
    public function comments()
    {
        return $this->hasMany('Comment', 'user_id');
    }
}
