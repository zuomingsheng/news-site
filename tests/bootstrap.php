<?php
/**
 * ThinkPHP Test Bootstrap
 * 正确初始化 ThinkPHP 框架（不依赖 Loader::register）
 */

// ThinkPHP 常量
define('THINK_PATH', __DIR__ . '/../thinkphp/');
define('APP_PATH', __DIR__ . '/../application/');
define('RUNTIME_PATH', __DIR__ . '/../runtime/');
define('ROOT_PATH', __DIR__ . '/../');
define('VENDOR_PATH', ROOT_PATH . 'vendor/');
define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');
define('LIB_PATH', THINK_PATH . 'library' . DS);
define('CORE_PATH', LIB_PATH . 'think' . DS);
define('TRAIT_PATH', LIB_PATH . 'traits' . DS);
define('CONF_PATH', APP_PATH);
define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);
define('EXTEND_PATH', ROOT_PATH . 'extend' . DS);
define('APP_DEBUG', true);
define('IS_CLI', PHP_SAPI == 'cli');
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
define('ENV_PREFIX', 'PHP_');

// 检查 autoload 是否已加载
if (!class_exists('Composer\Autoload\ClassLoader')) {
    require VENDOR_PATH . 'autoload.php';
}

// 检查 think\Config 是否存在，如果不存在则手动加载
if (!class_exists('think\Config')) {
    // 手动加载 Config
    require CORE_PATH . 'Config.php';
}

// 注册 app 命名空间
\think\Loader::addNamespace('app', APP_PATH);
\think\Loader::addNamespace('common', APP_PATH . 'common/');

// 初始化配置
\think\Config::set('app_debug', true);
\think\Config::set('app_trace', false);
