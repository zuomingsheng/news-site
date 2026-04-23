# 综合新闻门户网站

基于 **ThinkPHP 5.0** 开发的综合新闻门户网站，包含前台新闻展示和后台内容管理系统（CMS）。

---

## 功能特性

### 前台模块

- **首页**：轮播焦点图、热点新闻、分类导航
- **文章详情**：富文本内容、浏览量统计、上一篇/下一篇、相关文章推荐
- **文章列表**：分类筛选、分页展示、置顶/推荐标识
- **搜索**：标题/内容关键字全文检索
- **评论互动**：文章评论、嵌套回复
- **用户系统**：注册、登录、个人中心

### 后台模块

- **仪表盘**：数据统计概览
- **文章管理**：发布、编辑、上下架、富文本编辑
- **分类管理**：多级分类体系、排序调整
- **用户管理**：用户列表、状态管理
- **评论管理**：评论审核、回复、删除
- **轮播管理**：首页焦点图配置
- **系统设置**：站点配置、SEO 设置

---

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 5.0 |
| 数据库 | MySQL 5.7+ |
| 前端 | jQuery 3.x + Bootstrap 5 |
| 模板引擎 | ThinkPHP 内置 |
| 认证方式 | Session |

---

## 环境要求

- PHP >= 5.4
- MySQL >= 5.7
- Apache / Nginx
- PDO PHP 扩展
- OpenSSL PHP 扩展

---

## 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/zuomingsheng/news-site.git
cd news-site
```

### 2. 安装依赖

```bash
composer install
```

### 3. 配置数据库

```bash
# 复制示例配置文件
cp application/database.example.php application/database.php

# 编辑 database.php，填入你的数据库信息
```

### 4. 导入数据库

```bash
mysql -u root -p < news_site.sql
```

### 5. 启动服务

```bash
# PHP 内置服务器
cd public && php -S localhost:8888

# 或 ThinkPHP CLI
php think run
```

### 6. 访问项目

- 前台首页：http://localhost:8888
- 后台登录：http://localhost:8888/admin/login

---

## 项目结构

```
.
├── application/           # 应用目录
│   ├── common/            # 公共模块（模型）
│   │   └── model/         # Article, Category, User, Comment, Banner, Config
│   ├── index/             # 前台模块
│   │   ├── controller/    # Index, Article, User, Comment, Search
│   │   └── view/          # 前台模板
│   ├── admin/             # 后台模块
│   │   ├── controller/    # Auth, Index, Article, Category, User, Comment, Banner, System
│   │   ├── view/          # 后台模板
│   │   └── paginator/     # 自定义分页器
│   ├── database.php       # 数据库配置（需手动创建）
│   └── route.php          # 路由规则
├── public/                # Web 根目录
│   ├── static/            # CSS / JS / 图片
│   └── uploads/           # 上传文件
├── thinkphp/              # ThinkPHP 框架核心
├── news_site.sql          # 数据库结构
├── composer.json
└── README.md
```

---

## 核心路由

| URL | 说明 |
|-----|------|
| `/` | 前台首页 |
| `article/:id` | 文章详情 |
| `category/:cid` | 分类列表 |
| `article` | 文章列表 |
| `search` | 搜索 |
| `admin/login` | 后台登录 |

---

## 数据库配置说明

项目中的 `application/database.php` 已被加入 `.gitignore`，不会提交到版本控制。

首次使用时，请复制 `application/database.example.php` 为 `application/database.php`，并修改其中的数据库连接信息。

---

## 开发规范

- 遵循 ThinkPHP 5 编码规范
- 模型统一放在 `application/common/model/`
- 所有后台控制器继承 `Base` 基类进行权限校验
- 上传文件统一存放到 `/public/uploads/`

---

## License

[Apache-2.0](http://www.apache.org/licenses/LICENSE-2.0)
