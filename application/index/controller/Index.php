<?php
namespace app\index\controller;

use think\Controller;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use app\common\model\Banner as BannerModel;
use app\common\model\Config as ConfigModel;

class Index extends Controller
{
    // 首页
    public function index()
    {
        // _static 参数：由 serveStatic 处理
        if (input('_static')) {
            return $this->serveStatic();
        }

        // 获取轮播图
        $banners = BannerModel::getActiveBanners();
        $this->assign('banners', $banners);

        // 获取置顶文章
        $topArticles = ArticleModel::getTop(3);
        $this->assign('topArticles', $topArticles);

        // 获取推荐文章
        $recommendArticles = ArticleModel::getRecommend(8);
        $this->assign('recommendArticles', $recommendArticles);

        // 获取最新文章
        $latestArticles = ArticleModel::getLatest(10);
        $this->assign('latestArticles', $latestArticles);

        // 获取热门文章
        $hotArticles = ArticleModel::getHot(10);
        $this->assign('hotArticles', $hotArticles);

        // 获取所有分类
        $categories = CategoryModel::getTree(0);
        $this->assign('categories', $categories);

        // 获取网站配置
        $siteName = ConfigModel::getValue('site_name', '综合新闻网站');
        $this->assign('siteName', $siteName);

        return $this->fetch();
    }

    public function debug()
    {
        header('Content-Type: application/json');
        $data = [
            'top' => ArticleModel::getTop(3),
            'recommend' => ArticleModel::getRecommend(8),
            'latest' => ArticleModel::getLatest(10),
            'hot' => ArticleModel::getHot(10),
        ];
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 分类页面
    public function category($cid = 0)
    {

        $cid = intval($cid);


        // 获取当前分类信息
        $category = CategoryModel::get($cid);

        if (!$category || $category['status'] != 1) {
            $this->error('分类不存在');
        }
        $this->assign('category', $category);

        // 获取该分类下的文章列表
        $articleModel = new ArticleModel();
        $list = $articleModel->getPublishedList(['category_id' => $cid], 'is_top desc, publish_time desc', 15);
        $this->assign('list', $list);

        // 获取所有分类（用于侧边栏）
        $categories = CategoryModel::getTree(0);
        $this->assign('categories', $categories);

        // 获取热门文章
        $hotArticles = ArticleModel::getHot(10);
        $this->assign('hotArticles', $hotArticles);

        // 传给模板的分类ID
        $this->assign('cid', $cid);

        return $this->fetch('article/lists');
    }

    // Nginx try_files 找不到静态文件时 fallback 到此
    // 访问路径：/index.php?_static=/static/images/xxx.jpg
    public function serveStatic()
    {
        $uri = input('_static', '');
        if (empty($uri) || strpos($uri, '/') !== 0) {
            header('HTTP/1.1 404 Not Found');
            exit('Not Found');
        }

        // 获取 public 目录路径
        $publicRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (empty($publicRoot) && isset($_SERVER['SCRIPT_FILENAME'])) {
            $publicRoot = dirname($_SERVER['SCRIPT_FILENAME']);
        }
        $publicRoot = str_replace('\\', '/', rtrim($publicRoot, '/'));
        $filePath = $publicRoot . '/' . ltrim($uri, '/');
        // 清理路径中的 ./
        $filePath = str_replace(['/./', '//'], ['/', '/'], $filePath);

        // 安全检查：禁止 ../ 跳出 public 目录
        $realRoot = str_replace('\\', '/', realpath($publicRoot) ?: $publicRoot);
        $realFilePath = realpath($filePath);
        if ($realFilePath === false || strpos(str_replace('\\', '/', $realFilePath), $realRoot) !== 0) {
            header('HTTP/1.1 404 Not Found');
            exit('Not Found');
        }

        $ext = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/x-icon',
            'swf'  => 'application/x-shockwave-flash',
            'js'   => 'application/javascript',
            'css'  => 'text/css',
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $contentType);
        header('Expires: ' . gmdate('D, d M Y H:i:s', filemtime($realFilePath) + 2592000) . ' GMT');
        header('Cache-Control: max-age=2592000');
        readfile($realFilePath);
        exit;
    }
}
