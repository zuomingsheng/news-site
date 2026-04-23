<?php
namespace app\common\model;

use think\Model;

class Config extends Model
{
    protected $table = 'configs';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取配置值
    public static function getValue($name, $default = '')
    {
        $config = self::where('name', $name)->find();
        return $config ? $config['value'] : $default;
    }

    // 获取所有配置（按分组）
    public static function getAllByGroup()
    {
        $list = self::select();
        $result = [];
        foreach ($list as $item) {
            $result[$item['group']][$item['name']] = $item['value'];
        }
        return $result;
    }

    // 设置配置值
    public static function setValue($name, $value, $group = 'base')
    {
        $config = self::where('name', $name)->find();
        if ($config) {
            $config->value = $value;
            return $config->save();
        } else {
            return self::create([
                'name' => $name,
                'value' => $value,
                'group' => $group,
            ]);
        }
    }
}
