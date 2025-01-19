<?php
// 站点配置
define('SITE_NAME', 'Cursor中文破解论坛');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);

// 数据库配置
define('DB_HOST', '144.48.243.172');
define('DB_NAME', 'cursordiscuss');
define('DB_USER', 'cursordiscuss');
define('DB_PASS', '74FTAz2ZyJR8Bt22');
define('DB_PORT', '3306');

// 数据库连接函数
function db_connect() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            error_log("数据库连接失败: " . $conn->connect_error);
            die("数据库连接失败，请稍后重试");
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// 清理输入数据
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// 生成随机字符串
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// 开启session
session_start();

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 错误处理
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// 确保logs目录存在
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// 记录访问日志
$log_message = sprintf(
    "[%s] %s %s %s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
);
file_put_contents(__DIR__ . '/logs/access.log', $log_message, FILE_APPEND);
?> 