<?php
// 命名空间声明
namespace app\admin\controller;

// 引入 ThinkPHP 控制器基类
use think\Controller;
// 引入文章模型
use app\common\model\Article as ArticleModel;
// 引入分类模型
use app\common\model\Category as CategoryModel;
// 引入 Session 类
use think\Session;
// 引入 Request 类
use think\Request;

// 文章管理控制器（继承后台基类）
class Article extends Base
{
    // 前置操作方法列表
    protected $beforeActionList = [
        // 所有操作前都执行登录检查
        'checkLogin' => ['']
    ];

    // 检查登录状态
    protected function checkLogin()
    {
        // 判断 Session 中是否存在管理员 ID
        if (!Session::get('admin_id')) {
            // 未登录则重定向到登录页面
            $this->redirect('/admin/login');
        }
    }

    // 文章列表
    // 文章列表页
    public function index()
    {
        // 初始化查询条件数组
        $where = [];
        // 获取状态筛选参数
        $status = input('status', '');
        // 获取分类筛选参数并转为整数
        $cid = input('cid', 0, 'intval');
        // 获取关键词参数并去除首尾空格
        $keyword = input('keyword', '', 'trim');

        // 判断状态参数不为空
        if ($status !== '') {
            // 将状态加入查询条件
            $where['status'] = $status;
        }
        // 判断分类 ID 大于 0
        if ($cid > 0) {
            // 将分类加入查询条件
            $where['category_id'] = $cid;
        }
        // 判断关键词不为空
        if ($keyword) {
            // 设置标题模糊查询条件
            $where['title'] = ['like', "%{$keyword}%"];
        }

        // 关联分类和作者模型进行查询
        $list = ArticleModel::with(['category', 'author'])
            // 应用查询条件
            ->where($where)
            // 按 ID 降序排列
            ->order('id desc')
            // 分页，每页 10 条
            ->paginate(10);

        // 将文章列表赋值到模板
        $this->assign('list', $list);
        // 将分类树赋值到模板
        $this->assign('categories', CategoryModel::getTree(0));
        // 将状态筛选值赋值到模板
        $this->assign('status', $status);
        // 将分类筛选值赋值到模板
        $this->assign('cid', $cid);
        // 将关键词赋值到模板
        $this->assign('keyword', $keyword);

        // 渲染模板并返回
        return $this->fetch();
    }

    // 添加文章
    // 添加文章方法（支持页面渲染和表单提交）
    public function add()
    {
        // 判断是否为 POST 请求
        if (request()->isPost()) {
            // 组装文章数据
            $data = [
                // 获取标题并去除首尾空格
                'title' => input('post.title', '', 'trim'),
                // 获取摘要并去除首尾空格
                'summary' => input('post.summary', '', 'trim'),
                // 获取正文内容
                'content' => input('post.content', '', ''),
                // 获取封面图地址并去除首尾空格
                'cover' => input('post.cover', '', 'trim'),
                // 获取分类 ID 并转为整数
                'category_id' => input('post.category_id', 0, 'intval'),
                // 设置作者为当前登录管理员
                'author_id' => Session::get('admin_id'),
                // 获取发布状态并转为整数
                'status' => input('post.status', 0, 'intval'),
                // 获取是否置顶并转为整数
                'is_top' => input('post.is_top', 0, 'intval'),
                // 获取是否推荐并转为整数
                'is_recommend' => input('post.is_recommend', 0, 'intval'),
            ];

            // 判断标题是否为空
            if (empty($data['title'])) {
                // 返回标题不能为空提示
                return json(['code' => 0, 'msg' => '标题不能为空']);
            }

            // 判断状态是否为发布
            if ($data['status'] == 1) {
                // 设置发布时间为当前时间戳
                $data['publish_time'] = time();
            }

            // 创建文章记录
            $result = ArticleModel::create($data);

            // 判断创建是否成功
            if ($result) {
                // 返回添加成功提示
                return json(['code' => 1, 'msg' => '添加成功']);
            } else {
                // 返回添加失败提示
                return json(['code' => 0, 'msg' => '添加失败']);
            }
        }

        // 将分类树赋值到模板（用于下拉选择）
        $this->assign('categories', CategoryModel::getTree(0));
        // 渲染添加页面模板
        return $this->fetch();
    }

    // 编辑文章
    // 编辑文章方法（支持页面渲染和表单提交）
    public function edit()
    {
        // 尝试从 POST 中获取文章 ID
        $id = input('post.id', 0, 'intval');
        // 如果 POST 中没有 ID，则从 GET 中获取
        if (!$id) {
            $id = input('get.id', 0, 'intval');
        }

        // 判断是否为 POST 请求
        if (request()->isPost()) {
            // 组装更新数据
            $data = [
                // 获取标题并去除首尾空格
                'title' => input('post.title', '', 'trim'),
                // 获取摘要并去除首尾空格
                'summary' => input('post.summary', '', 'trim'),
                // 获取正文内容
                'content' => input('post.content', '', ''),
                // 获取封面图地址并去除首尾空格
                'cover' => input('post.cover', '', 'trim'),
                // 获取分类 ID 并转为整数
                'category_id' => input('post.category_id', 0, 'intval'),
                // 获取发布状态并转为整数
                'status' => input('post.status', 0, 'intval'),
                // 获取是否置顶并转为整数
                'is_top' => input('post.is_top', 0, 'intval'),
                // 获取是否推荐并转为整数
                'is_recommend' => input('post.is_recommend', 0, 'intval'),
            ];

            // 判断标题是否为空
            if (empty($data['title'])) {
                // 返回标题不能为空提示
                return json(['code' => 0, 'msg' => '标题不能为空']);
            }

            // 如果设置为发布状态且之前未发布
            // 获取当前文章数据
            $article = ArticleModel::get($id);
            // 判断本次改为发布且之前不是发布状态
            if ($data['status'] == 1 && $article['status'] != 1) {
                // 更新发布时间为当前时间戳
                $data['publish_time'] = time();
            }

            // 根据 ID 更新文章数据
            $result = ArticleModel::where('id', $id)->update($data);

            // 判断更新是否成功（update 返回影响行数或 false）
            if ($result !== false) {
                // 返回更新成功提示
                return json(['code' => 1, 'msg' => '更新成功']);
            } else {
                // 返回更新失败提示
                return json(['code' => 0, 'msg' => '更新失败']);
            }
        }

        // 根据 ID 获取文章数据
        $article = ArticleModel::get($id);
        // 判断文章是否存在
        if (!$article) {
            // 文章不存在时提示错误
            $this->error('文章不存在');
        }

        // 将文章数据赋值到模板
        $this->assign('article', $article);
        // 将分类树赋值到模板
        $this->assign('categories', CategoryModel::getTree(0));
        // 渲染编辑页面模板
        return $this->fetch();
    }

    // 删除文章
    // 删除单篇文章方法
    public function delete()
    {
        // 获取要删除的文章 ID
        $id = input('post.id', 0, 'intval');

        // 执行删除操作
        $result = ArticleModel::destroy($id);

        // 判断删除是否成功
        if ($result) {
            // 返回删除成功提示
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            // 返回删除失败提示
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }

    // 批量删除
    // 批量删除文章方法
    public function batchDelete()
    {
        // 获取要删除的文章 ID 字符串（逗号分隔）
        $ids = input('post.ids', '');

        // 判断 ID 字符串是否为空
        if (empty($ids)) {
            // 返回未选择文章的提示
            return json(['code' => 0, 'msg' => '请选择要删除的文章']);
        }

        // 将逗号分隔的字符串转为数组
        $idArr = explode(',', $ids);
        // 执行批量删除操作
        $result = ArticleModel::destroy($idArr);

        // 判断删除是否成功
        if ($result) {
            // 返回删除成功提示
            return json(['code' => 1, 'msg' => '删除成功']);
        } else {
            // 返回删除失败提示
            return json(['code' => 0, 'msg' => '删除失败']);
        }
    }
}
