<?php
use think\Route;

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    // 前台路由
    'article/:id' => 'index/article/detail',
    'category/:cid' => 'index/index/category',
    //'comment/add' => 'index/comment/add',
    'article' => 'index/article/lists',
    'search' => 'index/search/index',

    // 后台精确路由（按路径长度从长到短排列，避免前缀匹配冲突）
    'admin/login'                    => 'admin/auth/login',
    'admin/logout'                   => 'admin/auth/logout',
    ':controller/:action' => [
        'index/:controller/:action',
        [], // 路由参数，可以空
        ['controller' => '^(?!admin|api|home).*']
    ],
];
