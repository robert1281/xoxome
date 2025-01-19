# Cursor中文破解论坛
演示站点 https://xmyy.shop
cursor突破50次限制、cursor突破免费版显示 https://xoxome.online
一个具有黑客风格的技术交流论坛系统。

## 功能特点

- 用户系统
  - 注册、登录、登出
  - 用户名高亮显示(粉色)
  - 三级权限控制(游客、用户、管理员)

- 帖子系统
  - 发布帖子
  - 帖子列表(分页、置顶)
  - 帖子详情
  - 帖子审核
  - 帖子管理(删除、置顶)

- 评论系统
  - 发表评论
  - 评论列表
  - 评论审核
  - 评论管理

- 管理后台
  - 帖子管理
  - 评论管理
  - 系统设置

## 系统要求

- PHP 7.4+
- MySQL 5.7+
- Web服务器(Apache/Nginx)

## 安装步骤

1. 创建数据库
```sql
CREATE DATABASE cursordiscuss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. 导入数据库结构
```bash
mysql -u用户名 -p密码 cursordiscuss < database.sql
```

3. 修改配置文件
编辑 `config.php` 文件,修改数据库连接信息:
```php
define('DB_HOST', '数据库服务器地址');
define('DB_USER', '数据库用户名');
define('DB_PASS', '数据库密码');
define('DB_NAME', '数据库名');
```

4. 配置Web服务器
将网站根目录指向项目目录

5. 设置文件权限
```bash
chmod 755 -R ./
chmod 777 -R ./uploads/  # 如果需要上传功能
```

## 默认账号

- 管理员账号: admin
- 管理员密码: admin123

## 目录结构

```
.
├── README.md           # 项目说明文档
├── config.php         # 配置文件
├── database.sql       # 数据库结构
├── auth.php           # 用户认证类
├── post.php           # 帖子管理类
├── comment.php        # 评论管理类
├── settings.php       # 系统设置类
├── api.php            # API接口
├── index.php          # 前端首页
├── post.php           # 帖子详情页
└── admin.php          # 管理后台
```

## API文档

### 用户相关

- POST /api.php?path=register - 用户注册
- POST /api.php?path=login - 用户登录
- POST /api.php?path=logout - 用户登出
- GET /api.php?path=current-user - 获取当前用户信息

### 帖子相关

- GET /api.php?path=posts - 获取帖子列表
- POST /api.php?path=posts - 发布帖子
- GET /api.php?path=posts/detail - 获取帖子详情
- POST /api.php?path=posts/approve - 审核帖子
- POST /api.php?path=posts/top - 置顶/取消置顶帖子
- POST /api.php?path=posts/delete - 删除帖子

### 评论相关

- GET /api.php?path=comments - 获取评论列表
- POST /api.php?path=comments - 发表评论
- POST /api.php?path=comments/approve - 审核评论
- POST /api.php?path=comments/delete - 删除评论

### 系统设置相关

- GET /api.php?path=settings - 获取系统设置
- POST /api.php?path=settings - 更新系统设置

## 安全说明

1. 所有用户密码使用 `password_hash()` 加密存储
2. 所有SQL查询使用预处理语句,防止SQL注入
3. 所有输出数据使用 `htmlspecialchars()` 转义,防止XSS攻击
4. API接口使用JSON格式通信,防止CSRF攻击

## 开发建议

1. 添加验证码功能,防止垃圾注册
2. 添加角色管理功能,支持更细粒度的权限控制
3. 优化前端交互,添加更多动画效果
4. 添加内容过滤功能,过滤敏感词
5. 添加图片上传功能,支持发帖和评论时上传图片
6. 添加用户头像功能
7. 添加帖子分类功能
8. 添加搜索功能
9. 添加用户消息通知功能
10. 添加数据统计功能 
