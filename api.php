<?php
// 设置错误处理
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once 'config.php';
require_once 'auth.php';
require_once 'post.php';
require_once 'comment.php';
require_once 'settings.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 初始化对象
$auth = new Auth();
$post = new Post();
$comment = new Comment();

// 获取请求路径
$path = $_GET['path'] ?? '';

// 记录请求信息
debug_log("请求开始: " . $_SERVER['REQUEST_METHOD'] . " " . $path);
debug_log("GET数据: " . print_r($_GET, true));
debug_log("POST数据: " . print_r($_POST, true));
debug_log("原始输入: " . file_get_contents('php://input'));

try {
    switch($path) {
        case 'login':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            $result = $auth->login($_POST['username'], $_POST['password']);
            debug_log("登录结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'register':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            $result = $auth->register($_POST['username'], $_POST['password'], $_POST['email']);
            debug_log("注册结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'logout':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            $result = $auth->logout();
            debug_log("登出结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'current_user':
            $result = ['success' => true, 'data' => $auth->getCurrentUser()];
            debug_log("当前用户: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'posts':
            if($_SERVER['REQUEST_METHOD'] === 'GET') {
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                $tag = isset($_GET['tag']) ? $_GET['tag'] : null;
                
                // 检查是否是管理员
                $is_admin = $auth->isAdmin();
                $result = $post->getList($page, $limit, $is_admin, $tag);
                
                echo json_encode($result);
            }
            else if($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 检查是否允许发帖
                $settings = new Settings();
                if(!$auth->isLoggedIn() && !$settings->isGuestPostAllowed()) {
                    echo json_encode(['success' => false, 'message' => '请先登录']);
                    break;
                }

                // 获取当前用户
                $currentUser = $auth->getCurrentUser();
                $user_id = $currentUser ? $currentUser['id'] : 0;

                // 获取原始POST数据
                $raw_data = file_get_contents('php://input');
                $data = json_decode($raw_data, true);
                
                // 检查JSON解析是否成功
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode([
                        'success' => false,
                        'message' => '无效的JSON数据',
                        'error' => json_last_error_msg()
                    ]);
                    break;
                }
                
                // 检查必要字段
                if (!isset($data['title']) || !isset($data['content'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => '标题和内容不能为空'
                    ]);
                    break;
                }
                
                // 创建帖子
                $result = $post->create($user_id, $data['title'], $data['content']);
                echo json_encode($result);
            }
            break;
            
        case 'posts/detail':
            if($_SERVER['REQUEST_METHOD'] === 'GET') {
                $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                debug_log("获取帖子详情: ID=$post_id");
                $result = $post->getDetail($post_id);
                debug_log("帖子详情结果: " . print_r($result, true));
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            }
            break;
            
        case 'comments':
            if($_SERVER['REQUEST_METHOD'] === 'GET') {
                // 获取评论列表
                $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                debug_log("获取评论列表: post_id=$post_id, 页码=$page");
                $result = $comment->getList($post_id, $page);
                debug_log("评论列表结果: " . print_r($result, true));
                echo json_encode(['success' => true, 'data' => $result]);
            } 
            else if($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 发表新评论
                $settings = new Settings();
                $input = json_decode(file_get_contents('php://input'), true);
                
                // 检查是否允许评论
                if(!$auth->isLoggedIn() && !$settings->isGuestPostAllowed()) {
                    debug_log("未登录用户尝试评论，但不允许游客评论");
                    echo json_encode(['success' => false, 'message' => '请先登录']);
                    exit;
                }
                
                if(!isset($input['post_id']) || !isset($input['content'])) {
                    echo json_encode(['success' => false, 'message' => '参数错误']);
                    exit;
                }
                
                if(empty($input['content'])) {
                    echo json_encode(['success' => false, 'message' => '评论内容不能为空']);
                    exit;
                }
                
                debug_log("发表评论: post_id=" . $input['post_id'] . ", content=" . $input['content']);
                $result = $comment->create($input['post_id'], $input['content']);
                debug_log("发表评论结果: " . print_r($result, true));
                echo json_encode($result);
            }
            else {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            }
            break;
            
        case 'comments/approve':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            if(!$auth->isAdmin()) {
                echo json_encode(['success' => false, 'message' => '无权限进行此操作']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if(!isset($input['id']) || !isset($input['status'])) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            
            $result = $comment->approve($input['id'], $input['status']);
            debug_log("审核评论结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'comments/delete':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if(!isset($input['id'])) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            
            $result = $comment->delete($input['id']);
            debug_log("删除评论结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'posts/approve':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            if(!$auth->isAdmin()) {
                echo json_encode(['success' => false, 'message' => '无权限进行此操作']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if(!isset($input['id']) || !isset($input['status'])) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            
            $result = $post->approve($input['id'], $input['status']);
            debug_log("审核帖子结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'posts/top':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            if(!$auth->isAdmin()) {
                echo json_encode(['success' => false, 'message' => '无权限进行此操作']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if(!isset($input['id'])) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            
            $result = $post->toggleTop($input['id']);
            debug_log("置顶帖子结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'posts/delete':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            if(!$auth->isAdmin()) {
                echo json_encode(['success' => false, 'message' => '无权限进行此操作']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if(!isset($input['id'])) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            
            $result = $post->delete($input['id']);
            debug_log("删除帖子结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'posts/update':
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            // 检查是否是管理员
            if(!$auth->isAdmin()) {
                echo json_encode(['success' => false, 'message' => '没有权限']);
                exit;
            }

            // 获取原始POST数据
            $raw_data = file_get_contents('php://input');
            $data = json_decode($raw_data, true);
            
            // 检查JSON解析是否成功
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode([
                    'success' => false,
                    'message' => '无效的JSON数据',
                    'error' => json_last_error_msg()
                ]);
                break;
            }
            
            // 检查必要字段
            if (!isset($data['id']) || !isset($data['title']) || !isset($data['content'])) {
                echo json_encode([
                    'success' => false,
                    'message' => '缺少必要的字段'
                ]);
                break;
            }
            
            // 更新帖子
            $result = $post->update($data['id'], 0, $data['title'], $data['content']);
            echo json_encode($result);
            break;
            
        case 'settings':
            $settings = new Settings();
            if($_SERVER['REQUEST_METHOD'] === 'GET') {
                // 获取所有设置
                $result = $settings->getAll();
                debug_log("获取设置结果: " . print_r($result, true));
                echo json_encode(['success' => true, 'data' => $result]);
            } 
            else if($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 更新设置
                if(!$auth->isAdmin()) {
                    echo json_encode(['success' => false, 'message' => '无权限进行此操作']);
                    exit;
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                if(!isset($input['settings']) || !is_array($input['settings'])) {
                    echo json_encode(['success' => false, 'message' => '参数错误']);
                    exit;
                }
                
                $result = $settings->update($input['settings']);
                debug_log("更新设置结果: " . print_r($result, true));
                echo json_encode($result);
            }
            else {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            }
            break;
            
        case 'posts/tags':
            if($_SERVER['REQUEST_METHOD'] !== 'GET') {
                echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
                exit;
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $result = $post->getTags($limit);
            debug_log("获取标签结果: " . print_r($result, true));
            echo json_encode($result);
            break;
            
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // 检查是否有文件上传
                if (!isset($_FILES['file'])) {
                    echo json_encode(['success' => false, 'message' => '没有文件被上传']);
                    break;
                }

                $file = $_FILES['file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => '文件上传失败']);
                    break;
                }

                // 检查文件大小（20MB限制）
                if ($file['size'] > 20 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => '文件大小不能超过20MB']);
                    break;
                }

                // 创建上传目录
                $upload_dir = 'uploads/' . date('Y/m');
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // 生成唯一的文件名
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . '/' . $filename;

                // 移动文件到目标位置
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // 获取文件大小的可读格式
                    $size = $file['size'];
                    if ($size < 1024) {
                        $size = $size . 'B';
                    } elseif ($size < 1024 * 1024) {
                        $size = round($size / 1024, 2) . 'KB';
                    } else {
                        $size = round($size / (1024 * 1024), 2) . 'MB';
                    }

                    echo json_encode([
                        'success' => true,
                        'file_url' => '/' . $filepath,
                        'file_name' => $file['name'],
                        'file_size' => $size,
                        'message' => '文件上传成功'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => '文件保存失败']);
                }
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '接口不存在']);
            break;
    }
} catch(Exception $e) {
    debug_log("API异常: " . $e->getMessage());
    debug_log("异常堆栈: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => '服务器错误']);
}

// 记录响应信息
debug_log("请求结束: " . $path);

function debug_log($message) {
    $log_file = __DIR__ . '/logs/debug.log';
    $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    
    // 确保日志目录存在
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    
    // 如果文件不存在，添加 UTF-8 BOM
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "\xEF\xBB\xBF");
    }
    
    // 确保消息是UTF-8编码
    if (!mb_check_encoding($log_message, 'UTF-8')) {
        $log_message = mb_convert_encoding($log_message, 'UTF-8', 'auto');
    }
    
    // 使用FILE_APPEND标志追加内容
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}
?> 