-- ============================================
-- 综合新闻网站数据库结构
-- 版本: 2.0
-- 日期: 2026-04-07
-- 技术栈: ThinkPHP5 + MySQL
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户表
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '用户ID',
    `username` VARCHAR(50) UNIQUE NOT NULL COMMENT '用户名',
    `email` VARCHAR(100) UNIQUE COMMENT '邮箱',
    `phone` VARCHAR(20) UNIQUE COMMENT '手机号',
    `password` VARCHAR(255) NOT NULL COMMENT '密码(加密)',
    `nickname` VARCHAR(50) COMMENT '昵称',
    `avatar` VARCHAR(255) COMMENT '头像URL',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0-禁用 1-正常',
    `is_admin` TINYINT DEFAULT 0 COMMENT '是否管理员: 0-普通用户 1-管理员',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_phone` (`phone`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- 分类表
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '分类ID',
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父级ID',
    `sort` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_parent` (`parent_id`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类表';

-- ----------------------------
-- 文章表
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '文章ID',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `summary` VARCHAR(500) COMMENT '摘要',
    `content` LONGTEXT COMMENT '文章内容',
    `cover` VARCHAR(255) COMMENT '封面图URL',
    `author_id` INT UNSIGNED NOT NULL COMMENT '作者ID',
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `status` TINYINT DEFAULT 0 COMMENT '状态: 0-草稿 1-已发布 2-已下线',
    `is_top` TINYINT DEFAULT 0 COMMENT '是否置顶: 0-普通 1-置顶',
    `is_recommend` TINYINT DEFAULT 0 COMMENT '是否推荐: 0-普通 1-推荐',
    `views` INT UNSIGNED DEFAULT 0 COMMENT '浏览量',
    `likes` INT UNSIGNED DEFAULT 0 COMMENT '点赞数',
    `comments` INT UNSIGNED DEFAULT 0 COMMENT '评论数',
    `publish_time` INT UNSIGNED DEFAULT 0 COMMENT '发布时间',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_top` (`is_top`),
    KEY `idx_recommend` (`is_recommend`),
    KEY `idx_publish_time` (`publish_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- 评论表
-- ----------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '评论ID',
    `article_id` INT UNSIGNED NOT NULL COMMENT '文章ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父评论ID(回复)',
    `content` TEXT NOT NULL COMMENT '评论内容',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0-隐藏 1-显示',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_article` (`article_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论表';

-- ----------------------------
-- 轮播图表
-- ----------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '轮播ID',
    `title` VARCHAR(200) COMMENT '标题',
    `image` VARCHAR(255) NOT NULL COMMENT '图片URL',
    `url` VARCHAR(500) COMMENT '跳转链接',
    `sort` INT DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_sort` (`sort`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='轮播图表';

-- ----------------------------
-- 系统配置表
-- ----------------------------
DROP TABLE IF EXISTS `configs`;
CREATE TABLE `configs` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    `name` VARCHAR(50) NOT NULL COMMENT '配置名',
    `value` TEXT COMMENT '配置值',
    `group` VARCHAR(50) DEFAULT 'base' COMMENT '分组',
    `create_time` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- 初始数据
-- ----------------------------

-- 插入管理员账号 (密码: admin123)
INSERT INTO `users` (`username`, `password`, `nickname`, `email`, `status`, `is_admin`, `create_time`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', 'admin@example.com', 1, 1, UNIX_TIMESTAMP());

-- 插入测试用户
INSERT INTO `users` (`username`, `password`, `nickname`, `email`, `phone`, `status`, `create_time`) VALUES
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '测试用户', 'test@example.com', '13800138000', 1, UNIX_TIMESTAMP());

-- 插入文章分类
INSERT INTO `categories` (`name`, `parent_id`, `sort`, `status`, `create_time`) VALUES
('国内新闻', 0, 1, 1, UNIX_TIMESTAMP()),
('国际新闻', 0, 2, 1, UNIX_TIMESTAMP()),
('科技资讯', 0, 3, 1, UNIX_TIMESTAMP()),
('财经频道', 0, 4, 1, UNIX_TIMESTAMP()),
('体育竞技', 0, 5, 1, UNIX_TIMESTAMP()),
('娱乐八卦', 0, 6, 1, UNIX_TIMESTAMP()),
('汽车之家', 0, 7, 1, UNIX_TIMESTAMP()),
('房产家居', 0, 8, 1, UNIX_TIMESTAMP());

-- 插入测试文章
INSERT INTO `articles` (`title`, `summary`, `content`, `cover`, `author_id`, `category_id`, `status`, `is_top`, `is_recommend`, `views`, `likes`, `publish_time`, `create_time`) VALUES
('热烈祝贺本站正式上线', '经过紧张有序的开发与测试，综合新闻网站今日正式上线！', '<p>经过紧张有序的开发与测试，综合新闻网站今日正式上线！</p><p>本站将为大家提供最新、最全面的新闻资讯服务。</p>', '/static/images/default-cover.jpg', 1, 1, 1, 1, 1, 100, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('科技改变生活：人工智能引领未来', '人工智能技术的快速发展正在深刻改变我们的生活方式', '<p>人工智能（AI）技术的快速发展正在深刻改变我们的生活方式。</p><p>从智能家居到自动驾驶，从医疗诊断到金融风控，AI的应用场景越来越广泛。</p>', '/static/images/default-cover.jpg', 1, 3, 1, 0, 1, 85, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('今日A股市场回顾', '今日A股市场走势平稳，多个板块表现活跃', '<p>今日A股市场走势平稳，多个板块表现活跃。</p><p>科技股和新能源板块持续受到资金追捧。</p>', '/static/images/default-cover.jpg', 1, 4, 1, 0, 0, 60, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 插入轮播图
INSERT INTO `banners` (`title`, `image`, `url`, `sort`, `status`, `create_time`) VALUES
('欢迎访问新闻网站', '/static/images/banner1.jpg', '/', 1, 1, UNIX_TIMESTAMP()),
('最新科技资讯', '/static/images/banner2.jpg', '/category/3', 2, 1, UNIX_TIMESTAMP()),
('财经热点解读', '/static/images/banner3.jpg', '/category/4', 3, 1, UNIX_TIMESTAMP());

-- 插入系统配置
INSERT INTO `configs` (`name`, `value`, `group`, `create_time`) VALUES
('site_name', '综合新闻网站', 'base', UNIX_TIMESTAMP()),
('site_title', '综合新闻网站 - 实时资讯 深度报道', 'base', UNIX_TIMESTAMP()),
('site_keywords', '新闻,资讯,国内新闻,国际新闻,科技,财经', 'seo', UNIX_TIMESTAMP()),
('site_description', '专业的综合性新闻门户网站，提供实时新闻资讯、深度报道等服务', 'seo', UNIX_TIMESTAMP()),
('site_icp', '京ICP备XXXXXXXX号', 'base', UNIX_TIMESTAMP()),
('contact_email', 'contact@example.com', 'contact', UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
