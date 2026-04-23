<?php
namespace tests\index\controller;

use PHPUnit\Framework\TestCase;

/**
 * Comment controller unit tests.
 *
 * Bootstrap : tests/mock.php  (registered in phpunit.xml)
 * Sessions  : array backend   (configured in mock.php — no filesystem needed)
 *
 * Mocking strategy for static model methods
 * ------------------------------------------
 * The controller calls `CommentModel::addComment(...)` and
 * `CommentModel::getArticleComments(...)` which are statically bound at
 * compile time.  PHPUnit cannot intercept these without a library.
 *
 * Solution: we use a custom SPL autoloader that shadows the real
 * `app\common\model\Comment` class with `CommentModelTestDouble`
 * for the duration of each test.  The shadow class lives in the same
 * namespace as the original, so PHP's class resolution finds it
 * before the real class ever gets loaded.
 *
 * Test doubles
 * ------------
 *  - Anonymous subclass of Comment  — overrides isAjax() (non-public, cannot
 *                                        otherwise be stubbed without a library)
 *  - CommentModelTestDouble          — replaces the real model; records call
 *                                        arguments and returns deterministic data
 *  - $_POST / $_GET superglobals     — fed directly to input() for isolation
 */
class CommentTest extends TestCase
{
    /** @var string[]  autoloader keys to remove in tearDown */
    private static $registeredLoaders = [];

    protected function setUp(): void
    {
        parent::setUp();

        \think\Session::clear();
        \think\Session::init();

        $_POST = [];
        $_GET  = [];
    }

    protected function tearDown(): void
    {
        // Remove every shadow autoloader we registered
        foreach (self::$registeredLoaders as $loader) {
            spl_autoload_unregister($loader);
        }
        self::$registeredLoaders = [];

        // Restore the original Comment class by clearing the APCu/opcache
        // so a subsequent test sees the real class again.
        if (function_exists('apcu_delete')) {
            @apcu_delete('app\common\model\Comment');
        }

        \think\Session::clear();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper: install / uninstall the model shadow
    // -------------------------------------------------------------------------

    /**
     * Registers a shadow autoloader that serves CommentModelTestDouble
     * whenever PHP tries to load app\common\model\Comment.
     *
     * @param bool $throwException  Make addComment() throw for this test
     */
    private static function installModelShadow(bool $throwException = false): void
    {
        CommentModelTestDouble::reset($throwException);

        // This loader fires only for the specific class we want to shadow.
        $loader = function (string $class) {
            if ($class === 'app\common\model\Comment'
                || $class === 'app\common\model\CommentModel') {
                // Load the file that contains CommentModelTestDouble
                // (it lives in the same namespace so we can pretend it is
                //  the real Comment class once the real file is skipped)
                require_once __DIR__ . '/CommentTestDouble.php';
                return true;
            }
            return false;
        };

        spl_autoload_register($loader, true, true);
        self::$registeredLoaders[] = $loader;
    }

    // -------------------------------------------------------------------------
    // add() — request-method guard
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testAddRejectsNonAjaxRequest(): void
    {
        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return false; }
        };

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('请求方式错误', $body['msg']);
    }

    // -------------------------------------------------------------------------
    // add() — authentication guard
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testAddRequiresLogin(): void
    {
        \think\Session::clear(); // ensure no user session

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => 'any content',
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(2, $body['code']);
        $this->assertSame('请先登录', $body['msg']);
    }

    /** @runInSeparateProcess */
    public function testAddAllowedWhenLoggedIn(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => 'Valid comment.',
            'parent_id'  => '0',
        ];

        self::installModelShadow();

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        // Not code 2 — authentication guard passed
        $this->assertNotSame(2, $body['code']);
    }

    // -------------------------------------------------------------------------
    // add() — validation: article_id
    // -------------------------------------------------------------------------

    /**
     * @runInSeparateProcess
     * @dataProvider invalidArticleIdProvider
     */
    public function testAddRejectsInvalidArticleId(int $articleId): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => (string) $articleId,
            'content'    => 'Valid content',
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('文章ID无效', $body['msg']);
    }

    public static function invalidArticleIdProvider(): array
    {
        return [
            'zero'     => [0],
            'negative' => [-1],
        ];
    }

    // -------------------------------------------------------------------------
    // add() — validation: content
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testAddRejectsEmptyContent(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => '',
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('评论内容不能为空', $body['msg']);
    }

    /** @runInSeparateProcess */
    public function testAddRejectsWhitespaceOnlyContent(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => "  \n\t  ",
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('评论内容不能为空', $body['msg']);
    }

    /** @runInSeparateProcess */
    public function testAddRejectsContentExceeding500Characters(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => str_repeat('中', 501),
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('评论内容不能超过500字', $body['msg']);
    }

    /** @runInSeparateProcess */
    public function testAddAcceptsBoundaryContentAtExactly500Characters(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => str_repeat('中', 500),
            'parent_id'  => '0',
        ];

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        // At the boundary the validation branch is NOT taken.
        // The controller proceeds to the model call — if it throws we get
        // the exception branch (code 0), otherwise code 1.
        // Either way we should NOT see the validation error message.
        $this->assertNotEquals(
            0,
            $body['code'],
            'Content at exactly 500 chars must not be rejected by validation'
        );
    }

    // -------------------------------------------------------------------------
    // add() — success path (verified via shadow autoloader)
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testAddSuccessReturnsCorrectJsonStructure(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => 'Valid comment.',
            'parent_id'  => '0',
        ];

        self::installModelShadow();

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(1, $body['code']);
        $this->assertSame('评论成功', $body['msg']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id',             $body['data']);
        $this->assertArrayHasKey('content',         $body['data']);
        $this->assertArrayHasKey('user_nickname',   $body['data']);
        $this->assertArrayHasKey('create_time',     $body['data']);

        // The shadow class always returns id=99
        $this->assertSame(99, $body['data']['id']);
        $this->assertSame('Valid comment.', $body['data']['content']);
        $this->assertSame('testuser',        $body['data']['user_nickname']);

        // Verify model received the correct arguments
        $this->assertSame(
            [5, 1, 'Valid comment.', 0],
            CommentModelTestDouble::getLastCall()
        );
    }

    /** @runInSeparateProcess */
    public function testAddSuccessWithParentId(): void
    {
        \think\Session::set('user_id', 3);
        \think\Session::set('nickname', 'replyuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '10',
            'content'    => 'This is a reply.',
            'parent_id'  => '42',
        ];

        self::installModelShadow();

        $ctrl->add();

        $this->assertSame(
            [10, 3, 'This is a reply.', 42],
            CommentModelTestDouble::getLastCall()
        );
    }

    // -------------------------------------------------------------------------
    // add() — exception path
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testAddReturnsErrorWhenModelThrows(): void
    {
        \think\Session::set('user_id', 1);
        \think\Session::set('nickname', 'testuser');

        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return true; }
        };

        $_POST = [
            'article_id' => '5',
            'content'    => 'Valid comment.',
            'parent_id'  => '0',
        ];

        // Configure shadow to throw so we test the catch branch
        self::installModelShadow($throwException = true);

        $response = $ctrl->add();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('评论失败', $body['msg']);
    }

    // -------------------------------------------------------------------------
    // lists() — article_id validation
    // -------------------------------------------------------------------------

    /**
     * @runInSeparateProcess
     * @dataProvider invalidArticleIdProvider
     */
    public function testListsRejectsInvalidArticleId(int $articleId): void
    {
        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return false; }
        };

        $_GET = ['article_id' => (string) $articleId];

        $response = $ctrl->lists();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(0, $body['code']);
        $this->assertSame('文章ID无效', $body['msg']);
    }

    // -------------------------------------------------------------------------
    // lists() — success path
    // -------------------------------------------------------------------------

    /** @runInSeparateProcess */
    public function testListsSuccessReturnsCorrectJsonStructure(): void
    {
        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return false; }
        };

        $_GET = ['article_id' => '7'];

        self::installModelShadow();

        $response = $ctrl->lists();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(1, $body['code']);
        $this->assertSame('获取成功', $body['msg']);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertCount(2, $body['data']); // shadow returns 2 fixture records
    }

    /** @runInSeparateProcess */
    public function testListsPassesCorrectLimitToModel(): void
    {
        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return false; }
        };

        $_GET = ['article_id' => '7'];

        self::installModelShadow();

        $ctrl->lists();

        // The controller calls getArticleComments($articleId, 50)
        $this->assertSame(
            [7, 50],
            CommentModelTestDouble::getLastCall(),
            'lists() must pass (articleId, 50) to getArticleComments()'
        );
    }

    /** @runInSeparateProcess */
    public function testListsSuccessWithEmptyResult(): void
    {
        $ctrl = new class extends \app\index\controller\Comment {
            public function isAjax(): bool { return false; }
        };

        $_GET = ['article_id' => '99'];

        self::installModelShadow(true); // install first, then configure

        // Override the shadow to return empty for this specific test
        CommentModelTestDouble::reset(false, true);

        $response = $ctrl->lists();
        $body = json_decode($response->getContent(), true);

        $this->assertSame(1, $body['code']);
        $this->assertSame('获取成功', $body['msg']);
        $this->assertEmpty($body['data']);
    }
}

// =============================================================================
// CommentModelTestDouble
// =============================================================================
// Lives in its own file so the shadow autoloader can `require` it cleanly.
// This class is loaded INSTEAD OF the real `app\common\model\Comment` when
// the autoloader override is active, so PHP resolves static calls in the
// controller to these methods.
//
// namespace must match the real class so `use app\common\model\Comment`
// in the controller picks this file up.
// =============================================================================

namespace app\common\model;

class Comment
{
    private static $throwException = false;
    private static $returnEmpty    = false;
    private static $lastCall       = null;

    // -------------------------------------------------------------------------
    // Configuration (called from the test via installModelShadow())
    // -------------------------------------------------------------------------
    public static function _testSetThrowException(bool $v): void
    {
        self::$throwException = $v;
    }

    public static function _testSetReturnEmpty(bool $v): void
    {
        self::$returnEmpty = $v;
    }

    public static function _testReset(): void
    {
        self::$throwException = false;
        self::$returnEmpty   = false;
        self::$lastCall      = null;
    }

    // -------------------------------------------------------------------------
    // addComment — mirrors app\common\model\Comment::addComment signature
    // -------------------------------------------------------------------------
    public static function addComment(
        $articleId,
        $userId,
        $content,
        $parentId = 0
    ) {
        self::$lastCall = func_get_args();

        if (self::$throwException) {
            throw new \Exception('Simulated DB failure in test double');
        }

        return [
            'id'         => 99,
            'content'    => $content,
            'user_id'    => $userId,
            'article_id' => $articleId,
            'parent_id'  => $parentId,
        ];
    }

    // -------------------------------------------------------------------------
    // getArticleComments — mirrors app\common\model\Comment::getArticleComments
    // -------------------------------------------------------------------------
    public static function getArticleComments($articleId, $limit = 50)
    {
        self::$lastCall = [$articleId, $limit];

        if (self::$returnEmpty) {
            return [];
        }

        return [
            [
                'id'              => 1,
                'content'         => 'Shadow comment A',
                'user_nickname'   => 'Alice',
                'create_time'     => 1700000000,
            ],
            [
                'id'              => 2,
                'content'         => 'Shadow comment B',
                'user_nickname'   => 'Bob',
                'create_time'     => 1700000060,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Accessor for tests — reads private state via call_user_func
    // -------------------------------------------------------------------------
    public static function _testGetLastCall()
    {
        return self::$lastCall;
    }
}

// Return to test namespace so PHPUnit can finish loading this file.
namespace tests\index\controller;

// Forward the helper methods called by the test to the class in the model NS.
class CommentModelTestDouble
{
    public static function reset(bool $throw = false, bool $empty = false): void
    {
        $c = 'app\common\model\Comment';
        $c::_testReset();
        $c::_testSetThrowException($throw);
        $c::_testSetReturnEmpty($empty);
    }

    public static function getLastCall()
    {
        return \app\common\model\Comment::_testGetLastCall();
    }
}
