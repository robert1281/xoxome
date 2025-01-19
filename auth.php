<?php
require_once 'config.php';

class Auth {
    private $conn;

    public function __construct() {
        $this->conn = db_connect();
        $this->checkGuestLogin();
    }

    // 检查游客登录
    private function checkGuestLogin() {
        if(!$this->isLoggedIn()) {
            $settings = new Settings();
            if($settings->isGuestPostAllowed()) {
                // 查找或创建 guest 用户
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = 'guest'");
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($result->num_rows === 0) {
                    // 创建 guest 用户
                    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("INSERT INTO users (username, password, email, role) VALUES ('guest', ?, 'guest@example.com', 'user')");
                    $stmt->bind_param("s", $password_hash);
                    $stmt->execute();
                    $guest_id = $stmt->insert_id;
                } else {
                    $guest = $result->fetch_assoc();
                    $guest_id = $guest['id'];
                }
                
                // 自动登录为 guest 用户
                $_SESSION['user_id'] = $guest_id;
                $_SESSION['username'] = 'guest';
                $_SESSION['role'] = 'user';
            }
        }
    }

    // 用户注册
    public function register($username, $password, $email) {
        try {
            if(empty($username) || empty($password) || empty($email)) {
                return ['success' => false, 'message' => '所有字段都是必填的'];
            }

            if(strlen($username) < 3 || strlen($username) > 20) {
                return ['success' => false, 'message' => '用户名长度必须在3-20个字符之间'];
            }

            if(strlen($password) < 6) {
                return ['success' => false, 'message' => '密码长度必须大于6个字符'];
            }

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => '邮箱格式不正确'];
            }

            $username = clean_input($username);
            $email = clean_input($email);
            
            // 检查用户名是否已存在
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            $stmt->bind_param("s", $username);
            if(!$stmt->execute()) {
                error_log("执行查询失败: " . $stmt->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            if($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => '用户名已存在'];
            }
            
            // 检查邮箱是否已存在
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            $stmt->bind_param("s", $email);
            if(!$stmt->execute()) {
                error_log("执行查询失败: " . $stmt->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            if($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => '邮箱已被注册'];
            }
            
            // 密码加密
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if($hashed_password === false) {
                error_log("密码加密失败");
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            // 插入新用户
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            $stmt->bind_param("sss", $username, $hashed_password, $email);
            if(!$stmt->execute()) {
                error_log("插入用户失败: " . $stmt->error);
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
            if($stmt->affected_rows === 1) {
                return ['success' => true, 'message' => '注册成功'];
            } else {
                error_log("插入用户失败: 影响行数为0");
                return ['success' => false, 'message' => '注册失败，请稍后重试'];
            }
            
        } catch(Exception $e) {
            error_log("注册异常: " . $e->getMessage());
            return ['success' => false, 'message' => '注册失败，请稍后重试'];
        }
    }

    // 用户登录
    public function login($username, $password) {
        try {
            if(empty($username) || empty($password)) {
                return ['success' => false, 'message' => '用户名和密码不能为空'];
            }

            $username = clean_input($username);
            
            $stmt = $this->conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '登录失败，请稍后重试'];
            }
            
            $stmt->bind_param("s", $username);
            if(!$stmt->execute()) {
                error_log("执行查询失败: " . $stmt->error);
                return ['success' => false, 'message' => '登录失败，请稍后重试'];
            }
            
            $result = $stmt->get_result();
            
            if($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if($user['status'] === 0) {
                    return ['success' => false, 'message' => '账号已被禁用'];
                }
                
                if(password_verify($password, $user['password'])) {
                    // 更新最后登录时间
                    $stmt = $this->conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    if($stmt) {
                        $stmt->bind_param("i", $user['id']);
                        $stmt->execute();
                    }
                    
                    // 设置session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    return ['success' => true, 'message' => '登录成功'];
                }
            }
            
            return ['success' => false, 'message' => '用户名或密码错误'];
            
        } catch(Exception $e) {
            error_log("登录异常: " . $e->getMessage());
            return ['success' => false, 'message' => '登录失败，请稍后重试'];
        }
    }

    // 检查是否已登录
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // 获取当前用户信息
    public function getCurrentUser() {
        if(!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $stmt = $this->conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 0) {
            // 如果用户不存在，清除 session
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['role']);
            return null;
        }
        
        return $result->fetch_assoc();
    }

    // 检查是否是管理员
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE id = ? AND status = 1");
            if (!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $_SESSION['user_id']);
            if (!$stmt->execute()) {
                error_log("执行查询失败: " . $stmt->error);
                return false;
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                return $user['role'] === 'admin';
            }
            
            return false;
        } catch(Exception $e) {
            error_log("检查管理员权限异常: " . $e->getMessage());
            return false;
        }
    }

    // 用户登出
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => '已成功登出'];
    }
}
?> 