<?php
namespace tests\index\controller;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use think\Session;
use think\Request;

/**
 * User Controller Unit Tests
 *
 * Tests cover: login, doLogin, register, doRegister, logout, center, checkLogin
 */
class UserTest extends TestCase
{
    /** @var \app\index\controller\User */
    protected $controller;

    /** @var MockObject */
    protected $mockUserModel;

    /** @var MockObject */
    protected $mockRequest;

    /** @var array  */
    protected $sessionBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize session for tests
        if (!Session::init()) {
            Session::start();
        }
        Session::clear();

        // Mock UserModel static methods
        $this->mockUserModel = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find', 'create'])
            ->getMock();

        // Mock Request object
        $this->mockRequest = $this->getMockBuilder('think\Request')
            ->setMethods(['isPost', 'param', 'header', 'url'])
            ->getMock();
    }

    protected function tearDown(): void
    {
        Session::clear();
        parent::tearDown();
    }

    /**
     * Helper: Create controller instance with mocked request
     */
    protected function createControllerWithRequest()
    {
        // Register our mock request as the current request
        \think\App::bind('think\Request', $this->mockRequest);

        $controller = new \app\index\controller\User();
        return $controller;
    }

    // ─────────────────────────────────────────────────────────────
    // login()
    // ─────────────────────────────────────────────────────────────

    public function testLoginReturnsFetchTemplate()
    {
        $controller = new \app\index\controller\User();
        $result = $controller->login();

        // fetch() should return a View object with template content
        $this->assertInstanceOf('think\response\View', $result);
    }

    // ─────────────────────────────────────────────────────────────
    // doLogin()
    // ─────────────────────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginRejectsNonPostRequest()
    {
        $this->mockRequest->method('isPost')->willReturn(false);

        // Suppress output since error() calls halt()
        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('请求方式错误');

        \think\App::bind('think\Request', $this->mockRequest);
        $controller = new \app\index\controller\User();
        $controller->doLogin();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginRejectsEmptyCredentials()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                return $default;
            });

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('用户名和密码不能为空');

        \think\App::bind('think\Request', $this->mockRequest);
        $controller = new \app\index\controller\User();
        $controller->doLogin();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginRejectsNonexistentUser()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'ghost_user',
                    'post.password' => 'anypass',
                ];
                return $map[$key] ?? $default;
            });

        // UserModel::where(...)->find() returns null
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'findOrFail', 'find'])
            ->getMock();
        $userMock->method('where')->willReturnSelf();
        $userMock->method('find')->willReturn(null);

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('用户不存在');

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $controller = new \app\index\controller\User();
        $controller->doLogin();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginRejectsDisabledAccount()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'disabled_user',
                    'post.password' => 'pass123',
                ];
                return $map[$key] ?? $default;
            });

        $disabledUser = [
            'id' => 5,
            'username' => 'disabled_user',
            'password' => password_hash('pass123', PASSWORD_DEFAULT),
            'status' => 0,   // disabled
            'is_admin' => 0,
            'nickname' => 'Disabled',
        ];

        // Patch where/find to return the disabled user
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();
        $userMock->method('where')->willReturnSelf();
        $userMock->method('find')->willReturn($disabledUser);

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('账号已被禁用');

        $controller = new \app\index\controller\User();
        $controller->doLogin();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginRejectsWrongPassword()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'real_user',
                    'post.password' => 'wrong_password',
                ];
                return $map[$key] ?? $default;
            });

        $validUser = [
            'id' => 1,
            'username' => 'real_user',
            'password' => password_hash('correct_password', PASSWORD_DEFAULT),
            'status' => 1,
            'is_admin' => 0,
            'nickname' => 'Real User',
        ];

        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();
        $userMock->method('where')->willReturnSelf();
        $userMock->method('find')->willReturn($validUser);

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('密码错误');

        $controller = new \app\index\controller\User();
        $controller->doLogin();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoLoginSuccessSetsSession()
    {
        $rawPassword = 'valid_password_123';
        $validUser = [
            'id' => 10,
            'username' => 'testuser',
            'password' => password_hash($rawPassword, PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'phone' => null,
            'status' => 1,
            'is_admin' => 1,
            'nickname' => 'Test User',
        ];

        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) use ($rawPassword) {
                $map = [
                    'post.username' => 'testuser',
                    'post.password' => $rawPassword,
                ];
                return $map[$key] ?? $default;
            });

        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();
        $userMock->method('where')->willReturnSelf();
        $userMock->method('find')->willReturn($validUser);

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        // The exception carries the success redirect response
        try {
            $controller = new \app\index\controller\User();
            $controller->doLogin();
        } catch (\think\exception\HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf('think\response\Redirect', $response);
            $responseData = $response->getData();
            $this->assertEquals('登录成功', $responseData['msg']);
            $this->assertEquals('/', $responseData['url']);

            // Verify session values
            $this->assertEquals(10, Session::get('user_id'));
            $this->assertEquals('testuser', Session::get('username'));
            $this->assertEquals('Test User', Session::get('nickname'));
            $this->assertEquals(1, Session::get('is_admin'));
            throw $e; // Re-throw to satisfy PHPUnit
        }
    }

    // ─────────────────────────────────────────────────────────────
    // register()
    // ─────────────────────────────────────────────────────────────

    public function testRegisterReturnsFetchTemplate()
    {
        $controller = new \app\index\controller\User();
        $result = $controller->register();

        $this->assertInstanceOf('think\response\View', $result);
    }

    // ─────────────────────────────────────────────────────────────
    // doRegister()
    // ─────────────────────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsNonPostRequest()
    {
        $this->mockRequest->method('isPost')->willReturn(false);

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('请求方式错误');

        \think\App::bind('think\Request', $this->mockRequest);
        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsEmptyUsernameOrPassword()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                return $default;
            });

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('用户名和密码不能为空');

        \think\App::bind('think\Request', $this->mockRequest);
        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsShortPassword()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'newuser',
                    'post.password' => '12345', // less than 6 chars
                    'post.email' => '',
                    'post.phone' => '',
                ];
                return $map[$key] ?? $default;
            });

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('密码长度不能少于6位');

        \think\App::bind('think\Request', $this->mockRequest);
        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsDuplicateUsername()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'taken_user',
                    'post.password' => 'password123',
                    'post.email' => '',
                    'post.phone' => '',
                ];
                return $map[$key] ?? $default;
            });

        // First where('username')->find() returns existing user
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();
        $userMock->method('where')
            ->with('username', 'taken_user')
            ->willReturnSelf();
        $userMock->method('find')->willReturn(['id' => 1, 'username' => 'taken_user']);

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('用户名已存在');

        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsDuplicateEmail()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'newuser',
                    'post.password' => 'password123',
                    'post.email' => 'used@example.com',
                    'post.phone' => '',
                ];
                return $map[$key] ?? $default;
            });

        // Mock: username check passes, email check fails
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();

        $userMock->method('where')
            ->with('username', 'newuser')
            ->willReturnSelf();
        $userMock->method('find')
            ->willReturnOnConsecutiveCalls(
                null,                          // username not taken
                ['id' => 2, 'email' => 'used@example.com'] // email taken
            );

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('邮箱已被注册');

        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterRejectsDuplicatePhone()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'newuser',
                    'post.password' => 'password123',
                    'post.email' => '',
                    'post.phone' => '13800138000',
                ];
                return $map[$key] ?? $default;
            });

        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find'])
            ->getMock();

        $userMock->method('where')
            ->willReturnSelf();
        $userMock->method('find')
            ->willReturnOnConsecutiveCalls(
                null,                                      // username free
                null,                                      // email free
                ['id' => 3, 'phone' => '13800138000']      // phone taken
            );

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('手机号已被注册');

        $controller = new \app\index\controller\User();
        $controller->doRegister();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoRegisterSuccessSetsSessionAndAutoLogin()
    {
        $this->mockRequest->method('isPost')->willReturn(true);
        $this->mockRequest->method('param')
            ->willReturnCallback(function ($key, $default, $filter) {
                $map = [
                    'post.username' => 'brandnew',
                    'post.password' => 'password123',
                    'post.email' => 'new@example.com',
                    'post.phone' => '',
                ];
                return $map[$key] ?? $default;
            });

        // All uniqueness checks pass, create() returns a new user
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['where', 'find', 'create'])
            ->getMock();

        $userMock->method('where')->willReturnSelf();
        $userMock->method('find')->willReturn(null);
        $userMock->method('create')->willReturn([
            'id' => 99,
            'username' => 'brandnew',
            'password' => 'password123',
            'email' => 'new@example.com',
            'phone' => null,
            'status' => 1,
            'is_admin' => 0,
            'nickname' => 'brandnew',
        ]);

        \think\App::bind('think\Request', $this->mockRequest);
        \think\Db::bind('app\common\model\User', get_class($userMock));

        $this->expectException('think\exception\HttpResponseException');

        try {
            $controller = new \app\index\controller\User();
            $controller->doRegister();
        } catch (\think\exception\HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf('think\response\Redirect', $response);
            $responseData = $response->getData();
            $this->assertEquals('注册成功', $responseData['msg']);
            $this->assertEquals('/', $responseData['url']);

            $this->assertEquals(99, Session::get('user_id'));
            $this->assertEquals('brandnew', Session::get('username'));
            $this->assertEquals('brandnew', Session::get('nickname'));
            $this->assertEquals(0, Session::get('is_admin'));
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // logout()
    // ─────────────────────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLogoutClearsSessionAndRedirects()
    {
        // Pre-condition: user is logged in
        Session::set('user_id', 42);
        Session::set('username', 'logout_user');
        Session::set('nickname', 'Logout User');
        Session::set('is_admin', 0);

        $controller = new \app\index\controller\User();

        $this->expectException('think\exception\HttpResponseException');

        try {
            $controller->logout();
        } catch (\think\exception\HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf('think\response\Redirect', $response);
            $responseData = $response->getData();
            $this->assertEquals('已退出登录', $responseData['msg']);
            $this->assertEquals('/', $responseData['url']);

            // Session should be cleared
            $this->assertNull(Session::get('user_id'));
            $this->assertNull(Session::get('username'));
            $this->assertNull(Session::get('nickname'));
            $this->assertNull(Session::get('is_admin'));
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // center()
    // ─────────────────────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCenterRejectsUnauthenticatedUser()
    {
        // No session set
        $this->assertNull(Session::get('user_id'));

        $controller = new \app\index\controller\User();

        $this->expectException('think\exception\HttpResponseException');
        $this->expectExceptionMessage('请先登录');

        $controller->center();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCenterReturnsViewWithUserData()
    {
        $fixtureUser = [
            'id' => 7,
            'username' => 'center_user',
            'email' => 'center@example.com',
            'phone' => '13900001111',
            'nickname' => 'Center User',
            'status' => 1,
            'is_admin' => 0,
            'avatar' => null,
        ];

        Session::set('user_id', 7);

        // Mock UserModel::get()
        $userMock = $this->getMockBuilder('app\common\model\User')
            ->setMethods(['get'])
            ->getMock();
        $userMock->method('get')->with(7)->willReturn($fixtureUser);

        \think\Db::bind('app\common\model\User', get_class($userMock));

        $controller = new \app\index\controller\User();
        $result = $controller->center();

        $this->assertInstanceOf('think\response\View', $result);

        // Verify user data was assigned to the view
        $this->assertEquals($fixtureUser, $result->getData('user'));
    }

    // ─────────────────────────────────────────────────────────────
    // checkLogin()
    // ─────────────────────────────────────────────────────────────

    public function testCheckLoginReturnsNotLoggedInWhenNoSession()
    {
        Session::clear();

        $controller = new \app\index\controller\User();
        $result = $controller->checkLogin();

        $this->assertInstanceOf('think\response\Json', $result);

        $json = json_decode($result->getContent(), true);
        $this->assertEquals(0, $json['code']);
        $this->assertEquals('未登录', $json['msg']);
        $this->assertArrayNotHasKey('data', $json);
    }

    public function testCheckLoginReturnsLoggedInWithUserData()
    {
        Session::set('user_id', 55);
        Session::set('username', 'api_user');
        Session::set('nickname', 'API User');
        Session::set('is_admin', 0);

        $controller = new \app\index\controller\User();
        $result = $controller->checkLogin();

        $this->assertInstanceOf('think\response\Json', $result);

        $json = json_decode($result->getContent(), true);
        $this->assertEquals(1, $json['code']);
        $this->assertEquals('已登录', $json['msg']);
        $this->assertArrayHasKey('data', $json);
        $this->assertEquals(55, $json['data']['user_id']);
        $this->assertEquals('api_user', $json['data']['username']);
        $this->assertEquals('API User', $json['data']['nickname']);
    }
}
