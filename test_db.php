<?php
require_once 'config.php';

try {
    // 测试数据库连接
    $conn = db_connect();
    echo "数据库连接成功!<br>";

    // 检查users表是否存在
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if($result->num_rows > 0) {
        echo "users表存在!<br>";
        
        // 检查表结构
        $result = $conn->query("DESCRIBE users");
        echo "users表结构:<br>";
        while($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
        
        // 检查是否有管理员账号
        $result = $conn->query("SELECT * FROM users WHERE role = 'admin'");
        if($result->num_rows > 0) {
            echo "管理员账号存在!<br>";
        } else {
            echo "警告: 没有管理员账号!<br>";
        }
    } else {
        echo "警告: users表不存在!<br>";
    }

    // 测试创建用户
    $username = 'test_user_' . time();
    $password = password_hash('test123', PASSWORD_DEFAULT);
    $email = 'test' . time() . '@test.com';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $email);
    
    if($stmt->execute()) {
        echo "测试用户创建成功!<br>";
        
        // 测试登录
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if(password_verify('test123', $user['password'])) {
                echo "密码验证成功!<br>";
            } else {
                echo "警告: 密码验证失败!<br>";
            }
        } else {
            echo "警告: 用户查询失败!<br>";
        }
        
        // 清理测试数据
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        echo "测试数据已清理!<br>";
    } else {
        echo "警告: 测试用户创建失败! 错误: " . $stmt->error . "<br>";
    }

} catch(Exception $e) {
    echo "错误: " . $e->getMessage();
}
?> 