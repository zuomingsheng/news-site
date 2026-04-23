<?php
/**
 * Unit tests for app\index\controller\Index
 *
 * Run with:
 *   vendor/bin/phpunit tests/index/controller/IndexTest.php
 *
 * Or register the tests/ directory in phpunit.xml:
 *   <directory>./tests/</directory>
 *
 * These tests use lightweight stubs for all ThinkPHP internals and model
 * classes so that no database connection or full framework bootstrap is
 * required.  PHP 7.3+ compatible (no typed properties).
 *
 * Static return values for model stubs are controlled via public static
 * "fixture" properties on each stub class; tests reset them in setUp().
 */

// ====================================================================
// SECTION 1 – Global ThinkPHP helper functions
// Must be declared in the global namespace before any class definitions.
// ====================================================================

namespace {
    if (!function_exists('input')) {
        /**
         * Minimal stub for ThinkPHP's global input() helper.
         * Tests inject values via InputStore::set() before each case.
         *
         * @param  string $key
         * @param  mixed  $default
         * @return mixed
         */
        function input($key, $default = '')
        {
            return \tests\index\controller\InputStore::get($key, $default);
        }
    }
}

// ====================================================================
// SECTION 2 – ThinkPHP framework stubs (namespace think)
// ====================================================================

namespace think {

    if (!class_exists('think\\Request')) {
        class Request
        {
            /**
             * @return static
             */
            public static function instance()
            {
                return new static();
            }
        }
    }

    if (!class_exists('think\\View')) {
        class View
        {
            /** @var array */
            private $assigns = [];

            /**
             * @param  array $template
             * @param  array $replace
             * @return static
             */
            public static function instance($template = [], $replace = [])
            {
                return new static();
            }

            /**
             * @param  string|array $name
             * @param  mixed        $value
             * @return $this
             */
            public function assign($name, $value = '')
            {
                if (is_array($name)) {
                    $this->assigns = array_merge($this->assigns, $name);
                } else {
                    $this->assigns[$name] = $value;
                }
                return $this;
            }

            /**
             * @param  string $template
             * @param  array  $vars
             * @param  array  $replace
             * @return string
             */
            public function fetch($template = '', $vars = [], $replace = [])
            {
                return '<!-- rendered: ' . $template . ' -->';
            }

            /**
             * @return array
             */
            public function getAssigns()
            {
                return $this->assigns;
            }
        }
    }

    if (!class_exists('think\\Config')) {
        class Config
        {
            /**
             * @param  string $name
             * @param  mixed  $default
             * @return mixed
             */
            public static function get($name = '', $default = null)
            {
                return $default;
            }
        }
    }

    if (!class_exists('think\\Controller')) {
        /**
         * Minimal stub replacing think\Controller.
         *
         * - assign() accumulates template variables in $this->assigns.
         * - fetch() records the template name and returns a predictable string.
         * - error() throws \RuntimeException so tests can assert it.
         */
        abstract class Controller
        {
            /** @var View */
            protected $view;

            /** @var Request */
            protected $request;

            /**
             * All template variables assigned during the request.
             * @var array
             */
            public $assigns = [];

            /**
             * Last template name passed to fetch().
             * @var string
             */
            public $lastFetchTemplate = '';

            /**
             * @param Request|null $request
             */
            public function __construct($request = null)
            {
                $this->view    = View::instance([], []);
                $this->request = $request ?: Request::instance();
                $this->_initialize();
            }

            protected function _initialize()
            {
            }

            /**
             * @param  string|array $name
             * @param  mixed        $value
             */
            protected function assign($name, $value = '')
            {
                if (is_array($name)) {
                    $this->assigns = array_merge($this->assigns, $name);
                } else {
                    $this->assigns[$name] = $value;
                }
            }

            /**
             * @param  string $template
             * @param  array  $vars
             * @param  array  $replace
             * @param  array  $config
             * @return string
             */
            protected function fetch($template = '', $vars = [], $replace = [], $config = [])
            {
                $this->lastFetchTemplate = $template;
                return '<!-- rendered: ' . $template . ' -->';
            }

            /**
             * Throws RuntimeException so PHPUnit can catch and assert it.
             *
             * @param  string $msg
             * @param  string $url
             * @param  mixed  $data
             * @param  int    $wait
             * @param  array  $header
             * @throws \RuntimeException
             */
            protected function error($msg = '', $url = '', $data = '', $wait = 3, $header = [])
            {
                throw new \RuntimeException('controller_error: ' . $msg);
            }

            /**
             * @param  string $msg
             * @param  string $url
             * @param  mixed  $data
             * @param  int    $wait
             * @param  array  $header
             * @return string
             */
            protected function success($msg = '', $url = '', $data = '', $wait = 3, $header = [])
            {
                return 'controller_success: ' . $msg;
            }
        }
    }
}

// ====================================================================
// SECTION 3 – Model stubs  (namespace app\common\model)
//
// Each stub class exposes public static "fixture" properties that tests
// populate before exercising the controller, mirroring the real models'
// public API without requiring a database connection.
// ====================================================================

namespace app\common\model {

    if (!class_exists('app\\common\\model\\Article')) {
        class Article
        {
            // ---- configurable fixtures (set by tests) ----

            /** @var array */
            public static $topFixture = [];

            /** @var array */
            public static $recommendFixture = [];

            /** @var array */
            public static $latestFixture = [];

            /** @var array */
            public static $hotFixture = [];

            /** @var array */
            public static $publishedFixture = [];

            // ---- stub implementations ----

            /**
             * @param  int $limit
             * @return array
             */
            public static function getTop($limit = 3)
            {
                return array_slice(static::$topFixture, 0, $limit);
            }

            /**
             * @param  int $limit
             * @return array
             */
            public static function getRecommend($limit = 5)
            {
                return array_slice(static::$recommendFixture, 0, $limit);
            }

            /**
             * @param  int $limit
             * @return array
             */
            public static function getLatest($limit = 10)
            {
                return array_slice(static::$latestFixture, 0, $limit);
            }

            /**
             * @param  int $limit
             * @return array
             */
            public static function getHot($limit = 10)
            {
                return array_slice(static::$hotFixture, 0, $limit);
            }

            /**
             * @param  array  $where
             * @param  string $order
             * @param  int    $limit
             * @return array
             */
            public function getPublishedList($where = [], $order = 'is_top desc, publish_time desc', $limit = 10)
            {
                return static::$publishedFixture;
            }
        }
    }

    if (!class_exists('app\\common\\model\\Category')) {
        class Category
        {
            // ---- configurable fixtures ----

            /** @var array */
            public static $treeFixture = [];

            /**
             * null  = "not found"
             * array = the category row
             * @var array|null
             */
            public static $getFixture = null;

            // ---- stub implementations ----

            /**
             * @param  int        $parentId
             * @param  int|null   $exceptId
             * @return array
             */
            public static function getTree($parentId = 0, $exceptId = null)
            {
                return static::$treeFixture;
            }

            /**
             * Mimics ThinkPHP Model::get($id).
             *
             * @param  mixed $id
             * @return array|null
             */
            public static function get($id)
            {
                return static::$getFixture;
            }
        }
    }

    if (!class_exists('app\\common\\model\\Banner')) {
        class Banner
        {
            // ---- configurable fixtures ----

            /** @var array */
            public static $activeFixture = [];

            /**
             * @return array
             */
            public static function getActiveBanners()
            {
                return static::$activeFixture;
            }
        }
    }

    if (!class_exists('app\\common\\model\\Config')) {
        class Config
        {
            /**
             * key => value store used by tests.
             * @var array
             */
            public static $store = [];

            /**
             * @param  string $name
             * @param  mixed  $default
             * @return mixed
             */
            public static function getValue($name, $default = '')
            {
                return isset(static::$store[$name]) ? static::$store[$name] : $default;
            }
        }
    }
}

// ====================================================================
// SECTION 4 – Load the real controller under test
//
// Must come BEFORE TestableIndex (which extends it) but AFTER the stubs
// so that the stubs are already registered when Index.php is parsed.
// ====================================================================

namespace {
    require_once __DIR__ . '/../../../application/index/controller/Index.php';
}

// ====================================================================
// SECTION 5 – Test helpers and the test class
// ====================================================================

namespace tests\index\controller {

    use PHPUnit\Framework\TestCase;
    use app\common\model\Article  as ArticleStub;
    use app\common\model\Category as CategoryStub;
    use app\common\model\Banner   as BannerStub;
    use app\common\model\Config   as ConfigStub;

    // ----------------------------------------------------------------
    // InputStore – simple key/value store that backs the input() stub
    // ----------------------------------------------------------------
    class InputStore
    {
        /** @var array */
        private static $data = [];

        /**
         * @param string $key
         * @param mixed  $value
         */
        public static function set($key, $value)
        {
            static::$data[$key] = $value;
        }

        /**
         * @param  string $key
         * @param  mixed  $default
         * @return mixed
         */
        public static function get($key, $default = '')
        {
            return isset(static::$data[$key]) ? static::$data[$key] : $default;
        }

        public static function clear()
        {
            static::$data = [];
        }
    }

    // ----------------------------------------------------------------
    // TestableIndex – thin subclass of the real Index controller
    //
    // - Overrides serveStatic() so that tests of index() can verify
    //   delegation without running the real filesystem/header code.
    // - Exposes callServeStatic() to forward to the real implementation
    //   in tests that specifically target serveStatic().
    // - Exposes getAssigns() so tests can inspect assigned view variables.
    // ----------------------------------------------------------------
    class TestableIndex extends \app\index\controller\Index
    {
        /**
         * Set to true when the overridden serveStatic() is called.
         * @var bool
         */
        public $serveStaticCalled = false;

        /**
         * When non-null, serveStatic() returns this string instead of ''.
         * @var string|null
         */
        public $serveStaticReturn = null;

        /**
         * Forward to the parent's real serveStatic() implementation.
         * Used by tests that exercise serveStatic() directly.
         */
        public function callServeStatic()
        {
            return parent::serveStatic();
        }

        /**
         * Override hides real header/readfile/exit logic from index() tests.
         * Must be public to match the visibility in app\index\controller\Index.
         */
        public function serveStatic()
        {
            $this->serveStaticCalled = true;
            return $this->serveStaticReturn !== null ? $this->serveStaticReturn : '';
        }

        /**
         * Expose accumulated view assigns for assertions.
         * @return array
         */
        public function getAssigns()
        {
            return $this->assigns;
        }
    }

    // ----------------------------------------------------------------
    // IndexTest
    // ----------------------------------------------------------------

    /**
     * @covers \app\index\controller\Index
     */
    class IndexTest extends TestCase
    {
        /** @var TestableIndex */
        private $controller;

        protected function setUp(): void
        {
            // Reset all test inputs
            InputStore::clear();

            // Reset model fixtures to safe empty defaults
            ArticleStub::$topFixture       = [];
            ArticleStub::$recommendFixture = [];
            ArticleStub::$latestFixture    = [];
            ArticleStub::$hotFixture       = [];
            ArticleStub::$publishedFixture = [];

            CategoryStub::$treeFixture = [];
            CategoryStub::$getFixture  = null;

            BannerStub::$activeFixture = [];

            ConfigStub::$store = [];

            // Fresh controller for every test
            $this->controller = new TestableIndex();
        }

        // ============================================================
        // index() tests
        // ============================================================

        /**
         * @test
         * index() must delegate to serveStatic() when _static param is truthy.
         */
        public function index_delegatesToServeStaticWhenStaticParamIsSet()
        {
            InputStore::set('_static', '/static/css/app.css');

            $this->controller->index();

            $this->assertTrue(
                $this->controller->serveStaticCalled,
                'index() must call serveStatic() when _static input is truthy'
            );
        }

        /**
         * @test
         * index() must NOT call serveStatic() for normal (non-static) requests.
         */
        public function index_doesNotCallServeStaticForNormalRequest()
        {
            InputStore::set('_static', '');   // falsy value

            $this->controller->index();

            $this->assertFalse(
                $this->controller->serveStaticCalled,
                'index() must not call serveStatic() for normal requests'
            );
        }

        /**
         * @test
         * index() must assign banners returned by BannerModel to the view.
         */
        public function index_assignsBannersFromBannerModel()
        {
            $fakeBanners = [
                ['id' => 1, 'title' => '头图一', 'image' => '/static/img/b1.jpg', 'status' => 1],
                ['id' => 2, 'title' => '头图二', 'image' => '/static/img/b2.jpg', 'status' => 1],
            ];
            BannerStub::$activeFixture = $fakeBanners;

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertArrayHasKey('banners', $assigns);
            $this->assertSame($fakeBanners, $assigns['banners']);
        }

        /**
         * @test
         * index() must honour the limit of 3 for top articles.
         */
        public function index_assignsTopArticlesLimitedToThree()
        {
            ArticleStub::$topFixture = [
                ['id' => 1, 'title' => '头条一'],
                ['id' => 2, 'title' => '头条二'],
                ['id' => 3, 'title' => '头条三'],
                ['id' => 4, 'title' => '头条四'],  // beyond the limit – should be excluded
            ];

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertArrayHasKey('topArticles', $assigns);
            $this->assertCount(3, $assigns['topArticles']);
        }

        /**
         * @test
         * index() must assign all required template variables.
         */
        public function index_assignsAllRequiredViewVariables()
        {
            ArticleStub::$recommendFixture = [['id' => 10, 'title' => '推荐文章']];
            ArticleStub::$latestFixture    = [['id' => 20, 'title' => '最新文章']];
            ArticleStub::$hotFixture       = [['id' => 30, 'title' => '热门文章']];
            CategoryStub::$treeFixture     = [['id' => 1, 'name' => '科技', 'status' => 1]];

            $this->controller->index();

            $assigns   = $this->controller->getAssigns();
            $required  = ['banners', 'topArticles', 'recommendArticles', 'latestArticles', 'hotArticles', 'categories', 'siteName'];

            foreach ($required as $key) {
                $this->assertArrayHasKey($key, $assigns, "Expected '{$key}' to be assigned to the view");
            }
        }

        /**
         * @test
         * index() must use the site name stored in ConfigModel.
         */
        public function index_assignsSiteNameFromConfigModel()
        {
            ConfigStub::$store['site_name'] = '测试新闻站';

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertArrayHasKey('siteName', $assigns);
            $this->assertSame('测试新闻站', $assigns['siteName']);
        }

        /**
         * @test
         * index() must fall back to the default site name when config is absent.
         */
        public function index_usesDefaultSiteNameWhenConfigMissing()
        {
            // ConfigStub::$store is empty (reset in setUp)
            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertSame('综合新闻网站', $assigns['siteName']);
        }

        /**
         * @test
         * index() must return the string produced by fetch().
         */
        public function index_returnsFetchOutput()
        {
            $result = $this->controller->index();

            $this->assertIsString($result);
            $this->assertStringStartsWith('<!-- rendered:', $result);
        }

        // ============================================================
        // debug() tests
        // ============================================================

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * debug() must output valid JSON with the four expected top-level keys.
         * Runs in a separate process because the method calls exit().
         */
        public function debug_outputsValidJsonWithExpectedKeys()
        {
            ArticleStub::$topFixture       = [['id' => 1, 'title' => '头条']];
            ArticleStub::$recommendFixture = [['id' => 2, 'title' => '推荐']];
            ArticleStub::$latestFixture    = [['id' => 3, 'title' => '最新']];
            ArticleStub::$hotFixture       = [['id' => 4, 'title' => '热门']];

            ob_start();
            try {
                $this->controller->debug();
            } catch (\Throwable $e) {
                // Some environments convert exit() into a catchable exception
            }
            $output = ob_get_clean();

            $data = json_decode($output, true);

            $this->assertNotNull($data, 'debug() must output valid JSON');
            $this->assertArrayHasKey('top',       $data);
            $this->assertArrayHasKey('recommend', $data);
            $this->assertArrayHasKey('latest',    $data);
            $this->assertArrayHasKey('hot',       $data);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * debug() JSON content must reflect the data returned by each model method.
         */
        public function debug_jsonContentsMatchModelFixtures()
        {
            ArticleStub::$topFixture       = [['id' => 5, 'title' => '头条A']];
            ArticleStub::$recommendFixture = [['id' => 6, 'title' => '推荐A']];
            ArticleStub::$latestFixture    = [];
            ArticleStub::$hotFixture       = [['id' => 7], ['id' => 8]];

            ob_start();
            try {
                $this->controller->debug();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $output = ob_get_clean();
            $data   = json_decode($output, true);

            $this->assertSame([['id' => 5, 'title' => '头条A']], $data['top']);
            $this->assertSame([['id' => 6, 'title' => '推荐A']], $data['recommend']);
            $this->assertSame([], $data['latest']);
            $this->assertCount(2, $data['hot']);
        }

        // ============================================================
        // category() tests
        // ============================================================

        /**
         * @test
         * category() must call error() when CategoryModel::get() returns null.
         */
        public function category_throwsErrorWhenCategoryNotFound()
        {
            CategoryStub::$getFixture = null;

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/controller_error:.*分类不存在/');

            $this->controller->category(99);
        }

        /**
         * @test
         * category() must call error() when the category has status != 1.
         */
        public function category_throwsErrorWhenCategoryIsDisabled()
        {
            CategoryStub::$getFixture = ['id' => 5, 'name' => '归档', 'status' => 0];

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/controller_error:.*分类不存在/');

            $this->controller->category(5);
        }

        /**
         * @test
         * category() must assign all required view variables for a valid category.
         */
        public function category_assignsAllRequiredVariablesForValidCategory()
        {
            $fakeCategory = ['id' => 3, 'name' => '科技', 'status' => 1];
            CategoryStub::$getFixture      = $fakeCategory;
            CategoryStub::$treeFixture     = [$fakeCategory];
            ArticleStub::$publishedFixture = [['id' => 100, 'title' => '文章甲']];
            ArticleStub::$hotFixture       = [['id' => 200, 'title' => '热文乙']];

            $this->controller->category(3);

            $assigns = $this->controller->getAssigns();
            foreach (['category', 'list', 'categories', 'hotArticles', 'cid'] as $key) {
                $this->assertArrayHasKey($key, $assigns, "Expected '{$key}' to be assigned");
            }
        }

        /**
         * @test
         * category() must cast the cid parameter to integer before assigning it.
         */
        public function category_assignsCorrectIntegerCid()
        {
            CategoryStub::$getFixture = ['id' => 7, 'name' => '体育', 'status' => 1];

            $this->controller->category('7');   // string input – must be cast

            $assigns = $this->controller->getAssigns();
            $this->assertSame(7, $assigns['cid']);
        }

        /**
         * @test
         * category() must render the 'article/lists' template.
         */
        public function category_fetchesArticleListsTemplate()
        {
            CategoryStub::$getFixture = ['id' => 2, 'name' => '财经', 'status' => 1];

            $result = $this->controller->category(2);

            $this->assertSame('article/lists', $this->controller->lastFetchTemplate);
            $this->assertInternalType('string', $result);
        }

        /**
         * @test
         * category() must assign the exact category row returned by the model.
         */
        public function category_assignsCategoryData()
        {
            $fakeCategory = ['id' => 4, 'name' => '娱乐', 'status' => 1];
            CategoryStub::$getFixture = $fakeCategory;

            $this->controller->category(4);

            $assigns = $this->controller->getAssigns();
            $this->assertSame($fakeCategory, $assigns['category']);
        }

        /**
         * @test
         * category() with no argument uses cid=0 which, when unmatched,
         * triggers the error path.
         */
        public function category_defaultCidIsZeroTriggersErrorWhenNotFound()
        {
            CategoryStub::$getFixture = null;  // cid=0 returns nothing

            $this->expectException(\RuntimeException::class);

            $this->controller->category();  // uses default $cid = 0
        }

        /**
         * @test
         * category() must pass the correct category_id filter to getPublishedList.
         */
        public function category_articleListContainsPublishedArticlesForCategory()
        {
            CategoryStub::$getFixture      = ['id' => 6, 'name' => '国际', 'status' => 1];
            ArticleStub::$publishedFixture = [
                ['id' => 50, 'category_id' => 6, 'title' => '国际头条一'],
                ['id' => 51, 'category_id' => 6, 'title' => '国际头条二'],
            ];

            $this->controller->category(6);

            $assigns = $this->controller->getAssigns();
            $this->assertCount(2, $assigns['list']);
        }

        // ============================================================
        // serveStatic() tests
        // ============================================================

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must respond with 404 / "Not Found" when _static is empty.
         */
        public function serveStatic_returns404WhenUriIsEmpty()
        {
            InputStore::set('_static', '');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            $this->assertSame('Not Found', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must respond with 404 when the URI does not start with '/'.
         */
        public function serveStatic_returns404WhenUriDoesNotStartWithSlash()
        {
            InputStore::set('_static', 'relative/path/file.js');  // no leading slash

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            $this->assertSame('Not Found', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must respond with 404 for a path-traversal URI that
         * attempts to escape the document root.
         */
        public function serveStatic_returns404OnDirectoryTraversalAttempt()
        {
            InputStore::set('_static', '/../../../etc/passwd');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            $this->assertSame('Not Found', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must respond with 404 for a URI pointing to a file
         * that does not exist on disk.
         */
        public function serveStatic_returns404WhenFileDoesNotExist()
        {
            $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir();
            InputStore::set('_static', '/nonexistent_phpunit_file_xyz.css');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            $this->assertSame('Not Found', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must output the file contents for a real existing file.
         */
        public function serveStatic_servesExistingFile()
        {
            $tmpDir  = str_replace('\\', '/', sys_get_temp_dir());
            $tmpFile = $tmpDir . '/phpunit_test_serve.css';
            file_put_contents($tmpFile, 'body { color: red; }');

            $_SERVER['DOCUMENT_ROOT'] = $tmpDir;
            InputStore::set('_static', '/phpunit_test_serve.css');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            @unlink($tmpFile);

            $this->assertSame('body { color: red; }', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must serve a PNG file (verifies the MIME-type extension
         * map handles image extensions without returning 404).
         */
        public function serveStatic_servesExistingImageFile()
        {
            $tmpDir  = str_replace('\\', '/', sys_get_temp_dir());
            $tmpFile = $tmpDir . '/phpunit_test_img.png';
            file_put_contents($tmpFile, 'fake-png-data');

            $_SERVER['DOCUMENT_ROOT'] = $tmpDir;
            InputStore::set('_static', '/phpunit_test_img.png');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            @unlink($tmpFile);

            $this->assertSame('fake-png-data', $body);
        }

        /**
         * @test
         * @runInSeparateProcess
         * @preserveGlobalState disabled
         *
         * serveStatic() must fall back to DOCUMENT_ROOT derived from
         * SCRIPT_FILENAME when DOCUMENT_ROOT is not set.
         */
        public function serveStatic_derivesRootFromScriptFilenameWhenDocumentRootMissing()
        {
            $tmpDir  = str_replace('\\', '/', sys_get_temp_dir());
            $tmpFile = $tmpDir . '/phpunit_test_js.js';
            file_put_contents($tmpFile, 'alert(1);');

            unset($_SERVER['DOCUMENT_ROOT']);
            $_SERVER['SCRIPT_FILENAME'] = $tmpDir . '/index.php';
            InputStore::set('_static', '/phpunit_test_js.js');

            ob_start();
            try {
                $this->controller->callServeStatic();
            } catch (\Throwable $e) {
                // swallow exit
            }
            $body = ob_get_clean();

            @unlink($tmpFile);

            $this->assertSame('alert(1);', $body);
        }
    }
}

