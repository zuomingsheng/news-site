<?php
/**
 * ThinkPHP Test Bootstrap (No Composer Autoload)
 * 只加载 PHPUnit，不加载 ThinkPHP autoload
 * 测试文件应该自包含 stub 类
 */

// 加载 Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// 加载完成后，立即覆盖 think-captcha 的 helper
// 因为 think-captcha 的 helper.php 会在加载时注册路由
// 我们通过设置类别名来阻止 Route 初始化问题
