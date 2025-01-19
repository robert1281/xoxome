<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'post.php';

// 初始化对象
$auth = new Auth();
$post = new Post();

// 检查数据库连接
$conn = db_connect();
if (!$conn) {
    die("数据库连接失败");
}

// 检查posts表是否存在
$result = $conn->query("SHOW TABLES LIKE 'posts'");
if ($result->num_rows == 0) {
    die("posts表不存在");
}

// 检查帖子数量
$result = $conn->query("SELECT COUNT(*) as total FROM posts");
$total = $result->fetch_assoc()['total'];
echo "帖子总数: " . $total . "\n";

// 检查帖子状态分布
$result = $conn->query("SELECT status, COUNT(*) as count FROM posts GROUP BY status");
echo "\n帖子状态分布:\n";
while ($row = $result->fetch_assoc()) {
    echo "状态 " . $row['status'] . ": " . $row['count'] . " 个帖子\n";
}

// 如果没有帖子，创建一些测试帖子
if ($total == 0) {
    echo "\n开始创建测试帖子...\n";
    
    // 确保管理员用户存在
    $admin = $conn->query("SELECT id FROM users WHERE username = 'admin'")->fetch_assoc();
    if (!$admin) {
        echo "管理员用户不存在，请先创建管理员账号\n";
        exit;
    }
    
    // 创建测试帖子
    $titles = [
        'Cursor中文破解论坛欢迎你',
        'Cursor IDE使用教程',
        'Cursor Pro破解版下载地址',
        'Cursor常见问题解答',
        'Cursor新版本功能介绍'
    ];
    
    $content = "这是一个测试帖子的内容。\n\n包含了一些基本的文本信息。";
    
    foreach ($titles as $title) {
        $result = $post->create($admin['id'], $title, $content);
        if ($result['success']) {
            echo "成功创建帖子: $title\n";
        } else {
            echo "创建帖子失败: {$result['message']}\n";
        }
    }
    
    echo "\n创建测试帖子完成！\n";
}

// 获取最新的5个帖子
$result = $conn->query("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
echo "\n最新5个帖子:\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "标题: " . $row['title'] . "\n";
    echo "作者: " . $row['username'] . "\n";
    echo "状态: " . $row['status'] . "\n";
    echo "创建时间: " . $row['created_at'] . "\n";
    echo "-------------------\n";
}
?> 