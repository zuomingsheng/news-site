# CLAUDE.md

本文件为 Claude Code (claude.ai/code) 在此代码仓库中工作时提供指导。

## 项目概述

这是一个基于 **ThinkPHP 5.0 的综合新闻门户网站**（综合新闻网站），包含前台（`index` 模块）和后台管理（`admin` 模块），主数据库为 MySQL。

**关键参考文件：**
- 数据库结构：`news_site.sql`
- 技术方案文档：`plan.md`
- 数据库配置：`application/database.php`

---

## 常用命令

### 运行应用

```bash
# 启动 PHP 内置服务器（从 public/ 目录执行）
php -S localhost:8888 -t public

# 或使用 CLI 入口
php think run
```

### 数据库初始化

```bash
# 导入数据库结构（MySQL）
mysql -u root -p < news_site.sql
```

### 安装 Composer 依赖

```bash
composer install
```

---

## 架构

### 模块结构

```
application/
├── common/               # 所有模块共享的公共模块
│   └── model/            # Article, Category, User, Comment, Banner, Config
├── index/                # 前台（公开访问的网站）
│   ├── controller/        # Index, Article, User, Comment, Search
│   ├── view/              # 模板文件（.html）
│   └── exception/         # 自定义异常处理器
├── admin/                 # 后台管理（CMS）
│   ├── controller/        # Base, Auth, Index, Article, Category, User, Comment, Banner, System, Upload
│   ├── view/              # 后台模板
│   ├── paginator/         # 自定义 Bootstrap 分页器
│   └── command/           # CLI 命令（InitAdmin）
├── config.php             # 全局应用配置
├── database.php           # 数据库连接配置
└── route.php              # URL 路由规则
```

### 请求流程

1. 所有请求通过 `public/index.php` 入口，由 `thinkphp/` 加载 ThinkPHP 框架
2. `application/route.php` 中的路由将 URL 映射到对应的控制器和操作
3. 控制器返回视图（`.html` 模板）或 JSON 响应
4. `application/common/model/` 中的模型负责数据库交互


### 核心路由

| URL | 控制器 | 说明 |
|-----|--------|------|
| `/` | `index/Index` | 首页 |
| `article/:id` | `index/Article/detail` | 文章详情 |
| `category/:cid` | `index/Index/category` | 分类列表 |
| `article` | `index/Article/lists` | 文章列表 |
| `search` | `index/Search/index` | 搜索结果 |
| `admin/login` | `admin/Auth/login` | 后台登录 |
| `admin/*` | `admin/*` | 其他后台路由 |

注意：不要修改route.php里面的内容以及nginx相关配置

### 模型

所有模型均位于 `application/common/model/`，遵循 ThinkPHP ORM 规范：
- `Article` — 文章内容，含 `status`（0=草稿, 1=已发布, 2=已下线）、`is_top`、`is_recommend`
- `Category` — 分类，通过 `parent_id` 实现层级结构
- `User` — 用户账户，含 `is_admin` 标识
- `Comment` — 评论，通过 `parent_id` 支持嵌套回复
- `Banner` — 首页轮播图
- `Config` — 键值对形式的站点配置

### 后台认证

后台路由受 `application/admin/controller/Auth.php` 保护，所有后台控制器继承 `Base.php`，由 `Base` 校验管理员会话。

### 异常处理

自定义异常处理器注册在 `application/config.php` 中：
```php
'exception_handle' => 'app\index\exception\ExceptionHandler'
```

### 静态资源

所有静态文件（CSS、JS、图片）位于 `public/static/`，上传文件存放在 `public/uploads/`。

### 视图模板

前台视图：`application/index/view/`（后缀为 `.html`，在 `config.php` 中配置）
后台视图：`application/admin/view/`

### 配置优先级

ThinkPHP 按以下顺序合并配置（后者覆盖前者）：
`thinkphp/convention.php` → `application/config.php` → `application/extra/*.php` → 模块 `config.php`

---

## 重要模式

### 后台控制器模式

```php
// 所有后台控制器继承 Base，由 Base 校验权限
class Article extends Base { }

// Auth 控制器处理登录/登出及会话
class Auth extends Controller {
    public function login() { /* 设置 session */ }
}
```

### 格式化输出（Jump 跳转模板）

成功/错误的页面跳转使用 `application/index/view/public/jump.html`，而非原始 JSON。

### 分页配置

全局分页设置在 `config.php` 中：
```php
'paginate' => [
    'type'      => 'app\admin\paginator\Bootstrap',
    'var_page'  => 'page',
    'list_rows' => 10,
]
```

---

## 技术栈

- **框架：** ThinkPHP 5.0.x
- **PHP：** >= 5.4
- **数据库：** MySQL 5.7+
- **前端：** jQuery 3.x + Bootstrap 5
- **模板引擎：** ThinkPHP 内置（view_suffix: html）
- **认证方式：** 基于 Session（无 JWT）
