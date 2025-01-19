<?php
require_once 'config.php';

class Post {
    private $conn;

    public function __construct() {
        $this->conn = db_connect();
    }

    // 创建新帖子
    public function create($user_id, $title, $content) {
        try {
            if(empty($title) || empty($content)) {
                return ['success' => false, 'message' => '标题和内容不能为空'];
            }

            $title = clean_input($title);
            
            // 直接使用内容，不进行HTML实体解码
            $content = $this->sanitizeContent($content);

            // 检查是否需要审核
            $settings = new Settings();
            $status = $settings->isPostNeedReview() ? 0 : 1;
            
            // 游客发帖默认需要审核
            if($user_id === 0) {
                $status = 0;
            }

            $stmt = $this->conn->prepare("INSERT INTO posts (user_id, title, content, status) VALUES (?, ?, ?, ?)");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '发帖失败，请稍后重试'];
            }

            $stmt->bind_param("issi", $user_id, $title, $content, $status);
            if(!$stmt->execute()) {
                error_log("发帖失败: " . $stmt->error);
                return ['success' => false, 'message' => '发帖失败，请稍后重试'];
            }

            $message = $status === 0 ? '发帖成功，等待管理员审核' : '发帖成功';
            if($user_id === 0) {
                $message = '发帖成功，等待管理员审核';
            }
            return ['success' => true, 'message' => $message, 'post_id' => $stmt->insert_id];
        } catch(Exception $e) {
            error_log("发帖异常: " . $e->getMessage());
            return ['success' => false, 'message' => '发帖失败，请稍后重试'];
        }
    }

    // 添加sanitizeContent方法
    private function sanitizeContent($content) {
        // 如果内容为空，直接返回
        if (empty($content)) {
            return '';
        }

        try {
            error_log("开始处理内容: " . substr($content, 0, 100) . "...");
            
            // 允许的HTML标签和属性
            $allowed_tags = [
                'p', 'br', 'b', 'i', 'u', 'strong', 'em', 'strike', 'blockquote', 'code', 'pre',
                'h1', 'h2', 'ul', 'ol', 'li', 'a', 'img', 'div', 'span', 'attachment'
            ];
            
            $allowed_attrs = [
                'href', 'src', 'alt', 'title', 'class', 'style', 'target', 'data-filename', 'data-filesize'
            ];

            // 创建新的DOMDocument
            $dom = new DOMDocument('1.0', 'UTF-8');
            
            // 禁用错误报告
            libxml_use_internal_errors(true);
            
            // 设置一些选项来保持内容格式
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = false;
            
            // 使用UTF-8编码加载HTML
            $content = '<div>' . $content . '</div>';
            error_log("转换前的内容: " . $content);
            
            // 确保内容是UTF-8编码
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                error_log("内容编码已转换为UTF-8");
            }
            
            // 转换HTML实体
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
            error_log("HTML实体转换后的内容: " . $content);
            
            // 加载HTML
            $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
            
            // 获取并记录libxml错误
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                error_log("LibXML错误: " . $error->message);
            }
            libxml_clear_errors();
            
            // 递归清理节点
            $this->cleanNodes($dom, $allowed_tags, $allowed_attrs);
            
            // 获取body内容
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body) {
                $body = $dom->getElementsByTagName('div')->item(0);
            }
            
            // 导出处理后的HTML
            $clean_content = '';
            if ($body) {
                foreach ($body->childNodes as $node) {
                    $clean_content .= $dom->saveHTML($node);
                }
            }
            
            error_log("清理后的内容: " . $clean_content);
            
            // 将HTML实体转换回UTF-8字符
            $clean_content = html_entity_decode($clean_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            error_log("最终的内容: " . $clean_content);
            
            return $clean_content;
        } catch (Exception $e) {
            error_log("内容清理异常: " . $e->getMessage());
            error_log("异常堆栈: " . $e->getTraceAsString());
            // 如果处理失败，返回原始内容
            return $content;
        }
    }

    // 递归清理节点的辅助方法
    private function cleanNodes($dom, $allowed_tags, $allowed_attrs) {
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) return;

        $this->cleanNode($body, $allowed_tags, $allowed_attrs);
    }

    private function cleanNode($node, $allowed_tags, $allowed_attrs) {
        if (!$node) return;

        // 处理子节点
        $children = [];
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $children[] = $node->childNodes->item($i);
        }
        
        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                // 检查标签是否允许
                if (!in_array(strtolower($child->tagName), $allowed_tags)) {
                    // 保留内容，移除标签
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                } else {
                    // 清理属性
                    $attrs = [];
                    foreach ($child->attributes as $attr) {
                        $attrs[] = $attr;
                    }
                    foreach ($attrs as $attr) {
                        if (!in_array($attr->name, $allowed_attrs)) {
                            $child->removeAttribute($attr->name);
                        }
                    }
                    // 递归处理子节点
                    $this->cleanNode($child, $allowed_tags, $allowed_attrs);
                }
            }
        }
    }

    // 获取帖子列表
    public function getList($page = 1, $limit = 10, $admin = false, $tag = null) {
        try {
            $offset = ($page - 1) * $limit;
            
            debug_log("开始获取帖子列表: 页码=$page, 每页数量=$limit, 偏移量=$offset, 管理员模式=$admin, 标签=$tag");
            
            // 基础 SQL 查询
            $sql = "SELECT p.*, u.username, 
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count 
                   FROM posts p 
                   LEFT JOIN users u ON p.user_id = u.id 
                   WHERE 1=1";
            
            // 非管理员只能看到已审核的帖子
            if(!$admin) {
                $sql .= " AND p.status = 1";
            }
            
            // 如果指定了标签，添加标签过滤条件
            $params = [];
            $types = "";
            if($tag !== null) {
                $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
                $tag_param = "%" . $tag . "%";
                $params[] = $tag_param;
                $params[] = $tag_param;
                $types .= "ss";
            }
            
            // 添加排序和分页
            $sql .= " ORDER BY p.is_top DESC, p.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            debug_log("SQL查询: " . $sql);
            debug_log("参数类型: " . $types);
            debug_log("参数值: " . print_r($params, true));
            
            $stmt = $this->conn->prepare($sql);
            if(!$stmt) {
                debug_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '获取帖子列表失败'];
            }

            if(!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if(!$stmt->execute()) {
                debug_log("执行查询失败: " . $stmt->error);
                return ['success' => false, 'message' => '获取帖子列表失败'];
            }

            $result = $stmt->get_result();
            $posts = [];
            while($row = $result->fetch_assoc()) {
                // 处理游客发帖的用户名显示
                if($row['user_id'] === 0) {
                    $row['username'] = '游客';
                }
                $posts[] = $row;
            }

            // 获取总帖子数
            $count_sql = "SELECT COUNT(*) as total FROM posts WHERE 1=1" .
                (!$admin ? " AND status = 1" : "") .
                ($tag !== null ? " AND (title LIKE ? OR content LIKE ?)" : "");
                
            if($tag !== null) {
                $count_stmt = $this->conn->prepare($count_sql);
                $count_stmt->bind_param("ss", $tag_param, $tag_param);
                $count_stmt->execute();
                $total = $count_stmt->get_result()->fetch_assoc()['total'];
            } else {
                $total = $this->conn->query($count_sql)->fetch_assoc()['total'];
            }
            
            debug_log("查询结果: 总帖子数=$total, 当前页帖子数=" . count($posts));
            debug_log("帖子数据: " . print_r($posts, true));

            return [
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch(Exception $e) {
            debug_log("获取帖子列表异常: " . $e->getMessage());
            debug_log("异常堆栈: " . $e->getTraceAsString());
            return ['success' => false, 'message' => '获取帖子列表失败'];
        }
    }

    // 获取单个帖子详情
    public function getDetail($post_id) {
        try {
            $sql = "SELECT p.*, u.username, 
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 1) as comment_count 
                   FROM posts p 
                   LEFT JOIN users u ON p.user_id = u.id 
                   WHERE p.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '获取帖子详情失败'];
            }

            $stmt->bind_param("i", $post_id);
            if(!$stmt->execute()) {
                error_log("获取帖子详情失败: " . $stmt->error);
                return ['success' => false, 'message' => '获取帖子详情失败'];
            }

            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                return ['success' => false, 'message' => '帖子不存在或已被删除'];
            }

            // 更新浏览量
            $this->conn->query("UPDATE posts SET views = views + 1 WHERE id = $post_id");

            return ['success' => true, 'data' => $result->fetch_assoc()];
        } catch(Exception $e) {
            error_log("获取帖子详情异常: " . $e->getMessage());
            return ['success' => false, 'message' => '获取帖子详情失败'];
        }
    }

    // 更新帖子
    public function update($post_id, $user_id, $title, $content) {
        try {
            if(empty($title) || empty($content)) {
                return ['success' => false, 'message' => '标题和内容不能为空'];
            }

            $title = clean_input($title);
            // 使用sanitizeContent处理内容
            $content = $this->sanitizeContent($content);

            // 检查帖子是否存在
            $stmt = $this->conn->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 0) {
                return ['success' => false, 'message' => '帖子不存在'];
            }
            
            $post = $result->fetch_assoc();
            
            // 检查权限：只有管理员或帖子作者可以编辑
            if($post['user_id'] !== $user_id) {
                // 检查是否是管理员
                if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                    return ['success' => false, 'message' => '没有权限编辑此帖子'];
                }
            }

            $stmt = $this->conn->prepare("UPDATE posts SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '更新帖子失败'];
            }

            $stmt->bind_param("ssi", $title, $content, $post_id);
            if(!$stmt->execute()) {
                error_log("更新帖子失败: " . $stmt->error);
                return ['success' => false, 'message' => '更新帖子失败'];
            }

            return ['success' => true, 'message' => '更新成功'];
        } catch(Exception $e) {
            error_log("更新帖子异常: " . $e->getMessage());
            return ['success' => false, 'message' => '更新帖子失败'];
        }
    }

    // 删除帖子
    public function delete($post_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM posts WHERE id = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '删除帖子失败'];
            }

            $stmt->bind_param("i", $post_id);
            if(!$stmt->execute()) {
                error_log("删除帖子失败: " . $stmt->error);
                return ['success' => false, 'message' => '删除帖子失败'];
            }

            // 同时删除相关的评论
            $this->conn->query("DELETE FROM comments WHERE post_id = $post_id");

            return ['success' => true, 'message' => '帖子已删除'];
        } catch(Exception $e) {
            error_log("删除帖子异常: " . $e->getMessage());
            return ['success' => false, 'message' => '删除帖子失败'];
        }
    }

    // 审核帖子
    public function approve($post_id, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '审核帖子失败'];
            }

            $stmt->bind_param("ii", $status, $post_id);
            if(!$stmt->execute()) {
                error_log("审核帖子失败: " . $stmt->error);
                return ['success' => false, 'message' => '审核帖子失败'];
            }

            return ['success' => true, 'message' => $status === 1 ? '帖子已通过审核' : '帖子已驳回'];
        } catch(Exception $e) {
            error_log("审核帖子异常: " . $e->getMessage());
            return ['success' => false, 'message' => '审核帖子失败'];
        }
    }

    // 置顶/取消置顶帖子
    public function toggleTop($post_id) {
        try {
            // 先获取当前置顶状态
            $stmt = $this->conn->prepare("SELECT is_top FROM posts WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 0) {
                return ['success' => false, 'message' => '帖子不存在'];
            }
            
            $current_status = $result->fetch_assoc()['is_top'];
            $new_status = $current_status ? 0 : 1;
            
            // 更新置顶状态
            $stmt = $this->conn->prepare("UPDATE posts SET is_top = ? WHERE id = ?");
            if(!$stmt) {
                error_log("SQL准备失败: " . $this->conn->error);
                return ['success' => false, 'message' => '更新置顶状态失败'];
            }

            $stmt->bind_param("ii", $new_status, $post_id);
            if(!$stmt->execute()) {
                error_log("更新置顶状态失败: " . $stmt->error);
                return ['success' => false, 'message' => '更新置顶状态失败'];
            }

            return ['success' => true, 'message' => $new_status ? '帖子已置顶' : '帖子已取消置顶'];
        } catch(Exception $e) {
            error_log("更新置顶状态异常: " . $e->getMessage());
            return ['success' => false, 'message' => '更新置顶状态失败'];
        }
    }

    // 获取热门标签
    public function getTags($limit = 20) {
        try {
            // 从已审核的帖子中提取标题和内容的关键词
            $sql = "SELECT title, content FROM posts WHERE status = 1";
            $result = $this->conn->query($sql);
            
            if(!$result) {
                debug_log("获取帖子数据失败: " . $this->conn->error);
                return ['success' => false, 'message' => '获取标签失败'];
            }
            
            $keywords = [];
            $stopwords = ['的', '了', '和', '与', '或', '在', '是', '我', '你', '他', '它', '这', '那', '都', '有'];
            
            while($row = $result->fetch_assoc()) {
                // 合并标题和内容
                $text = $row['title'] . ' ' . $row['content'];
                
                // 使用正则表达式提取中文词组（2-4个字）
                preg_match_all('/[\x{4e00}-\x{9fa5}]{2,4}/u', $text, $matches);
                
                foreach($matches[0] as $word) {
                    if(!in_array($word, $stopwords)) {
                        if(isset($keywords[$word])) {
                            $keywords[$word]++;
                        } else {
                            $keywords[$word] = 1;
                        }
                    }
                }
            }
            
            // 按出现频率排序
            arsort($keywords);
            
            // 只返回前 $limit 个标签
            $tags = array_slice($keywords, 0, $limit, true);
            
            return [
                'success' => true,
                'data' => array_map(function($word, $count) {
                    return [
                        'text' => $word,
                        'count' => $count
                    ];
                }, array_keys($tags), array_values($tags))
            ];
            
        } catch(Exception $e) {
            debug_log("获取标签异常: " . $e->getMessage());
            return ['success' => false, 'message' => '获取标签失败'];
        }
    }
}
?> 