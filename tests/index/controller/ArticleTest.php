<?php
namespace tests\index\controller;

use PHPUnit\Framework\TestCase;
use app\index\controller\Article;
use app\common\model\Article as ArticleModel;
use app\common\model\Comment as CommentModel;
use app\common\model\Category as CategoryModel;

/**
 * Article controller unit tests.
 */
class ArticleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    // -------------------------------------------------------------------------
    // detail() tests
    // -------------------------------------------------------------------------

    public function testDetailReturnsErrorWhenArticleNotFound()
    {
        ArticleModel::shouldReceive('get')
            ->once()
            ->with(999)
            ->andReturn(null);

        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['error'])
            ->getMock();

        $controller->expects($this->once())
            ->method('error')
            ->with('文章不存在或已下架');

        $controller->detail(999);
    }

    public function testDetailReturnsErrorWhenArticleNotPublished()
    {
        $mockArticle = new \stdClass();
        $mockArticle->id = 1;
        $mockArticle->status = 0; // 已下线

        ArticleModel::shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($mockArticle);

        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['error'])
            ->getMock();

        $controller->expects($this->once())
            ->method('error')
            ->with('文章不存在或已下架');

        $controller->detail(1);
    }

    public function testDetailSuccess()
    {
        $mockArticle = new \stdClass();
        $mockArticle->id = 1;
        $mockArticle->status = 1;
        $mockArticle->category_id = 5;
        $mockArticle->views = 100;
        $mockArticle->likes = 10;

        // Mock ArticleModel::get
        ArticleModel::shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($mockArticle);

        // Mock setInc for views
        $mockArticle->shouldReceive('setInc')
            ->once()
            ->with('views')
            ->andReturn(true);

        // Mock prev article
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('id', '<', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('status', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('order')
            ->once()
            ->with('id desc')
            ->andReturnSelf();
        ArticleModel::shouldReceive('find')
            ->once()
            ->andReturn(null);

        // Mock next article
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('id', '>', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('where')
            ->twice()
            ->with('status', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('order')
            ->once()
            ->with('id asc')
            ->andReturnSelf();
        ArticleModel::shouldReceive('find')
            ->once()
            ->andReturn(null);

        // Mock related articles
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('category_id', 5)
            ->andReturnSelf();
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('id', '<>', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('where')
            ->once()
            ->with('status', 1)
            ->andReturnSelf();
        ArticleModel::shouldReceive('limit')
            ->once()
            ->with(5)
            ->andReturnSelf();
        ArticleModel::shouldReceive('select')
            ->once()
            ->andReturn([]);

        // Mock comments
        CommentModel::shouldReceive('getArticleComments')
            ->once()
            ->with(1, 50)
            ->andReturn([]);

        // Mock hot articles
        ArticleModel::shouldReceive('getHot')
            ->once()
            ->with(10)
            ->andReturn([]);

        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['assign', 'fetch'])
            ->getMock();

        $controller->expects($this->exactly(8))
            ->method('assign');

        $controller->expects($this->once())
            ->method('fetch')
            ->willReturn('view content');

        $result = $controller->detail(1);
        $this->assertEquals('view content', $result);
    }

    // -------------------------------------------------------------------------
    // lists() tests
    // -------------------------------------------------------------------------

    public function testListsWithoutCategory()
    {
        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['assign', 'fetch'])
            ->getMock();

        $mockList = new \think\Collection();

        // Mock ArticleModel
        $mockArticleModel = $this->createMock(ArticleModel::class);
        $mockArticleModel->expects($this->once())
            ->method('getPublishedList')
            ->with([], 'is_top desc, publish_time desc', 15)
            ->willReturn($mockList);

        // Mock CategoryModel::getTree
        CategoryModel::shouldReceive('getTree')
            ->once()
            ->with(0)
            ->andReturn([]);

        // Mock ArticleModel::getHot
        ArticleModel::shouldReceive('getHot')
            ->once()
            ->with(10)
            ->andReturn([]);

        $controller->expects($this->exactly(4))
            ->method('assign');

        $controller->expects($this->once())
            ->method('fetch')
            ->willReturn('view content');

        $result = $controller->lists(0);
        $this->assertEquals('view content', $result);
    }

    public function testListsWithCategory()
    {
        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['assign', 'fetch'])
            ->getMock();

        $mockList = new \think\Collection();

        // Mock CategoryModel::getTree
        CategoryModel::shouldReceive('getTree')
            ->once()
            ->with(0)
            ->andReturn([]);

        // Mock ArticleModel::getHot
        ArticleModel::shouldReceive('getHot')
            ->once()
            ->with(10)
            ->andReturn([]);

        $controller->expects($this->exactly(4))
            ->method('assign');

        $controller->expects($this->once())
            ->method('fetch')
            ->willReturn('view content');

        $result = $controller->lists(5);
        $this->assertEquals('view content', $result);
    }

    // -------------------------------------------------------------------------
    // like() tests
    // -------------------------------------------------------------------------

    public function testLikeReturnsErrorWhenNotAjax()
    {
        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['isAjax'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isAjax')
            ->willReturn(false);

        $response = $controller->like(1);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('请求方式错误', $data['msg']);
    }

    public function testLikeReturnsErrorWhenArticleNotFound()
    {
        ArticleModel::shouldReceive('get')
            ->once()
            ->with(999)
            ->andReturn(null);

        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['isAjax'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);

        $response = $controller->like(999);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('文章不存在', $data['msg']);
    }

    public function testLikeSuccess()
    {
        $mockArticle = new \stdClass();
        $mockArticle->likes = 10;

        ArticleModel::shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($mockArticle);

        $mockArticle->shouldReceive('setInc')
            ->once()
            ->with('likes')
            ->andReturn(true);

        $controller = $this->getMockBuilder(Article::class)
            ->setMethods(['isAjax'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);

        $response = $controller->like(1);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(1, $data['code']);
        $this->assertEquals('点赞成功', $data['msg']);
    }
}
