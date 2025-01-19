<?php
require_once 'config.php';
require_once 'auth.php';

class Comment {
    private $conn;
    private $auth;

    public function __construct() {
        $this->conn = db_connect();
        $this->auth = new Auth();
    }

    // 发表评论
    public function create($post_id, $content) {
        try {
            if(empty($content)) {
                return ['success' => false, 'message' => '评论内容不能为空'];
            }

            $content = clean_input($content);
            
            // 检查帖子是否存在
            $stmt = $this->conn->prepare("SELECT id FROM posts WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 0) {
                return ['success' => false, 'message' => '帖子不存在或已被删除'];
            }
            
            // 获取当前用户ID
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            
            // 检查是否需要审核
            $settings = new Settings();
            $status = $settings->isCommentNeedReview() ? 0 : 1;
            
            $stmt = $this->conn->prepare("INSERT INTO comments (post_id, user_id, content, status) VALUES (?, ?, ?, ?)");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '发表评论失败'];
            }

            $stmt->bind_param("iisi", $post_id, $user_id, $content, $status);
            if(!$stmt->execute()) {
                error_log("发表评论失败: " . $stmt->error);
                return ['success' => false, 'message' => '发表评论失败'];
            }

            $message = $status === 0 ? '评论发表成功，等待审核' : '评论发表成功';
            return ['success' => true, 'message' => $message];
        } catch(Exception $e) {
            error_log("发表评论异常: " . $e->getMessage());
            return ['success' => false, 'message' => '发表评论失败'];
        }
    }

    // 获取评论列表
    public function getList($post_id = null, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT c.*, u.username, p.title as post_title 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                LEFT JOIN posts p ON c.post_id = p.id 
                WHERE 1=1 ";
        
        $params = [];
        $types = "";
        
        if($post_id) {
            $sql .= "AND c.post_id = ? ";
            $params[] = $post_id;
            $types .= "i";
        }
        
        if(!$this->auth->isAdmin()) {
            $sql .= "AND c.status = 1 ";
        }
        
        $sql .= "ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        if(!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 审核评论
    public function approve($id, $status) {
        if(!$this->auth->isAdmin()) {
            return ['success' => false, 'message' => '无权限进行此操作'];
        }

        $stmt = $this->conn->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);

        if($stmt->execute()) {
            return ['success' => true, 'message' => '操作成功'];
        } else {
            return ['success' => false, 'message' => '操作失败'];
        }
    }

    // 删除评论
    public function delete($id) {
        if(!$this->auth->isAdmin()) {
            $stmt = $this->conn->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if($result['user_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'message' => '无权限删除此评论'];
            }
        }

        $stmt = $this->conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $id);

        if($stmt->execute()) {
            return ['success' => true, 'message' => '删除成功'];
        } else {
            return ['success' => false, 'message' => '删除失败'];
        }
    }

    // 获取评论总数
    public function getCount($post_id = null) {
        $sql = "SELECT COUNT(*) as count FROM comments WHERE status = 1";
        $params = [];
        $types = "";
        
        if($post_id) {
            $sql .= " AND post_id = ?";
            $params[] = $post_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        if(!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc()['count'];
    }
}
?> 