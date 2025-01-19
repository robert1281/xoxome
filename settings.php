<?php
require_once 'config.php';

class Settings {
    private $conn;
    
    public function __construct() {
        $this->conn = db_connect();
    }
    
    // 获取所有设置
    public function getAll() {
        try {
            $sql = "SELECT setting_key, setting_value FROM settings";
            $result = $this->conn->query($sql);
            
            if(!$result) {
                throw new Exception("获取设置失败: " . $this->conn->error);
            }
            
            $settings = [];
            while($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // 确保所有必要的设置都有默认值
            $defaults = [
                'site_name' => 'Cursor中文破解论坛',
                'site_description' => 'Cursor中文破解论坛 - 专注于技术分享与交流',
                'post_need_review' => '1',
                'comment_need_review' => '1',
                'allow_guest_post' => '0'
            ];
            
            foreach($defaults as $key => $value) {
                if(!isset($settings[$key])) {
                    $settings[$key] = $value;
                }
            }
            
            return $settings;
        } catch(Exception $e) {
            error_log("获取设置异常: " . $e->getMessage());
            return [];
        }
    }
    
    // 更新设置
    public function update($settings) {
        try {
            // 开始事务
            $this->conn->begin_transaction();
            
            // 验证必要的设置项
            $required = ['site_name', 'site_description'];
            foreach($required as $key) {
                if(!isset($settings[$key]) || trim($settings[$key]) === '') {
                    throw new Exception("缺少必要的设置项: $key");
                }
            }
            
            // 准备更新语句
            $stmt = $this->conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                        VALUES (?, ?) 
                                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            if(!$stmt) {
                throw new Exception("准备SQL语句失败: " . $this->conn->error);
            }
            
            // 更新每个设置项
            foreach($settings as $key => $value) {
                $stmt->bind_param("ss", $key, $value);
                if(!$stmt->execute()) {
                    throw new Exception("更新设置失败: " . $stmt->error);
                }
            }
            
            // 提交事务
            $this->conn->commit();
            
            return ['success' => true, 'message' => '设置已更新'];
        } catch(Exception $e) {
            // 回滚事务
            $this->conn->rollback();
            error_log("更新设置异常: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // 获取网站标题
    public function getSiteName() {
        $settings = $this->getAll();
        return $settings['site_name'] ?? 'Cursor中文破解论坛';
    }
    
    // 获取网站描述
    public function getSiteDescription() {
        $settings = $this->getAll();
        return $settings['site_description'] ?? 'Cursor中文破解论坛 - 专注于技术分享与交流';
    }
    
    // 检查帖子是否需要审核
    public function isPostNeedReview() {
        $settings = $this->getAll();
        return $settings['post_need_review'] === '1';
    }
    
    // 检查评论是否需要审核
    public function isCommentNeedReview() {
        $settings = $this->getAll();
        return $settings['comment_need_review'] === '1';
    }
    
    // 检查是否允许游客发帖
    public function isGuestPostAllowed() {
        $settings = $this->getAll();
        return $settings['allow_guest_post'] === '1';
    }
}
?> 