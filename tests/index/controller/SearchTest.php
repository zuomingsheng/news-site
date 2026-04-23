<?php
/**
 * Unit tests for app\index\controller\Search
 *
 * Run in isolation:
 *   vendor/bin/phpunit tests/index/controller/SearchTest.php
 *
 * Run as part of the full suite (from tests/ directory):
 *   vendor/bin/phpunit
 *
 * Design notes
 * ------------
 * This file is intentionally self-contained. All ThinkPHP framework stubs and
 * model stubs are declared here so that no real database connection or full
 * framework bootstrap is required.
 *
 * Each stub class is guarded with `class_exists()` so that the file can be
 * included alongside other test files (e.g. IndexTest.php) that declare the
 * same stubs. When running the full suite, whichever test file is loaded first
 * wins; both files use the same fixture-property naming convention so tests
 * remain predictable.
 *
 * NOTE: If running alongside IndexTest.php, IndexTest.php's Article stub must
 * also expose a `search()` method (the stub below adds it). In isolation this
 * file provides complete stub coverage.
 */

// ====================================================================
// SECTION 1 – Global ThinkPHP helper function stubs
// Declared before any class that calls them.
// ====================================================================

namespace {
    if (!function_exists('input')) {
        /**
         * Minimal stub for ThinkPHP's global input() helper.
         *
         * Reads values from InputStore, which tests populate via
         * InputStore::set() before exercising the controller.
         *
         * Supports the 'trim' filter used by Search::index().
         *
         * @param  string $key     Parameter name (no prefix notation needed here)
         * @param  mixed  $default Value returned when the key is absent
         * @param  string $filter  Optional filter: 'trim' | 'intval' | ...
         * @return mixed
         */
        function input($key = '', $default = null, $filter = '')
        {
            $value = \tests\index\controller\InputStore::get($key, $default);

            if ($filter === 'trim' && is_string($value)) {
                return trim($value);
            }
            if ($filter === 'intval') {
                return intval($value);
            }

            return $value;
        }
    }
}

// ====================================================================
// SECTION 2 – ThinkPHP framework stubs  (namespace think)
// ====================================================================

namespace think {

    if (!class_exists('think\\Request')) {
        class Request
        {
            public static function instance()
            {
                return new static();
            }
        }
    }

    if (!class_exists('think\\View')) {
        class View
        {
            private $assigns = [];

            public static function instance($template = [], $replace = [])
            {
                return new static();
            }

            public function assign($name, $value = '')
            {
                if (is_array($name)) {
                    $this->assigns = array_merge($this->assigns, $name);
                } else {
                    $this->assigns[$name] = $value;
                }
                return $this;
            }

            public function fetch($template = '', $vars = [], $replace = [])
            {
                return '<!-- rendered: ' . $template . ' -->';
            }

            public function getAssigns()
            {
                return $this->assigns;
            }
        }
    }

    if (!class_exists('think\\Config')) {
        class Config
        {
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
         * – assign() stores variables in the public $assigns array so that
         *   test assertions can inspect them without needing a rendered view.
         * – fetch() returns a predictable sentinel string.
         * – error() throws RuntimeException so tests can catch it without an
         *   HTTP response layer.
         */
        abstract class Controller
        {
            /** @var View */
            protected $view;

            /** @var Request */
            protected $request;

            /** All template variables assigned during the request. */
            public $assigns = [];

            /** Template name passed to the last fetch() call. */
            public $lastFetchTemplate = '';

            public function __construct($request = null)
            {
                $this->view    = View::instance([], []);
                $this->request = $request ?: Request::instance();
                $this->_initialize();
            }

            protected function _initialize() {}

            protected function assign($name, $value = '')
            {
                if (is_array($name)) {
                    $this->assigns = array_merge($this->assigns, $name);
                } else {
                    $this->assigns[$name] = $value;
                }
                return $this;
            }

            protected function fetch($template = '', $vars = [], $replace = [], $config = [])
            {
                $this->lastFetchTemplate = $template;
                return '<!-- rendered: ' . $template . ' -->';
            }

            protected function error($msg = '', $url = '', $data = '', $wait = 3, $header = [])
            {
                throw new \RuntimeException('controller_error: ' . $msg);
            }

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
// Each stub exposes public static "fixture" properties that tests set
// before calling the controller.  setUp() resets them to safe defaults
// before every test.
// ====================================================================

namespace app\common\model {

    // ------------------------------------------------------------------
    // FakePaginator – returned by Article::search(); has total()
    // ------------------------------------------------------------------
    if (!class_exists('app\\common\\model\\FakePaginator')) {
        class FakePaginator
        {
            private $items;
            private $total;

            public function __construct($items = [], $total = 0)
            {
                $this->items = $items;
                $this->total = $total;
            }

            public function total()
            {
                return $this->total;
            }

            public function getItems()
            {
                return $this->items;
            }
        }
    }

    // ------------------------------------------------------------------
    // Article stub
    // ------------------------------------------------------------------
    if (!class_exists('app\\common\\model\\Article')) {
        class Article
        {
            // ---- configurable fixtures ----

            /** @var FakePaginator|null  Returned by search() */
            public static $searchFixture = null;

            /** @var array  Returned by getHot() */
            public static $hotFixture = [];

            // ---- stub implementations ----

            /**
             * Instance method (controller calls it on an instance).
             * The real model defines this as static; calling a static method on
             * an instance is valid PHP, so our instance-method stub works too.
             *
             * @param  string $keyword
             * @param  int    $limit
             * @return FakePaginator|null
             */
            public function search($keyword, $limit = 20)
            {
                return static::$searchFixture;
            }

            /**
             * @param  int   $limit
             * @return array
             */
            public static function getHot($limit = 10)
            {
                return array_slice(static::$hotFixture, 0, $limit);
            }
        }
    }

    // ------------------------------------------------------------------
    // Category stub
    // ------------------------------------------------------------------
    if (!class_exists('app\\common\\model\\Category')) {
        class Category
        {
            /** @var array  Returned by getTree() */
            public static $treeFixture = [];

            /**
             * @param  int        $parentId
             * @param  mixed|null $exceptId
             * @return array
             */
            public static function getTree($parentId = 0, $exceptId = null)
            {
                return static::$treeFixture;
            }
        }
    }
}

// ====================================================================
// SECTION 4 – Test helpers and the actual test class
// ====================================================================

namespace tests\index\controller {

    use PHPUnit\Framework\TestCase;
    use app\common\model\Article   as ArticleStub;
    use app\common\model\Category  as CategoryStub;
    use app\common\model\FakePaginator;

    // ----------------------------------------------------------------
    // InputStore – shared key→value store backing the input() stub
    // ----------------------------------------------------------------
    if (!class_exists('tests\\index\\controller\\InputStore')) {
        class InputStore
        {
            private static $data = [];

            public static function set($key, $value)
            {
                static::$data[$key] = $value;
            }

            public static function get($key, $default = null)
            {
                return array_key_exists($key, static::$data)
                    ? static::$data[$key]
                    : $default;
            }

            public static function clear()
            {
                static::$data = [];
            }
        }
    }

    // ----------------------------------------------------------------
    // TestableSearch – thin subclass; exposes $assigns (inherited from
    // the think\Controller stub) so test assertions can inspect them.
    // ----------------------------------------------------------------
    class TestableSearch extends \app\index\controller\Search
    {
        /**
         * Convenience accessor — returns all variables assigned to the view.
         *
         * @return array
         */
        public function getAssigns()
        {
            return $this->assigns;
        }
    }

    // ----------------------------------------------------------------
    // SearchTest
    // ----------------------------------------------------------------

    /**
     * @covers \app\index\controller\Search
     */
    class SearchTest extends TestCase
    {
        /** @var TestableSearch */
        private $controller;

        // ============================================================
        // Lifecycle
        // ============================================================

        protected function setUp(): void
        {
            parent::setUp();

            // Clear the input store so tests start with a blank slate
            InputStore::clear();

            // Reset model fixtures to empty / safe defaults
            ArticleStub::$searchFixture = null;
            ArticleStub::$hotFixture    = [];
            CategoryStub::$treeFixture  = [];

            // Fresh controller for every test
            $this->controller = new TestableSearch();
        }

        // ============================================================
        // Helper
        // ============================================================

        /**
         * Populate a standard set of sidebar fixtures (categories + hot articles).
         * Most tests need these to prevent null-reference errors in the controller.
         *
         * @param array $categories
         * @param array $hotArticles
         */
        private function setSidebarFixtures(array $categories = [], array $hotArticles = [])
        {
            CategoryStub::$treeFixture = $categories;
            ArticleStub::$hotFixture   = $hotArticles;
        }

        // ============================================================
        // index() — empty keyword branch
        // ============================================================

        /**
         * @test
         * When no keyword is submitted, index() should assign an empty keyword,
         * an empty list, and a count of zero.
         */
        public function index_assignsEmptyListAndZeroCountWhenKeywordIsAbsent()
        {
            // keyword is not in the store → input() returns the default ''
            $this->setSidebarFixtures();

            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            $this->assertArrayHasKey('keyword', $assigns);
            $this->assertSame('', $assigns['keyword'], 'keyword should be empty string when no input');

            $this->assertArrayHasKey('list', $assigns);
            $this->assertSame([], $assigns['list'], 'list should be empty array when keyword is absent');

            $this->assertArrayHasKey('count', $assigns);
            $this->assertSame(0, $assigns['count'], 'count should be 0 when keyword is absent');
        }

        /**
         * @test
         * Explicitly passing an empty string should produce the same empty-keyword result.
         */
        public function index_assignsEmptyListWhenKeywordIsExplicitlyEmptyString()
        {
            InputStore::set('keyword', '');
            $this->setSidebarFixtures();

            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            $this->assertSame('', $assigns['keyword']);
            $this->assertSame([], $assigns['list']);
            $this->assertSame(0, $assigns['count']);
        }

        /**
         * @test
         * A whitespace-only keyword must be trimmed to an empty string by the
         * input('keyword', '', 'trim') call, putting the controller in the
         * empty-keyword branch (no search, list=[], count=0).
         */
        public function index_treatsWhitespaceOnlyKeywordAsEmpty()
        {
            InputStore::set('keyword', '   ');
            $this->setSidebarFixtures();

            // If search() were invoked it would receive the null fixture and
            // calling total() on null would throw — a failure would surface here.
            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            $this->assertSame([], $assigns['list'],
                'Whitespace-only keyword should be treated as empty after trim');
            $this->assertSame(0, $assigns['count'],
                'count should be 0 for whitespace-only keyword');
        }

        /**
         * @test
         * index() must not invoke ArticleModel::search() when the keyword is empty,
         * so the searchFixture remains null and no call to total() is made.
         * (Calling total() on null would throw — the test passing is the assertion.)
         */
        public function index_doesNotCallSearchWhenKeywordIsEmpty()
        {
            InputStore::set('keyword', '');
            // searchFixture intentionally stays null
            $this->setSidebarFixtures();

            // No exception = search() was not called
            $this->controller->index();
            $this->assertTrue(true, 'search() must not be called when keyword is empty');
        }

        // ============================================================
        // index() — non-empty keyword branch
        // ============================================================

        /**
         * @test
         * When a keyword is present, index() should assign the keyword, the
         * paginator returned by search(), and the total from that paginator.
         */
        public function index_assignsKeywordListAndCountWhenKeywordIsPresent()
        {
            $keyword  = 'ThinkPHP';
            $articles = [
                ['id' => 1, 'title' => 'ThinkPHP 入门'],
                ['id' => 2, 'title' => 'ThinkPHP 进阶'],
            ];
            $paginator = new FakePaginator($articles, 2);

            InputStore::set('keyword', $keyword);
            ArticleStub::$searchFixture = $paginator;
            $this->setSidebarFixtures();

            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            $this->assertSame($keyword, $assigns['keyword'],
                'keyword should match the submitted search term');
            $this->assertSame($paginator, $assigns['list'],
                'list should be the paginator returned by ArticleModel::search()');
            $this->assertSame(2, $assigns['count'],
                'count should equal FakePaginator::total()');
        }

        /**
         * @test
         * The count assigned to the view must equal the value returned by
         * $list->total(), not a hard-coded constant.
         */
        public function index_countReflectsPaginatorTotalAccurately()
        {
            InputStore::set('keyword', '新闻');
            ArticleStub::$searchFixture = new FakePaginator([], 42);
            $this->setSidebarFixtures();

            $this->controller->index();

            $this->assertSame(42, $this->controller->getAssigns()['count'],
                'count must precisely equal the total returned by the paginator');
        }

        /**
         * @test
         * When the search returns zero results the count must be 0 (not null or absent).
         */
        public function index_countIsZeroWhenSearchReturnsNoResults()
        {
            InputStore::set('keyword', '无结果关键词');
            ArticleStub::$searchFixture = new FakePaginator([], 0);
            $this->setSidebarFixtures();

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertSame(0, $assigns['count']);
            $this->assertArrayHasKey('list', $assigns);
        }

        /**
         * @test
         * A keyword consisting of a single character is still a valid search term
         * and must not be treated as empty.
         */
        public function index_singleCharacterKeywordTriggersSearch()
        {
            InputStore::set('keyword', 'A');
            $paginator = new FakePaginator([['id' => 5, 'title' => 'Article A']], 1);
            ArticleStub::$searchFixture = $paginator;
            $this->setSidebarFixtures();

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertSame('A', $assigns['keyword']);
            $this->assertSame($paginator, $assigns['list']);
            $this->assertSame(1, $assigns['count']);
        }

        // ============================================================
        // Sidebar data — always assigned regardless of keyword
        // ============================================================

        /**
         * @test
         * categories must be assigned from CategoryModel::getTree(0) in every request,
         * even when the keyword is empty.
         */
        public function index_assignsCategoriesFromModelWhenKeywordIsEmpty()
        {
            InputStore::set('keyword', '');
            $categories = [
                ['id' => 1, 'name' => '科技', 'status' => 1],
                ['id' => 2, 'name' => '体育', 'status' => 1],
            ];
            $this->setSidebarFixtures($categories, []);

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertArrayHasKey('categories', $assigns);
            $this->assertSame($categories, $assigns['categories']);
        }

        /**
         * @test
         * categories must be assigned from CategoryModel::getTree(0) when a keyword
         * is present too.
         */
        public function index_assignsCategoriesFromModelWhenKeywordIsPresent()
        {
            $categories = [['id' => 3, 'name' => '娱乐', 'status' => 1]];
            InputStore::set('keyword', '娱乐圈');
            ArticleStub::$searchFixture = new FakePaginator([], 0);
            $this->setSidebarFixtures($categories, []);

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertSame($categories, $assigns['categories']);
        }

        /**
         * @test
         * hotArticles must be assigned from ArticleModel::getHot(10) in every request,
         * even when the keyword is empty.
         */
        public function index_assignsHotArticlesFromModelWhenKeywordIsEmpty()
        {
            InputStore::set('keyword', '');
            $hotArticles = [
                ['id' => 10, 'title' => '热门文章甲'],
                ['id' => 11, 'title' => '热门文章乙'],
            ];
            $this->setSidebarFixtures([], $hotArticles);

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertArrayHasKey('hotArticles', $assigns);
            $this->assertSame($hotArticles, $assigns['hotArticles']);
        }

        /**
         * @test
         * hotArticles must be assigned from ArticleModel::getHot(10) when a keyword
         * is present too.
         */
        public function index_assignsHotArticlesFromModelWhenKeywordIsPresent()
        {
            $hotArticles = [['id' => 20, 'title' => '爆款文章']];
            InputStore::set('keyword', '爆款');
            ArticleStub::$searchFixture = new FakePaginator([], 0);
            $this->setSidebarFixtures([], $hotArticles);

            $this->controller->index();

            $assigns = $this->controller->getAssigns();
            $this->assertSame($hotArticles, $assigns['hotArticles']);
        }

        /**
         * @test
         * index() must assign all five required template variables in a single call
         * (keyword, list, count, categories, hotArticles).
         */
        public function index_assignsAllFiveRequiredVariablesWhenKeywordIsEmpty()
        {
            InputStore::set('keyword', '');
            $this->setSidebarFixtures(
                [['id' => 1, 'name' => '财经']],
                [['id' => 99, 'title' => '热门']]
            );

            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            foreach (['keyword', 'list', 'count', 'categories', 'hotArticles'] as $key) {
                $this->assertArrayHasKey($key, $assigns,
                    "Expected '{$key}' to be assigned to the view");
            }
        }

        /**
         * @test
         * index() must assign all five required template variables when a keyword
         * triggers a search.
         */
        public function index_assignsAllFiveRequiredVariablesWhenKeywordIsPresent()
        {
            InputStore::set('keyword', '搜索词');
            ArticleStub::$searchFixture = new FakePaginator([], 0);
            $this->setSidebarFixtures(
                [['id' => 2, 'name' => '国际']],
                [['id' => 88, 'title' => '热门国际']]
            );

            $this->controller->index();

            $assigns = $this->controller->getAssigns();

            foreach (['keyword', 'list', 'count', 'categories', 'hotArticles'] as $key) {
                $this->assertArrayHasKey($key, $assigns,
                    "Expected '{$key}' to be assigned to the view");
            }
        }

        // ============================================================
        // Return value
        // ============================================================

        /**
         * @test
         * index() must return the string produced by fetch() (the rendered view).
         */
        public function index_returnsFetchOutputAsString()
        {
            InputStore::set('keyword', '');
            $this->setSidebarFixtures();

            $result = $this->controller->index();

            $this->assertIsString($result,
                'index() should return a string (the rendered template)');
        }

        /**
         * @test
         * The return value of index() is a string regardless of whether a keyword
         * was supplied.
         */
        public function index_returnsFetchOutputWhenKeywordIsPresent()
        {
            InputStore::set('keyword', '框架');
            ArticleStub::$searchFixture = new FakePaginator([], 1);
            $this->setSidebarFixtures();

            $result = $this->controller->index();

            $this->assertIsString($result);
        }
    }
}

// ====================================================================
// SECTION 5 – Load the real controller under test
// Must come AFTER all stubs so PHP's class loader finds the stubs first.
// ====================================================================

namespace {
    require_once __DIR__ . '/../../../application/index/controller/Search.php';
}
