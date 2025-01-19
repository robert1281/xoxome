<?php
require_once 'config.php';

$conn = db_connect();
$password = password_hash('Aa89031281@', PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $password);

if($stmt->execute()) {
    echo "管理员密码已更新为: Aa89031281@";
} else {
    echo "更新失败: " . $stmt->error;
}

$stmt->close();
$conn->close();
?> 