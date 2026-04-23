<?php
namespace app\index\exception;

use Exception;
use think\Config;
use think\exception\HttpException;
use think\exception\Handle;
use think\Request;

class ExceptionHandler extends Handle
{
    protected $ignoreReport = [
        'think\\exception\\HttpException',
    ];

    public function render(Exception $e)
    {
        $request = Request::instance();

        // 如果是 AJAX 请求，返回 JSON
        if ($request->isAjax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            if ($e instanceof HttpException) {
                $code = $e->getStatusCode();
                $msg = $this->getHttpMessage($code);
            } else {
                $code = 500;
                $msg = $e->getMessage() ?: '服务器内部错误';
            }
            return json(['code' => 0, 'msg' => $msg], $code);
        }

        // 如果是 HTTP 异常
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
            $msg = $this->getHttpMessage($code);
        } else {
            $code = 500;
            $msg = $e->getMessage() ?: '服务器内部错误';
        }

        // 调试模式使用框架默认错误页
        if (Config::get('app_debug')) {
            return parent::render($e);
        }

        // 生产环境使用自定义错误页
        return view('public/error', [
            'code' => $code,
            'msg'  => $msg,
            'data' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ]);
    }

    protected function getHttpMessage($code)
    {
        $messages = [
            400 => '请求错误',
            401 => '未授权，请先登录',
            403 => '禁止访问',
            404 => '页面不存在',
            405 => '请求方法不被允许',
            500 => '服务器内部错误',
            502 => '服务网关错误',
            503 => '服务暂时不可用',
            504 => '网关超时',
        ];
        return isset($messages[$code]) ? $messages[$code] : '请求错误';
    }
}
